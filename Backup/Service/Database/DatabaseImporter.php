<?php
namespace WPStaging\Backup\Service\Database;
use WPStaging\Backup\Dto\Service\DatabaseImporterDto;
use WPStaging\Backup\Service\Database\Importer\Insert\QueryInserter;
use WPStaging\Backup\Service\Database\Importer\QueryCompatibility;
use WPStaging\Backup\Service\Database\Importer\SubsiteManagerInterface;
use WPStaging\Framework\Adapter\DatabaseInterface;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Traits\ApplyFiltersTrait;
use WPStaging\Framework\Traits\DebugLogTrait;
use WPStaging\Framework\Traits\SerializeTrait;
class DatabaseImporter
{
    use DebugLogTrait;
    use ApplyFiltersTrait;
    use SerializeTrait;
    const THRESHOLD_EXCEPTION_CODE = 2001;
    const FINISHED_QUEUE_EXCEPTION_CODE = 2002;
    const RETRY_EXCEPTION_CODE = 2003;
    const FILE_FORMAT = 'sql';
    const TMP_DATABASE_PREFIX = 'wpstgtmp_';
    const TMP_DATABASE_PREFIX_TO_DROP = 'wpstgbak_';
    const NULL_FLAG = "{WPSTG_NULL}";
    const BINARY_FLAG = "{WPSTG_BINARY}";
    private $file;
    private $totalLines;
    private $client;
    private $databaseImporterDto;
    private $database;
    private $warningLogCallable;
    private $searchReplace;
    private $searchReplaceForPrefix;
    private $tmpDatabasePrefix;
    private $queryInserter;
    private $smallerSearchLength;
    private $binaryFlagLength;
    private $queryCompatibility;
    private $isSameSiteBackupRestore = false;
    private $tablesExcludedFromSearchReplace = [];
    private $subsiteManager;
    private $backupDbVersion;

    public function __construct(
        DatabaseInterface $database,
        QueryInserter $queryInserter,
        QueryCompatibility $queryCompatibility,
        SubsiteManagerInterface $subsiteManager
    ) {
        $this->client             = $database->getClient();
        $this->database           = $database;
        $this->queryInserter      = $queryInserter;
        $this->queryCompatibility = $queryCompatibility;
        $this->subsiteManager     = $subsiteManager;
        $this->binaryFlagLength   = strlen(self::BINARY_FLAG);
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
            throw new \RuntimeException('Restore file is not set');
        }
        $this->file->seek($line);
        return $this;
    }

    public function init(string $tmpDatabasePrefix)
    {
        $this->tmpDatabasePrefix = $tmpDatabasePrefix;
        $this->databaseImporterDto->setTmpPrefix($this->tmpDatabasePrefix);
        $this->setupSearchReplaceForPrefix();
        if (!$this->file) {
            throw new \RuntimeException('Restore file is not set');
        }
        $this->exec("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
        if ($this->applyFilters('wpstg.backup.restore.innodbStrictModeOff', false) === true) {
            $this->exec("SET SESSION innodb_strict_mode=OFF");
        }
    }

    public function retryQuery()
    {
        $this->databaseImporterDto->setCurrentIndex($this->file->key() - 1);
        $this->queryInserter->commit();
    }

    public function updateIndex()
    {
        $this->databaseImporterDto->setCurrentIndex($this->file->key());
        $this->queryInserter->commit();
    }

    public function getCurrentOffset(): int
    {
        return (int)$this->file->ftell();
    }

    public function finish()
    {
        $this->databaseImporterDto->finish();
        $this->queryInserter->commit();
    }

    public function getQueryCompatibility(): QueryCompatibility
    {
        return $this->queryCompatibility;
    }

    public function isSupportPageCompression(): bool
    {
        static $hasCompression;
        if ($hasCompression !== null) {
            return $hasCompression;
        }
        if (!$this->isMariaDB()) {
            return false;
        }
        $query  = "SHOW GLOBAL STATUS WHERE Variable_name IN ('Innodb_have_lz4', 'Innodb_have_lzo', 'Innodb_have_lzma', 'Innodb_have_bzip2', 'Innodb_have_snappy');";
        $result = $this->client->query($query);
        if (! ($result instanceof \mysqli_result)) {
            return false;
        }
        while ($row = $result->fetch_assoc()) {
            if ($row['Value'] === 'ON') {
                $hasCompression = true;
                return true;
            }
        }
        $hasCompression = false;
        return false;
    }

    public function isMariaDB(): bool
    {
        return stripos($this->serverInfo(), 'MariaDB') !== false;
    }

    public function removePageCompression(&$query): bool
    {
        if (!strpos($query, 'PAGE_COMPRESSED') || !(stripos($query, "CREATE TABLE") == 0)) {
            return false;
        }
        if ($this->isSupportPageCompression()) {
            return false;
        }
        $query = preg_replace("@`?PAGE_COMPRESSED`?='?(ON|OFF|0|1)'?@", '', $query);
        if (strpos($query, 'PAGE_COMPRESSION_LEVEL') !== false) {
            $query = preg_replace("@`?PAGE_COMPRESSION_LEVEL`?='?\d+'?@", '', $query);
        }
        return true;
    }

    public function setup(DatabaseImporterDto $databaseImporterDto, bool $isSameSiteBackupRestore, string $backupDbVersion)
    {
        $this->databaseImporterDto     = $databaseImporterDto;
        $this->isSameSiteBackupRestore = $isSameSiteBackupRestore;
        $this->backupDbVersion         = $backupDbVersion;
        $this->queryInserter->setDbVersions($this->serverVersion(), $this->backupDbVersion);
        $this->queryInserter->initialize($this->client, $this->databaseImporterDto);
        $this->subsiteManager->initialize($this->databaseImporterDto);
    }

    public function setupNonWpTables(array $nonWpTables)
    {
        $this->tablesExcludedFromSearchReplace = $nonWpTables;
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

    public function setWarningLogCallable(callable $callable)
    {
        $this->warningLogCallable = $callable;
    }

    public function execute()
    {
        $query = $this->findExecutableQuery();
        if (!$query) {
            throw new \Exception("", self::FINISHED_QUEUE_EXCEPTION_CODE);
        }
        $query = $this->searchReplaceForPrefix->replace($query);
        $query = $this->maybeShorterTableNameForDropTableQuery($query);
        $query = $this->maybeShorterTableNameForCreateTableQuery($query);
        $query = $this->maybeFixReplaceTableConstraints($query);
        $this->replaceTableCollations($query);
        if (strpos($query, 'INSERT INTO') === 0) {
            if ($this->isExcludedInsertQuery($query)) {
                $this->debugLog('processQuery - This query has been skipped from inserting by using a custom filter: ' . $query);
                $this->logWarning(sprintf('The query has been skipped from inserting by using a custom filter: %s.', esc_html($query)));
                return false;
            }
            if ($this->subsiteManager->isTableFromDifferentSubsite($query)) {
                $this->subsiteManager->updateSubsiteId();
                throw new \Exception("", self::RETRY_EXCEPTION_CODE);
            }
            if (
                !$this->isSameSiteBackupRestore
                || (strpos($query, self::BINARY_FLAG) !== false)
                || (strpos($query, self::NULL_FLAG) !== false)
            ) {
                $this->searchReplaceInsertQuery($query);
            }
            try {
                $result = $this->queryInserter->processQuery($query);
            } catch (\Exception $e) {
                throw $e;
            }
            if ($result === null && $this->queryInserter->getLastError() !== false) {
                $this->logWarning($this->queryInserter->getLastError());
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
        $backupDbVersion  = $this->backupDbVersion;
        if ($result === false) {
            switch ($this->client->errno()) {
                case 1030:
                    $this->queryCompatibility->replaceTableEngineIfUnsupported($query);
                    $result = $this->exec($query);
                    if ($result) {
                        $this->logWarning('Engine changed to InnoDB, as it your MySQL server does not support MyISAM.');
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
                        $this->logWarning('Row format changed to DYNAMIC, as it would exceed the maximum length according to your MySQL settings. To not see this message anymore, please upgrade your MySQL version or increase the row format.');
                    }
                    if ($replaceUtf8Mb4 && $result) {
                        $this->logWarning('Encoding changed to UTF8 from UTF8MB4, as your current MySQL version max key length support is 767 bytes');
                    }
                    break;
                case 1214:
                    $this->queryCompatibility->removeFullTextIndexes($query);
                    $result = $this->exec($query);
                    if ($result) {
                        $this->logWarning('FULLTEXT removed from query, as your current MySQL version does not support it. To not see this message anymore, please upgrade your MySQL version.');
                    }
                    break;
                case 1226:
                    if (stripos($this->client->error(), 'max_queries_per_hour') !== false) {
                        throw new \RuntimeException('Your server has reached the maximum allowed queries per hour set by your admin or hosting provider. Please increase MySQL max_queries_per_hour_limit. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>');
                    } elseif (stripos($this->client->error(), 'max_updates_per_hour') !== false) {
                        throw new \RuntimeException('Your server has reached the maximum allowed updates per hour set by your admin or hosting provider. Please increase MySQL max_updates_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>');
                    } elseif (stripos($this->client->error(), 'max_connections_per_hour') !== false) {
                        throw new \RuntimeException('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_connections_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>');
                    } elseif (stripos($this->client->error(), 'max_user_connections') !== false) {
                        throw new \RuntimeException('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_user_connections. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>');
                    }
                    break;
                case 1118:
                    throw new \RuntimeException('Your server has reached the maximum row size of the table. Please refer to the documentation on how to fix it. <a href="https://wp-staging.com/docs/mysql-database-error-codes" target="_blank">Technical details</a>');
                case 1059:
                    $shortIdentifiers = $this->queryCompatibility->shortenKeyIdentifiers($query);
                    $result           = $this->exec($query);
                    if ($result) {
                        foreach ($shortIdentifiers as $shortIdentifier => $identifier) {
                            $this->logWarning(sprintf('Key identifier `%s` exceeds the characters limits, it is now shortened to `%s` to continue restoring.', $identifier, $shortIdentifier));
                        }
                    }
                    break;
                case 1064:
                    $tableName = $this->queryCompatibility->pageCompressionMySQL($query, $errorMsg);
                    if (!empty($tableName)) {
                        $result = $this->exec($query);
                    }
                    if (!empty($tableName) && $result) {
                        $this->logWarning(sprintf('PAGE_COMPRESSED removed from Table: %s, as it is not a supported syntax in MySQL.', $tableName));
                    }
                    break;
                case 1813:
                    throw new \RuntimeException('Could not restore the database. MySQL returned the error code 1813, which is related to a tablespace error that WP STAGING can\'t handle. Please contact your hosting company.');
                case 1273:
                    $tableCollation = $this->queryCompatibility->replaceCollation($query, $errorMsg);
                    $result = $this->exec($query);
                    if ($result) {
                        $this->logWarning(sprintf('"The collation of the table `%s` has been changed from `%s` to `%s`, as the collation `%s` is missing from current MySQL version. To prevent this warning in the future, please restore the backup on a database using the same MySQL version as the one used during the backup.', $tableCollation['tableName'], $tableCollation['collationBefore'], $tableCollation['collationAfter'], $tableCollation['collationBefore']));
                    }
                    break;
            }
            if ($result) {
                return true;
            }
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                $this->logWarning(sprintf('Database Restorer - Failed Query: %s', substr($query, 0, 1000)));
                $this->debugLog(sprintf('Database Restorer Failed Query: %s', substr($query, 0, 1000)));
                if (isset($this->client->isSQLite) && $this->client->isSQLite) {
                    $this->debugLog($errorMsg);
                }
            }
            $errorNo  = $this->client->errno();
            $errorMsg = $this->client->error();
            $additionalInfo = '';
            if ($backupDbVersion !== $currentDbVersion) {
                $additionalInfo = sprintf(' Your current MySQL version is %s. If this issue persists, try using the same MySQL version used to create this Backup (%s).', $currentDbVersion, $backupDbVersion);
            }
            throw new \RuntimeException(sprintf('Could not restore query. MySQL has returned the error code %d, with message "%s".', $errorNo, $errorMsg) . $additionalInfo);
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
            $tableName = $this->databaseImporterDto->addShortNameTable($tableName, $this->tmpDatabasePrefix);
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
            $shortName = $this->databaseImporterDto->getShortNameTable($tableName, $this->tmpDatabasePrefix);
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
            $query = $this->replaceLastMatch("`);", "`) );", $query);
        }
        return $query;
    }

    public function searchReplaceInsertQuery(&$query)
    {
        if (!$this->searchReplace) {
            throw new \RuntimeException('SearchReplace not set');
        }
        $querySize = strlen($query);
        if ($querySize > ini_get('pcre.backtrack_limit')) {
            $this->logWarning(
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
            $this->debugLog($query);
            throw new \OutOfBoundsException('Skipping insert query. The query was logged....');
        }
        $tableName = $insertIntoExploded[1];
        if (strlen($tableName) > 64) {
            $tableName = $this->databaseImporterDto->getShortNameTable($tableName, $this->tmpDatabasePrefix);
        }
        $values = $insertIntoExploded[2];
        preg_match_all("#'(?:[^'\\\]++|\\\.)*+'#s", $values, $valueMatches);
        if (count($valueMatches) !== 1) {
            throw new \RuntimeException('Value match in query does not match.');
        }
        $valueMatches = $valueMatches[0];
        $query = "INSERT INTO `$tableName` VALUES (";
        foreach ($valueMatches as $value) {
            if (empty($value) || $value === "''") {
                $query .= "'', ";
                continue;
            }
            if ($value === "'" . self::NULL_FLAG . "'") {
                $query .= "NULL, ";
                continue;
            }
            if ($this->smallerSearchLength > strlen($value) - 2) {
                $query .= "{$value}, ";
                continue;
            }
            $value = substr($value, 1, -1);
            if (strpos($value, self::BINARY_FLAG) === 0) {
                $query .= "UNHEX('" . substr($value, strlen(self::BINARY_FLAG)) . "'), ";
                continue;
            }
            if ($this->isSameSiteBackupRestore || !$this->shouldSearchReplace($query)) {
                $query .= "'{$value}', ";
                continue;
            }
            if ($this->isSerialized($value)) {
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

    protected function setupSearchReplaceForPrefix()
    {
        $this->searchReplaceForPrefix = new SearchReplace(['{WPSTG_TMP_PREFIX}', '{WPSTG_FINAL_PREFIX}'], [$this->tmpDatabasePrefix, $this->database->getPrefix()], true, []);
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

    public function isExecutableQuery($query = null)
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
        if (substr($query, -1) !== ';') {
            $this->logWarning(
                'Skipping query because it does not end with a semi-colon.',
                [
                    'method'     => __METHOD__,
                    'DbFileLine' => is_object($this->file) ? $this->file->key() : 0,
                    'DbQuery'    => $query,
                ]
            );
            $this->debugLog($query);
            return false;
        }
        return true;
    }

    private function exec($query)
    {
        $result = $this->client->query($query);
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
        $excludedQueries = $this->applyFilters('wpstg.database.import.excludedQueries', []);
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

    private function replaceLastMatch(string $needle, string $replace, string $haystack): string
    {
        $result = $haystack;
        $pos    = strrpos($haystack, $needle);
        if ($pos !== false) {
            $result = substr_replace($haystack, $replace, $pos, strlen($needle));
        }
        return $result;
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

    protected function logWarning(string $message, array $data = [])
    {
        $callable = $this->warningLogCallable;
        if (__NAMESPACE__ === 'WpstgRestorer') {
            if (empty($data) || !is_callable($callable)) {
                return;
            }
            $message = array_merge([
                'method'  => '', 'message' => $message
            ], $data);
        }
        $callable($message);
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
