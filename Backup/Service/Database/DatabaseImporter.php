<?php
namespace WPStaging\Backup\Service\Database;
use RuntimeException;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Service\Database\Exporter\RowsExporter;
use WPStaging\Backup\Service\Database\Importer\Insert\QueryInserter;
use WPStaging\Backup\Service\Database\Importer\QueryCompatibility;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Backup\Service\Database\Importer\SubsiteManagerInterface;
use WPStaging\Framework\Adapter\DatabaseInterface;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Exceptions\RetryException;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Exception\ThresholdException;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use function WPStaging\functions\debug_log;
class DatabaseImporter
{
    use ResourceTrait;
    const FILE_FORMAT = 'sql';
    private $file;
    private $totalLines;
    private $client;
    private $database;
    private $logger;
    private $stepsDto;
    private $searchReplace;
    private $searchReplaceForPrefix;
    private $tmpDatabasePrefix;
    private $jobRestoreDataDto;
    private $queryInserter;
    private $smallerSearchLength;
    private $binaryFlagLength;
    private $queryCompatibility;
    private $restoreTask;
    private $getIsSameSiteBackupRestore = false;
    private $tablesExcludedFromSearchReplace = [];
    private $subsiteManager;
    protected $stringsUtil;
    public function __construct(
        DatabaseInterface $database,
        JobDataDto $jobRestoreDataDto,
        QueryInserter $queryInserter,
        QueryCompatibility $queryCompatibility,
        SubsiteManagerInterface $subsiteManager,
        Strings $stringsUtil
    ) {
        $this->client   = $database->getClient();
        $this->database = $database;
        $this->jobRestoreDataDto  = $jobRestoreDataDto;
        $this->queryInserter      = $queryInserter;
        $this->queryCompatibility = $queryCompatibility;
        $this->subsiteManager     = $subsiteManager;
        $this->binaryFlagLength   = strlen(RowsExporter::BINARY_FLAG);
        $this->stringsUtil        = $stringsUtil;
    }
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
        $this->exec("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
        if (apply_filters('wpstg.backup.restore.innodbStrictModeOff', false) === true) {
            $this->exec("SET SESSION innodb_strict_mode=OFF");
        }
        try {
            while (true) {
                try {
                    $this->execute();
                } catch (\OutOfBoundsException $e) {
                    $this->logger->debug($e->getMessage());
                }
            }
        } catch (FinishedQueueException $e) {
            $this->stepsDto->finish();
        } catch (ThresholdException $e) {
            } catch (RetryException $e) {
            $this->stepsDto->setCurrent($this->file->key() - 1);
            $this->queryInserter->commit();
            return;
        } catch (\Exception $e) {
            $this->stepsDto->setCurrent($this->file->key());
            $this->logger->critical(substr($e->getMessage(), 0, 1000));
        }
        $this->queryInserter->commit();
        $this->stepsDto->setCurrent($this->file->key());
    }
    protected function setupSearchReplaceForPrefix()
    {
        $this->searchReplaceForPrefix = new SearchReplace(['{WPSTG_TMP_PREFIX}', '{WPSTG_FINAL_PREFIX}'], [$this->tmpDatabasePrefix, $this->database->getPrefix()], true, []);
    }
    public function setup(LoggerInterface $logger, StepsDto $stepsDto, RestoreTask $task)
    {
        $this->logger      = $logger;
        $this->stepsDto    = $stepsDto;
        $this->restoreTask = $task;
        $this->getIsSameSiteBackupRestore = $this->jobRestoreDataDto->getIsSameSiteBackupRestore();
        $this->queryInserter->initialize($this->database, $this->jobRestoreDataDto, $logger);
        $this->restoreTask->setTmpPrefix($this->jobRestoreDataDto->getTmpDatabasePrefix());
        $this->tablesExcludedFromSearchReplace = $this->jobRestoreDataDto->getBackupMetadata()->getNonWpTables();
        $this->subsiteManager->initialize($this->jobRestoreDataDto);
        return $this;
    }
    public function setSearchReplace(SearchReplace $searchReplace)
    {
        $this->searchReplace = $searchReplace;
        $this->smallerSearchLength = min($searchReplace->getSmallerSearchLength(), $this->binaryFlagLength);
        return $this;
    }
    public function getTotalLines()
    {
        return $this->totalLines;
    }
    private function execute()
    {
        if ($this->isDatabaseRestoreThreshold()) {
            throw new ThresholdException();
        }
        $query = $this->findExecutableQuery();
        if (!$query) {
            throw new FinishedQueueException();
        }
        $query = $this->searchReplaceForPrefix->replace($query);
        $query = $this->maybeShorterTableNameForDropTableQuery($query);
        $query = $this->maybeShorterTableNameForCreateTableQuery($query);
        $query = $this->maybeFixReplaceTableConstraints($query);
        $this->replaceTableCollations($query);
        if (strpos($query, 'INSERT INTO') === 0) {
            if ($this->isExcludedInsertQuery($query)) {
                debug_log('processQuery - This query has been skipped from inserting by using a custom filter: ' . $query);
                $this->logger->warning(sprintf('The query has been skipped from inserting by using a custom filter: %s.', esc_html($query)));
                return false;
            }
            if ($this->subsiteManager->isTableFromDifferentSubsite($query)) {
                $this->subsiteManager->updateSubsiteId();
                throw new RetryException();
            }
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
                throw $e;
            }
            if ($result === null && $this->queryInserter->getLastError() !== false) {
                $this->logger->warning($this->queryInserter->getLastError());
            }
        } else {
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
    protected function maybeFixReplaceTableConstraints(&$query)
    {
        if (strpos($query, "CREATE TABLE") !== 0) {
            return $query;
        }
        if (preg_match('@KEY\s+\`.*\`\s+?\(.*\)(,(\s+)?\`.*`\)\s+ON\s+(DELETE|UPDATE).*?)\)@i', $query, $matches)) {
            $query = str_replace($matches[1], '', $query);
        }
        $patterns = [
            '/\s+CONSTRAINT(.+)REFERENCES(.+)(\s+)?,/i',
            '/,(\s+)?(KEY(.+))?CONSTRAINT(.+)REFERENCES(.+)\`\)(\s+)?\)/i',
        ];
        $replace = ['', ')'];
        $query = preg_replace($patterns, $replace, $query);
        if ($this->isCorruptedCreateTableQuery($query)) {
            $query = $this->stringsUtil->replaceLastMatch("`);", "`) );", $query);
        }
        return $query;
    }
    protected function searchReplaceInsertQuery(&$query)
    {
        if (!$this->searchReplace) {
            throw new RuntimeException('SearchReplace not set');
        }
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
            if ($value === "'" . RowsExporter::NULL_FLAG . "'") {
                $query .= "NULL, ";
                continue;
            }
            if ($this->smallerSearchLength > strlen($value) - 2) {
                $query .= "{$value}, ";
                continue;
            }
            $value = substr($value, 1, -1);
            if (strpos($value, RowsExporter::BINARY_FLAG) === 0) {
                $query .= "UNHEX('" . substr($value, strlen(RowsExporter::BINARY_FLAG)) . "'), ";
                continue;
            }
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
    private function isExecutableQuery($query = null)
    {
        if (!$query) {
            return false;
        }
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
        if (substr($query, -strlen(1)) !== ';') {
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
    private function replaceTableCollations(string &$input)
    {
        static $search  = [];
        static $replace = [];
        if (!empty($search) && !empty($replace)) {
            $input = str_replace($search, $replace, $input);
            return;
        }
        if ($this->hasCapabilities('utf8mb4_520')) {
            $search  = ['utf8mb4_0900_ai_ci'];
            $replace = ['utf8mb4_unicode_520_ci'];
            $input   = str_replace($search, $replace, $input);
            return;
        }
        if (!$this->hasCapabilities('utf8mb4')) {
            $search  = ['utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci', 'utf8mb4'];
            $replace = ['utf8_unicode_ci', 'utf8_unicode_ci', 'utf8'];
        } else {
            $search  = ['utf8mb4_0900_ai_ci', 'utf8mb4_unicode_520_ci'];
            $replace = ['utf8mb4_unicode_ci', 'utf8mb4_unicode_ci'];
        }
        $input = str_replace($search, $replace, $input);
    }
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
    protected function isCorruptedCreateTableQuery(string $query): bool
    {
        if (strpos($query, "ENGINE") !== false) {
            return false;
        }
        if (strpos($query, "CHARSET") !== false) {
            return false;
        }
        if (strpos($query, "COLLATE") !== false) {
            return false;
        }
        return true;
    }
    private function hasCapabilities(string $capabilities): bool
    {
        $serverVersion = $this->serverVersion();
        $serverInfo    = $this->serverInfo();
        if ($serverVersion === '5.5.5' && strpos($serverInfo, 'MariaDB') !== false && PHP_VERSION_ID < 80016) {
            $serverInfo    = preg_replace('@^5\.5\.5-(.*)@', '$1', $serverInfo);
            $serverVersion = preg_replace('@[^0-9.].*@', '', $serverInfo);
        }
        switch (strtolower($capabilities)) {
            case 'collation':
                return version_compare($serverVersion, '4.1', '>=');
            case 'set_charset':
                return version_compare($serverVersion, '5.0.7', '>=');
            case 'utf8mb4':
                if (version_compare($serverVersion, '5.5.3', '<')) {
                    return false;
                }
                $clienVersion = $this->clientInfo();
                if (false !== strpos($clienVersion, 'mysqlnd')) {
                    $clienVersion = preg_replace('@^\D+([\d.]+).*@', '$1', $clienVersion);
                    return version_compare($clienVersion, '5.0.9', '>=');
                } else {
                    return version_compare($clienVersion, '5.5.3', '>=');
                }
            case 'utf8mb4_520':
                return version_compare($serverVersion, '5.6', '>=');
        }
        return false;
    }
    private function clientInfo(): string
    {
        return !empty($this->client->getLink()->host_info) ? $this->client->getLink()->host_info : '';
    }
    private function serverInfo(): string
    {
        return !empty($this->client->getLink()->server_info) ? $this->client->getLink()->server_info : '';
    }
    private function serverVersion(): string
    {
        $serverInfo = $this->serverInfo();
        if (stripos($serverInfo, 'MariaDB') !== false && preg_match('@^([0-9\.]+)\-([0-9\.]+)\-MariaDB@i', $serverInfo, $match)) {
            return $match[2];
        }
        return preg_replace('@[^0-9\.].*@', '', $serverInfo);
    }
}
