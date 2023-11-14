<?php

namespace WPStaging\Backup\Service\Database;

use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Exceptions\ThresholdException;
use WPStaging\Backup\Service\Database\Exporter\RowsExporter;
use WPStaging\Backup\Service\Database\Importer\Insert\QueryInserter;
use WPStaging\Backup\Service\Database\Importer\QueryCompatibility;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use RuntimeException;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Backup\Task\RestoreTask;

use function WPStaging\functions\debug_log;

class DatabaseImporter
{
    use ResourceTrait;

    /** @var FileObject */
    private $file;

    /** @var int */
    private $totalLines;

    /** @var InterfaceDatabaseClient */
    private $client;

    /** @var Database */
    private $database;

    /** @var LoggerInterface */
    private $logger;

    /** @var StepsDto */
    private $stepsDto;

    /** @var SearchReplace|null The SearchReplace instance that replaces insert into values. */
    private $searchReplace;

    /** @var SearchReplace The SearchReplace instance that replaces prefixes. */
    private $searchReplaceForPrefix;

    /** @var Database\WpDbAdapter */
    private $wpdb;

    /** @var string The temporary prefix used for the tables during restore. */
    private $tmpDatabasePrefix;

    /** @var JobRestoreDataDto */
    private $jobRestoreDataDto;

    /** @var QueryInserter */
    private $queryInserter;

    /** @var int */
    private $smallerSearchLength;

    /** @var int Pre-computated for performance, since this is used in the loop. */
    private $binaryFlagLength;

    /** @var QueryCompatibility Performs modifications to a query to make it portable between MySQL versions */
    private $queryCompatibility;

    /** @var RestoreTask */
    private $restoreTask;

    /** @var bool */
    private $getIsSameSiteBackupRestore = false;

    /** @var array */
    private $tablesExcludedFromSearchReplace = [];

    public function __construct(Database $database, JobDataDto $jobRestoreDataDto, QueryInserter $queryInserter, QueryCompatibility $queryCompatibility)
    {
        $this->client   = $database->getClient();
        $this->wpdb     = $database->getWpdba();
        $this->database = $database;
        // @phpstan-ignore-next-line
        $this->jobRestoreDataDto = $jobRestoreDataDto;

        $this->queryInserter      = $queryInserter;
        $this->queryCompatibility = $queryCompatibility;

        $this->binaryFlagLength = strlen(RowsExporter::BINARY_FLAG);
    }

    /**
     * @param string $filePath Full file path
     *
     * @return $this
     */
    public function setFile($filePath)
    {
        $this->file       = new FileObject($filePath);
        $this->totalLines = $this->file->totalLines();

        return $this;
    }

    public function seekLine($line)
    {
        if (!$this->file) {
            throw new RuntimeException('Restore file is not set');
        }

        $this->file->seek($line);

        return $this;
    }

    public function restore($tmpDatabasePrefix)
    {
        $this->tmpDatabasePrefix = $tmpDatabasePrefix;

        $this->setupSearchReplaceForPrefix();

        if (!$this->file) {
            throw new RuntimeException('Restore file is not set');
        }

        /*
         * Remove any sql_modes such as ANSI_QUOTES that might cause our queries to have an invalid syntax.
         *
         * Also set the NO_AUTO_VALUE_ON_ZERO, which forces MySQL to interpret a primary
         * key of zero as a literal zero, instead of nulling it and letting the auto-increment
         * kick-in. This is necessary for some rare cases where the primary key starts at zero,
         * which would trigger a duplicated primary key of 1.
         *
         * We override the SQL mode only on this session (this connection), as to avoid
         * side effects with other connections, so we have to run it once on every start
         * of the database restore request life cycle.
         */
        $this->exec("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

        // Fix Error 1118 Row size too large
        if (apply_filters('wpstg.backup.restore.innodbStrictModeOff', false) === true) {
            $this->exec("SET SESSION innodb_strict_mode=OFF");
        }

        try {
            while (true) {
                try {
                    $this->execute();
                } catch (\OutOfBoundsException $e) {
                    // Skipping INSERT query due to unexpected format...
                    $this->logger->debug($e->getMessage());
                }
            }
        } catch (FinishedQueueException $e) {
            $this->stepsDto->finish();
        } catch (ThresholdException $e) {
            // no-op
        } catch (\Exception $e) {
            $this->stepsDto->setCurrent($this->file->key());
            $this->logger->critical(substr($e->getMessage(), 0, 1000));
        }

        // Make sure we commit when bailing
        $this->queryInserter->commit();

        $this->stepsDto->setCurrent($this->file->key());
    }

    protected function setupSearchReplaceForPrefix()
    {
        /*
         * WPSTG_TMP_PREFIX: This prefix will be replaced by a unique temporary prefix for restoring, eg: wp123456_posts
         * WPSTG_FINAL_PREFIX: This prefix will be replaced by the final prefix after the temporary tables have been renamed as the permanent ones.
         *
         * Restoring {WPSTG_TMP_PREFIX}posts:
         * - Detect that the final prefix will be "wp_"
         * - Rename to "{WPSTG_TMP_PREFIX}posts" to "wp123456_posts"
         * - Replace any {WPSTG_FINAL_PREFIX}posts to "wp_posts", eg: In VALUES of rows that references tables, so that we can BACKUP from a site that use a different prefix from the destination one.
         * - After restoring everything, "wp123456_posts" will be renamed to "wp_posts"
         */
        $this->searchReplaceForPrefix = new SearchReplace(['{WPSTG_TMP_PREFIX}', '{WPSTG_FINAL_PREFIX}'], [$this->tmpDatabasePrefix, $this->wpdb->getClient()->prefix], true, []);
    }

    /**
     * @param LoggerInterface $logger
     * @param StepsDto $stepsDto
     * @return $this
     */
    public function setup(LoggerInterface $logger, StepsDto $stepsDto, RestoreTask $task)
    {
        $this->logger      = $logger;
        $this->stepsDto    = $stepsDto;
        $this->restoreTask = $task;

        $this->getIsSameSiteBackupRestore = $this->jobRestoreDataDto->getIsSameSiteBackupRestore();
        $this->queryInserter->initialize($this->database, $this->jobRestoreDataDto, $logger);

        $this->restoreTask->setTmpPrefix($this->jobRestoreDataDto->getTmpDatabasePrefix());

        $this->tablesExcludedFromSearchReplace = $this->jobRestoreDataDto->getBackupMetadata()->getNonWpTables();

        return $this;
    }

    /**
     * @param SearchReplace|null $searchReplace
     *
     * @return $this
     */
    public function setSearchReplace(SearchReplace $searchReplace)
    {
        $this->searchReplace = $searchReplace;

        // Any query that has fewer characters than this can be safely ignored for S/R.
        $this->smallerSearchLength = min($searchReplace->getSmallerSearchLength(), $this->binaryFlagLength);

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalLines()
    {
        return $this->totalLines;
    }

    /**
     * @return bool
     * @throws ThresholdException
     */
    private function execute()
    {
        if ($this->isDatabaseRestoreThreshold()) {
            throw new ThresholdException();
        }

        $query = $this->findExecutableQuery();

        if (!$query) {
            throw new FinishedQueueException();
        }

        /*
         * Example: INSERT INTO {WPSTG_TMP_PREFIX}options VALUES ('foo'); => INSERT INTO wp_options VALUES ('foo');
         */
        $query = $this->searchReplaceForPrefix->replace($query);

        $query = $this->maybeShorterTableNameForDropTableQuery($query);
        $query = $this->maybeShorterTableNameForCreateTableQuery($query);

        $this->replaceTableCollations($query);

        // "Insert" queries are handled differently than others.
        if (strpos($query, 'INSERT INTO') === 0) {
            if ($this->isExcludedInsertQuery($query)) {
                debug_log('processQuery - This query has been skipped from inserting by using a custom filter: ' . $query);
                $this->logger->warning(sprintf(__('The query has been skipped from inserting by using a custom filter: %s.', 'wp-staging'), $query));
                return false;
            }

            // Even if same site we may still need to run search replace against each value for NULL or BINARY values
            if (
                !$this->getIsSameSiteBackupRestore
                || (strpos($query, RowsExporter::BINARY_FLAG) !== false)
                || (strpos($query, RowsExporter::NULL_FLAG) !== false)
            ) {
                $this->searchReplaceInsertQuery($query);
            }

            try {
                $result = $this->queryInserter->processQuery($query);
            } catch (\Exception $e) {
                // These are thrown if Transaction cannot be started or committed, or when an insert query fails
                throw $e;
            }

            if ($result === null && $this->queryInserter->getLastError() !== false) {
                $this->logger->warning($this->queryInserter->getLastError());
            }
        } else {
            // We don't want transactions on any other query
            $this->queryInserter->commit();

            $this->queryCompatibility->removeDefiner($query);
            $this->queryCompatibility->removeSqlSecurity($query);
            $this->queryCompatibility->removeAlgorithm($query);

            $result = $this->exec($query);
        }

        $errorNo          = $this->client->errno();
        $errorMsg         = $this->client->error();
        $currentDbVersion = $this->database->getSqlVersion($compact = true);
        $backupDbVersion  = $this->jobRestoreDataDto->getBackupMetadata()->getSqlServerVersion();

        if ($result === false) {
            /**
             * @link https://mariadb.com/kb/en/mariadb-error-codes/
             */
            switch ($this->client->errno()) {
                case 1030:
                    $this->queryCompatibility->replaceTableEngineIfUnsupported($query);
                    $result = $this->exec($query);

                    if ($result) {
                        $this->logger->warning('Engine changed to InnoDB, as it your MySQL server does not support MyISAM.');
                    }

                    break;
                case 1071:
                case 1709:
                    $this->queryCompatibility->replaceTableRowFormat($query);
                    $replaceUtf8Mb4 = ($errorNo === 1071 && version_compare($currentDbVersion, '5.7', '<'));
                    if ($replaceUtf8Mb4) {
                        $this->queryCompatibility->convertUtf8Mb4toUtf8($query);
                    }

                    $result = $this->exec($query);

                    if ($result) {
                        $this->logger->warning('Row format changed to DYNAMIC, as it would exceed the maximum length according to your MySQL settings. To not see this message anymore, please upgrade your MySQL version or increase the row format.');
                    }

                    if ($replaceUtf8Mb4 && $result) {
                        $this->logger->warning('Encoding changed to UTF8 from UTF8MB4, as your current MySQL version max key length support is 767 bytes');
                    }

                    break;
                case 1214:
                    $this->queryCompatibility->removeFullTextIndexes($query);
                    $result = $this->exec($query);

                    if ($result) {
                        $this->logger->warning('FULLTEXT removed from query, as your current MySQL version does not support it. To not see this message anymore, please upgrade your MySQL version.');
                    }

                    break;
                case 1226:
                    /*
                     * Code: ER_USER_LIMIT_REACHED
                     * Format: User '%s' has exceeded the '%s' resource (current value: %ld)
                     */
                    if (stripos($this->client->error(), 'max_queries_per_hour') !== false) {
                        throw new RuntimeException('Your server has reached the maximum allowed queries per hour set by your admin or hosting provider. Please increase MySQL max_queries_per_hour_limit. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>');
                    } elseif (stripos($this->client->error(), 'max_updates_per_hour') !== false) {
                        throw new RuntimeException('Your server has reached the maximum allowed updates per hour set by your admin or hosting provider. Please increase MySQL max_updates_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>');
                    } elseif (stripos($this->client->error(), 'max_connections_per_hour') !== false) {
                        throw new RuntimeException('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_connections_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>');
                    } elseif (stripos($this->client->error(), 'max_user_connections') !== false) {
                        throw new RuntimeException('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_user_connections. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>');
                    }

                    break;
                case 1118:
                    throw new RuntimeException('Your server has reached the maximum row size of the table. Please refer to the documentation on how to fix it. <a href="https://wp-staging.com/docs/mysql-database-error-codes" target="_blank">Technical details</a>');
                    break;
                case 1059:
                    $shortIdentifiers = $this->queryCompatibility->shortenKeyIdentifiers($query);
                    $result           = $this->exec($query);

                    if ($result) {
                        foreach ($shortIdentifiers as $shortIdentifier => $identifier) {
                            $this->logger->warning(sprintf('Key identifier `%s` exceeds the characters limits, it is now shortened to `%s` to continue restoring.', $identifier, $shortIdentifier));
                        }
                    }

                    break;
                case 1064:
                    $tableName = $this->queryCompatibility->pageCompressionMySQL($query, $errorMsg);
                    if (!empty($tableName)) {
                        $result = $this->exec($query);
                    }

                    if (!empty($tableName) && $result) {
                        $this->logger->warning(sprintf('PAGE_COMPRESSED removed from Table: %s, as it is not a supported syntax in MySQL.', $tableName));
                    }

                    break;
                case 1813:
                    throw new RuntimeException('Could not restore the database. MySQL returned the error code 1813, which is related to a tablespace error that WP STAGING can\'t handle. Please contact your hosting company.');
            }

            if ($result) {
                return true;
            }

            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                $this->logger->warning(sprintf('Database Restorer - Failed Query: %s', substr($query, 0, 1000)));
                debug_log(sprintf('Database Restorer Failed Query: %s', substr($query, 0, 1000)));
            }

            // Fetch latest error if query fails after compatibility fixes.
            $errorNo  = $this->client->errno();
            $errorMsg = $this->client->error();

            $additionalInfo = '';
            if ($backupDbVersion !== $currentDbVersion) {
                $additionalInfo = sprintf(' Your current MySQL version is %s. If this issue persists, try using the same MySQL version used to create this Backup (%s).', $currentDbVersion, $backupDbVersion);
            }

            throw new RuntimeException(sprintf('Could not restore query. MySQL has returned the error code %d, with message "%s".', $errorNo, $errorMsg) . $additionalInfo);
        }

        return $result;
    }

    protected function maybeShorterTableNameForDropTableQuery(&$query)
    {
        if (strpos($query, "DROP TABLE IF EXISTS") !== 0) {
            return $query;
        }

        preg_match('#^DROP TABLE IF EXISTS `(.+?(?=`))`;$#', $query, $dropTableExploded);

        $tableName = $dropTableExploded[1];
        if (strlen($tableName) > 64) {
            $tableName = $this->restoreTask->addShortNameTable($tableName, $this->tmpDatabasePrefix);
        }

        return "DROP TABLE IF EXISTS `$tableName`;";
    }

    protected function maybeShorterTableNameForCreateTableQuery(&$query)
    {
        if (strpos($query, "CREATE TABLE") !== 0) {
            return $query;
        }

        preg_match('#^CREATE TABLE `(.+?(?=`))`#', $query, $createTableExploded);

        $tableName = $createTableExploded[1];
        if (strlen($tableName) > 64) {
            $shortName = $this->restoreTask->getShortNameTable($tableName, $this->tmpDatabasePrefix);
            return str_replace($tableName, $shortName, $query);
        }

        return $query;
    }

    protected function searchReplaceInsertQuery(&$query)
    {
        if (!$this->searchReplace) {
            throw new RuntimeException('SearchReplace not set');
        }

        // Early bail if query exceeds preg function limit
        $querySize = strlen($query);
        if ($querySize > ini_get('pcre.backtrack_limit')) {
            $this->logger->warning(
                sprintf(
                    'Skipped search & replace on query: "%s" Increasing pcre.backtrack_limit can fix it! Query Size: %s. pcre.backtrack_limit: %s',
                    substr($query, 0, 1000) . '...',
                    $querySize,
                    ini_get('pcre.backtrack_limit')
                )
            );
            return;
        }

        /**
         * Replace only values area, not anything else
         *
         * @todo: Execute this preg_match only once.
         */
        preg_match('#^INSERT INTO `(.+?(?=`))` VALUES (\(.+\));$#', $query, $insertIntoExploded);

        if (count($insertIntoExploded) !== 3) {
            debug_log($query);
            throw new \OutOfBoundsException('Skipping insert query. The query was logged....');
        }

        $tableName = $insertIntoExploded[1];
        if (strlen($tableName) > 64) {
            $tableName = $this->restoreTask->getShortNameTable($tableName, $this->tmpDatabasePrefix);
        }

        $values = $insertIntoExploded[2];

        /**
         * Match strings wrapped with single quotes, taking into consideration escaped single-quotes.
         *
         * @see string REGEX adapted from: https://stackoverflow.com/a/33617839/2056484
         */
        preg_match_all("#'(?:[^'\\\]++|\\\.)*+'#s", $values, $valueMatches);

        if (count($valueMatches) !== 1) {
            throw new RuntimeException('Value match in query does not match.');
        }

        $valueMatches = $valueMatches[0];

        $query = "INSERT INTO `$tableName` VALUES (";

        foreach ($valueMatches as $value) {
            if (empty($value) || $value === "''") {
                $query .= "'', ";
                continue;
            }

            // Null value
            if ($value === "'" . RowsExporter::NULL_FLAG . "'") {
                $query .= "NULL, ";
                continue;
            }

            /**
             * Save S/R effort on very small queries.
             * -2 comes from the surrounding quotes 'foo' => 3
             */
            if ($this->smallerSearchLength > strlen($value) - 2) {
                $query .= "{$value}, ";
                continue;
            }

            /**
             * Removes wrapping quotes, respecting the original string if it was
             * enclosed in quotes in the first place.
             *
             * We do this instead of using a capture group just for the values to
             * minimize memory usage.
             *
             * @example "'foo'" => "foo"
             * @example "'class=\'ngg_lightbox\''" => "class=\'ngg_lightbox\'"
             */
            $value = substr($value, 1, -1);

            if (strpos($value, RowsExporter::BINARY_FLAG) === 0) {
                $query .= "UNHEX('" . substr($value, strlen(RowsExporter::BINARY_FLAG)) . "'), ";
                continue;
            }

            // Early bail as there is no need to perform other search place as same site
            if ($this->getIsSameSiteBackupRestore || !$this->shouldSearchReplace($query)) {
                $query .= "'{$value}', ";
                continue;
            }

            if (is_serialized($value)) {
                $value = $this->undoMySqlRealEscape($value);
                $value = $this->searchReplace->replaceExtended($value);
                $value = $this->mySqlRealEscape($value);
            } else {
                $value = $this->searchReplace->replaceExtended($value);
            }

            $query .= "'{$value}', ";
        }

        $query = rtrim($query, ', ');

        $query .= ');';
    }

    /**
     * Revert the effects of real_escape_string
     *
     * It is very unlikely to have LIKE statement in INSERT query,
     * So there is no need to escape % and _ at the moment
     *
     * @see  https://www.php.net/manual/en/mysqli.real-escape-string.php
     * @link https://dev.mysql.com/doc/refman/8.0/en/string-literals.html#character-escape-sequences
     *
     * @param string $query The query to revert real_escape_string
     *
     */
    protected function undoMySqlRealEscape(&$query)
    {
        $replacementMap = [
            "\\0"  => "\0",
            "\\n"  => "\n",
            "\\r"  => "\r",
            "\\t"  => "\t",
            "\\Z"  => chr(26),
            "\\b"  => chr(8),
            '\"'   => '"',
            "\'"   => "'",
            '\\\\' => '\\',
        ];

        return strtr($query, $replacementMap);
    }

    /**
     * Mimics MySQLi real_escape_string, without having to open a DB connection.
     *
     * It is very unlikely to have LIKE statement in INSERT query,
     * So there is no need to escape % and _ at the moment
     *
     * @see  https://www.php.net/manual/en/mysqli.real-escape-string.php
     * @link https://dev.mysql.com/doc/refman/8.0/en/string-literals.html#character-escape-sequences
     *
     * @param string $query
     */
    protected function mySqlRealEscape(&$query)
    {
        $replacementMap = [
            "\0"    => "\\0",
            "\n"    => "\\n",
            "\r"    => "\\r",
            "\t"    => "\\t",
            chr(26) => "\\Z",
            chr(8)  => "\\b",
            '"'     => '\"',
            "'"     => "\'",
            '\\'    => '\\\\',
        ];

        return strtr($query, $replacementMap);
    }

    protected function shouldSearchReplace($query)
    {
        // Early bail: for older backups
        if (empty($this->tablesExcludedFromSearchReplace)) {
            return true;
        }

        preg_match('#^INSERT INTO `(.+?(?=`))` VALUES#', $query, $insertIntoExploded);
        $tableName = $insertIntoExploded[0];

        return !in_array($tableName, $this->tablesExcludedFromSearchReplace);
    }

    private function findExecutableQuery()
    {
        while (!$this->file->eof()) {
            $line = $this->getLine();
            if ($this->isExecutableQuery($line)) {
                return $line;
            }
        }

        return;
    }

    private function getLine()
    {
        if ($this->file->eof()) {
            return;
        }

        return trim($this->file->readAndMoveNext());
    }

    /**
     * Checks if given query / line is a valid, executable query
     * Valid SQL query means:
     * - Not an empty line
     * - Just a comment
     *
     * @param string|null $query
     *
     * @return bool
     */
    private function isExecutableQuery($query = null)
    {
        if (!$query) {
            return false;
        }

        // Line starts with -- or # (to the end of the line) comments
        $first2Chars = substr($query, 0, 2);
        if ($first2Chars === '--' || strpos($query, '#') === 0) {
            return false;
        }

        if ($first2Chars === '/*') {
            return false;
        }

        if (stripos($query, 'start transaction;') === 0) {
            return false;
        }

        if (stripos($query, 'commit;') === 0) {
            return false;
        }

        // All queries must terminate in ;
        // This string is assumed to be trimmed.
        if (substr($query, -strlen(1)) !== ';') {
            /*
             * Possibly fgets returned a truncated string?
             * @link https://wiki.sei.cmu.edu/confluence/pages/viewpage.action?pageId=87152445
             */
            $this->logger->debug('Skipping query because it does not end with a semi-colon... The query was logged.');
            debug_log($query);

            return false;
        }

        return true;
    }

    private function exec($query)
    {
        $result = $this->client->query($query, true);

        return $result !== false;
    }

    /**
     * Replace table collations
     *
     * @param string $input SQL statement
     */
    private function replaceTableCollations(&$input)
    {
        static $search  = [];
        static $replace = [];

        // Replace table collations
        if (empty($search) || empty($replace)) {
            if (!$this->wpdb->getClient()->has_cap('utf8mb4_520')) {
                if (!$this->wpdb->getClient()->has_cap('utf8mb4')) {
                    $search  = ['utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci', 'utf8mb4'];
                    $replace = ['utf8_unicode_ci', 'utf8_unicode_ci', 'utf8'];
                } else {
                    $search  = ['utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci'];
                    $replace = ['utf8mb4_unicode_ci', 'utf8mb4_unicode_ci'];
                }
            } else {
                $search  = ['utf8mb4_0900_ai_ci'];
                $replace = ['utf8mb4_unicode_520_ci'];
            }
        }

        $input = str_replace($search, $replace, $input);
    }

    /**
     *
     * A filterable method to exclude a particular INSERT query from the backup file.
     * $excludedQueries should not contain an escaped double quote or it will not match $query
     *
     * You don't need to write the entire query. It's sufficient if the beginning of a string matches $query
     *
     * E.g.
     * $excludedQueries = [
     * "INSERT INTO `wpstgtmp_options` VALUES ('6883792','wpstg_backup_process_locked'",
     * "INSERT INTO `wpstgtmp_actionscheduler_actions` VALUES ('13514','wc-admin_import_orders','failed','2021-03-16 15:09:35','2021-03-16 16:09:35','[88182]','O:30:"
     * ];
     *
     * @param $query
     * @return bool
     */
    private function isExcludedInsertQuery($query)
    {
        $excludedQueries = apply_filters('wpstg.database.import.excludedQueries', []);

        if (empty($excludedQueries)) {
            return false;
        }

        foreach ($excludedQueries as $excludedQuery) {
            if (strpos($query, $excludedQuery) === 0) {
                return true;
            }
        }

        return false;
    }
}
