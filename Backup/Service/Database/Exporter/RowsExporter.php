<?php

namespace WPStaging\Backup\Service\Database\Exporter;

use Exception;
use UnexpectedValueException;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Traits\DatabaseSearchReplaceTrait;
use WPStaging\Framework\Traits\MySQLRowsGeneratorTrait;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

class RowsExporter extends AbstractExporter
{
    use MySQLRowsGeneratorTrait;
    use DatabaseSearchReplaceTrait;

    /**
     * Current table
     * @var int
     */
    protected $tableIndex = 0;

    /**
     * @var int How many rows were backup on this current table
     */
    protected $tableRowsOffset = 0;

    /**
     * @var int How many rows were backup across all tables
     */
    protected $totalRowsExported;

    /**
     * @var int How many rows the current table has to back up
     */
    protected $totalRowsInCurrentTable;

    /** @var array */
    protected $tables = [];

    /** @var array */
    protected $prefixedValues = [];

    /** @var string A concatenated series of queries to store to the file */
    protected $queriesToInsert = '';

    /** @var JobBackupDataDto */
    protected $jobDataDto;

    /** @var LoggerInterface */
    protected $logger;

    /** @var int */
    protected $writeToFileCount = 0;

    /** @var SearchReplace */
    protected $searchReplaceForPrefix;

    /** @var bool */
    protected $isRetryingAfterRepair = false;

    /** @var string The name of the table being backup. */
    protected $tableName;

    /** @var string The name of the database being backup. */
    protected $databaseName;

    /** @var int */
    protected $maxSplitSize;

    /** @var bool */
    protected $isBackupSplit = false;

    /** @var bool */
    protected $exceedSplitSize = false;

    /** @var int */
    protected $unInsertedSqlSize = 0;

    /** @var int */
    protected $lastNumericInsertId = -PHP_INT_MAX;

    /** @var array Fields/Option name which need special care for search replace */
    protected $specialFields;

    /** @var array */
    protected $nonWpTables;

    /**
     * All values must be wrapped into single quotes due to
     * \WPStaging\Backup\Service\Database\DatabaseImporter::searchReplaceInsertQuery
     * So we use a special flag to null values, that will be replaced with
     * actual NULL during restore. All other types we leave to MySQL type-juggling.
     * @var string
     */
    const NULL_FLAG = "{WPSTG_NULL}";

    /**
     * This flag indicates that this value has a binary data type in MySQL, such
     * as binary, blob, longblob, etc.
     * @var string
     */
    const BINARY_FLAG = "{WPSTG_BINARY}";

    /**
     * @param Database $database
     * @param JobDataDto $jobDataDto
     */
    public function __construct(Database $database, JobDataDto $jobDataDto)
    {
        parent::__construct($database);

        // @phpstan-ignore-next-line
        $this->jobDataDto = $jobDataDto;

        $this->specialFields = ['user_roles', 'capabilities', 'user_level', 'dashboard_quick_press_last_post_id', 'user-settings', 'user-settings-time'];

        $this->prefixSpecialFields();

        $this->databaseName = $this->database->getWpdba()->getClient()->__get('dbname');
    }

    /**
     * @return int
     */
    public function getTableRowsOffset(): int
    {
        return $this->tableRowsOffset;
    }

    /**
     * @param int $tableRowsOffset
     */
    public function setTableRowsOffset(int $tableRowsOffset)
    {
        $this->tableRowsOffset = $tableRowsOffset;
    }

    /**
     * @return int
     */
    public function getTableIndex(): int
    {
        return $this->tableIndex;
    }

    /**
     * @return bool
     */
    public function isTableExcluded(): bool
    {
        return in_array($this->getTableBeingBackup(), $this->excludedTables);
    }

    /**
     * @param int $tableIndex
     */
    public function setTableIndex(int $tableIndex)
    {
        $this->tableIndex = $tableIndex;

        if (!array_key_exists($this->tableIndex, $this->tables)) {
            throw new \RuntimeException('Table not found.');
        }

        $this->tableName = $this->tables[$this->tableIndex];
    }

    /**
     * @param int $totalRowsExported
     */
    public function setTotalRowsExported(int $totalRowsExported)
    {
        $this->totalRowsExported = $totalRowsExported;
    }

    /**
     * @param int $totalRows
     */
    public function setTotalRowsInCurrentTable(int $totalRows)
    {
        $this->totalRowsInCurrentTable = $totalRows;
    }

    /**
     * @return int
     */
    public function getTotalRowsExported(): int
    {
        return $this->totalRowsExported;
    }

    /**
     * @return string empty if no table is being backup
     */
    public function getTableBeingBackup(): string
    {
        return array_key_exists($this->tableIndex, $this->tables) ? $this->tables[$this->tableIndex] : '';
    }

    /**
     * @param bool $isBackupSplit
     */
    public function setIsBackupSplit(bool $isBackupSplit)
    {
        $this->isBackupSplit = $isBackupSplit;
    }

    /**
     * @param int $maxSplitSize
     */
    public function setMaxSplitSize(int $maxSplitSize)
    {
        $this->maxSplitSize = $maxSplitSize;
    }

    /**
     * @return bool
     */
    public function doExceedSplitSize(): bool
    {
        return $this->exceedSplitSize;
    }

    /**
     * @return int
     */
    public function countTotalRows(): int
    {
        // Early bail: Table not found
        if (!array_key_exists($this->tableIndex, $this->tables)) {
            throw new \RuntimeException('Table not found.');
        }

        $query = "SELECT COUNT(*) as `totalRows` FROM `$this->tableName`";

        $this->initDbExclusionValues();
        if ($this->columnToExclude && $this->valuesToExclude) {
            $query .= " WHERE `{$this->columnToExclude}` NOT IN ({$this->valuesToExclude})";
        }

        $result = $this->client->query($query);

        if (!$result) {
            if ($this->isRetryingAfterRepair) {
                $this->throwUnableToCountException();
            }

            switch ($this->client->errno()) {
                case 144: // Table is crashed and last repair failed
                case 145: // Table was marked as crashed and should be repaired
                    if ($this->client->query("REPAIR TABLE `$this->tableName`;")) {
                        $this->logger->warning(sprintf("Table %s is marked as crashed, we automatically repaired it.", $this->tableName));
                    } else {
                        $this->logger->warning(sprintf("Table %s is marked as crashed, we automatically repaired it but failed.", $this->tableName));
                    }

                    $this->isRetryingAfterRepair = true;

                    return $this->countTotalRows();
                default:
                    $this->throwUnableToCountException();
            }
        }

        return (int)$result->fetch_object()->totalRows;
    }

    /**
     * @return void
     */
    public function setupPrefixedValuesForSubsites()
    {
        if (!is_multisite()) {
            return;
        }

        $basePrefix = $this->database->getBasePrefix();

        foreach ($this->subsites as $subsite) {
            $siteId = $subsite['blog_id'];
            if (empty($siteId) || $siteId === 1) {
                continue;
            }

            $prefix = $basePrefix . $siteId . "_";

            $prefixedValues = array_flip(array_map(function ($unprefixedValue) use ($prefix) {
                return $prefix . $unprefixedValue;
            }, $this->specialFields));

            $this->prefixedValues = array_merge($this->prefixedValues, $prefixedValues);
        }
    }

    /**
     * @return void
     * @see \WPStaging\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask::prepare For Restore Search/Replace.
     */
    protected function setupBackupSearchReplace()
    {
        $this->searchReplaceForPrefix = new SearchReplace([$this->getPrefix()], ['{WPSTG_FINAL_PREFIX}'], true, []);
    }

    /**
     * @return string|null|int
     * @throws Exception
     */
    public function backup($jobId, LoggerInterface $logger)
    {
        $this->client->query("SET SESSION sql_mode = ''");

        $this->logger = $logger;

        $prefixedTableName = $this->tableName;
        if (!in_array($this->tableName, $this->nonWpTables)) {
            $prefixedTableName = $this->getPrefixedTableName($this->tableName);
        }

        $tableColumns = $this->getColumnTypes($this->tableName);

        try {
            $numericPrimaryKey = $this->getPrimaryKey();
        } catch (Exception $e) {
            $numericPrimaryKey = null;
            if ($this->jobDataDto->getTableRowsOffset() === 0 && $this->jobDataDto->getTotalRowsOfTableBeingBackup() > 300000) {
                $logger->info("The table $this->tableName does not have a compatible primary key, so it will get slower the more rows it backup...");
            }
        }

        $this->setupBackupSearchReplace();

        do {
            if ($this->useMemoryExhaustFix) {
                $this->tableRowsOffset     = $this->jobDataDto->getTableRowsOffset();
                $this->lastNumericInsertId = (int)$this->jobDataDto->getLastInsertId();
            }

            $data = $this->rowsGenerator($this->databaseName, $this->tableName, $numericPrimaryKey, $this->tableRowsOffset, "rowsExporter_$jobId", $this->client, $this->jobDataDto);

            foreach ($data as $row) {
                if ($this->updateLastNumericInsertId($numericPrimaryKey ?? '', $row)) {
                    continue;
                }

                $this->writeQueryInsert($row, $prefixedTableName, $tableColumns);

                if ($this->useMemoryExhaustFix) {
                    $this->writeToFileCount++;
                }

                if ($this->exceedSplitSize) {
                    if (!empty($this->queriesToInsert)) {
                        $this->file->fwrite($this->queriesToInsert);
                        $this->queriesToInsert   = '';
                        $this->writeToFileCount  = 0;
                        $this->unInsertedSqlSize = 0;
                    }

                    return max($this->totalRowsInCurrentTable - $this->totalRowsExported, 0);
                }

                if (!$this->useMemoryExhaustFix) {
                    $this->writeToFileCount++;
                    $this->totalRowsExported++;

                    if (!empty($numericPrimaryKey)) {
                        $this->tableRowsOffset = $row[$numericPrimaryKey];
                    } else {
                        $this->tableRowsOffset++;
                    }
                }

                /*
                 * This can run hundreds of thousands of times,
                 * so let's write sometimes.
                 */
                if ($this->writeToFileCount >= 10) {
                    $this->file->fwrite($this->queriesToInsert);
                    $this->queriesToInsert  = '';
                    $this->updateOffset($numericPrimaryKey ?? '', $this->writeToFileCount);
                    $this->writeToFileCount = 0;
                }
            }

            if (!empty($this->queriesToInsert) && $this->useMemoryExhaustFix) {
                $this->file->fwrite($this->queriesToInsert);
                $this->queriesToInsert  = '';
                $this->updateOffset($numericPrimaryKey ?? '', $this->writeToFileCount);
                $this->writeToFileCount = 0;
            }

            $rowsLeftToProcess = max($this->totalRowsInCurrentTable - $this->totalRowsExported, 0);
        } while (!$this->isThreshold() && $rowsLeftToProcess > 0);

        // Commit to file any leftover queries left to write
        if (!empty($this->queriesToInsert)) {
            $this->file->fwrite($this->queriesToInsert);
            $this->queriesToInsert = '';
            $this->updateOffset($numericPrimaryKey ?? '', $this->writeToFileCount);
        }

        return $rowsLeftToProcess;
    }

    /**
     * @return bool|\mysqli_result|resource False if it couldn't lock.
     */
    public function lockTable()
    {
        if (!$result = $this->client->query("LOCK TABLES `$this->tableName` WRITE;")) {
            debug_log("Backup: Could not lock table $this->tableName");
        }

        return $result;
    }

    /**
     * @return bool|\mysqli_result|resource False if it couldn't lock.
     */
    public function unLockTables()
    {
        if (!$result = $this->client->query("UNLOCK TABLES;")) {
            debug_log("WP STAGING: Could not unlock tables after locking tables $this->tableName");
        }

        return $result;
    }

    /**  @param array $tables */
    public function setTables(array $tables = [])
    {
        $this->tables = $tables;
    }

    /** @var array $nonWpTables */
    public function setNonWpTables(array $nonWpTables = [])
    {
        $this->nonWpTables = $nonWpTables;
    }

    /**
     * @return string The primary key of the current table, if any.
     */
    public function getPrimaryKey(): string
    {
        if ($this->hasMoreThanOnePrimaryKey()) {
            throw new UnexpectedValueException();
        }

        $query = "SELECT COLUMN_NAME 
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = '$this->tableName'
                  AND TABLE_SCHEMA = '$this->databaseName'
                  AND IS_NULLABLE = 'NO'
                  AND DATA_TYPE IN ('int', 'bigint', 'smallint', 'mediumint')
                  AND COLUMN_KEY = 'PRI'
                  AND EXTRA like '%auto_increment%';";

        $result = $this->client->query($query);

        if (!$result) {
            throw new UnexpectedValueException();
        }

        $primaryKey = $this->client->fetchObject($result);

        $this->client->freeResult($result);

        if (!is_object($primaryKey)) {
            throw new UnexpectedValueException();
        }

        if (!property_exists($primaryKey, 'COLUMN_NAME')) {
            throw new UnexpectedValueException();
        }

        if (empty($primaryKey->COLUMN_NAME)) {
            throw new UnexpectedValueException();
        }

        return $primaryKey->COLUMN_NAME;
    }

    /**
     * @return void
     */
    protected function prefixSpecialFields()
    {
        $prefix = $this->getPrefix();

        /**
         * We do an array_flip for performance reasons, to use the performant
         * isset($this->prefixedValues['someValue']),
         * since this function can be called millions of times.
         */
        $this->prefixedValues = array_flip(array_map(function ($unprefixedValue) use ($prefix) {
            return $prefix . $unprefixedValue;
        }, $this->specialFields));
    }

    /**
     * @return string
     */
    protected function getPrefix(): string
    {
        return $this->database->getBasePrefix();
    }

    /**
     * Used in the Pro version
     * @param string $value
     * @return bool
     */
    protected function isOtherSitePrefixValue(string $value): bool
    {
        return false;
    }

    /**
     * @param array $row
     * @param string $prefixedTableName
     * @param array $tableColumns
     *
     * @return void
     * @throws Exception
     */
    protected function writeQueryInsert(array $row, string $prefixedTableName, array $tableColumns)
    {
        try {
            foreach ($row as $column => &$value) {
                if (is_null($value)) {
                    $nullFlag = self::NULL_FLAG;
                    $value    = "'$nullFlag'";
                    continue;
                }

                // binary, varbinary, blob
                if (
                    strpos($tableColumns[strtolower($column)], 'binary') !== false ||
                    strpos($tableColumns[strtolower($column)], 'blob') !== false
                ) {
                    $value      = bin2hex($value);
                    $binaryFlag = static::BINARY_FLAG;
                    $value      = "'$binaryFlag$value'";
                    continue;
                }

                if ($prefixedTableName === '{WPSTG_TMP_PREFIX}options' && $column === 'option_name') {
                    // Rows not to back up
                    if (substr($value, 0, 1) === '_') {
                        foreach (['_transient_', '_site_transient_', '_wc_session_'] as $excludedOption) {
                            if (strpos($value, $excludedOption) === 0) {
                                throw new \OutOfBoundsException();
                            }
                        }
                    }

                    // Don't include analytics events in the backup
                    if (substr($value, 0, 22) === 'wpstg_analytics_event_') {
                        throw new \OutOfBoundsException();
                    }

                    // Rows that need special prefix
                    if (isset($this->prefixedValues[$value])) {
                        $value = $this->searchReplaceForPrefix->replace($value);
                    }
                }

                if ($prefixedTableName === '{WPSTG_TMP_PREFIX}usermeta' && $column === 'meta_key') {
                    // Rows not to back up
                    if ($this->isOtherSitePrefixValue($value)) {
                        throw new \OutOfBoundsException();
                    }

                    // Rows that need special prefix
                    if (isset($this->prefixedValues[$value])) {
                        $value = $this->searchReplaceForPrefix->replace($value);
                    }
                }

                $value = "'{$this->client->escape($value)}'";
            }

            $this->exceedSplitSize = false;

            static $isFirstInsert = false;
            $insertSeparator      = '';
            if ($isFirstInsert === false) {
                $isFirstInsert   = true;
                $insertSeparator = "--\n-- SQL DATA\n--\n";
            }

            $insertQuery = "{$insertSeparator}INSERT INTO `{$prefixedTableName}` VALUES (" . implode(',', $row) . ");\n";
            if (!$this->isBackupSplit) {
                $this->queriesToInsert .= $insertQuery;
                return;
            }

            $this->unInsertedSqlSize += strlen($insertQuery);

            if (($this->unInsertedSqlSize + $this->file->getSize()) > $this->maxSplitSize) {
                $this->exceedSplitSize = true;
                return;
            }

            $this->queriesToInsert .= $insertQuery;
        } catch (Exception $e) {
            // Row skipped, no-op
        }
    }

    /**
     * @return void
     * @throws \RuntimeException
     */
    protected function throwUnableToCountException()
    {
        throw new \RuntimeException(sprintf(
            'We could not count the rows of a given table. Table: %s MySQL Error No: %s MySQL Error: %s',
            $this->tableName,
            $this->client->errno(),
            $this->client->error()
        ));
    }

    /**
     * @param string $numericPrimaryKey
     * @param int $filesWritten
     * @return void
     */
    protected function updateOffset(string $numericPrimaryKey, int $filesWritten)
    {
        if (!$this->useMemoryExhaustFix) {
            return;
        }

        $this->totalRowsExported = $this->jobDataDto->getTotalRowsBackup() + $filesWritten;
        $this->jobDataDto->setTotalRowsBackup($this->totalRowsExported);

        if (!empty($numericPrimaryKey)) {
            $this->tableRowsOffset = $this->lastNumericInsertId;
            $this->jobDataDto->setTableRowsOffset($this->lastNumericInsertId);
            $this->jobDataDto->setLastInsertId($this->lastNumericInsertId);
            return;
        }

        $this->tableRowsOffset = $this->jobDataDto->getTableRowsOffset() + $filesWritten;
        $this->jobDataDto->setTableRowsOffset($this->jobDataDto->getTableRowsOffset() + $filesWritten);
        $this->jobDataDto->setLastInsertId($this->lastNumericInsertId);
    }

    /**
     * @param string $tableName Table name
     *
     * @return array
     */
    private function getColumnTypes(string $tableName): array
    {
        $column_types = [];

        $result = $this->client->query("SHOW COLUMNS FROM `{$tableName}`");
        while ($row = $this->client->fetchAssoc($result)) {
            if (isset($row['Field'])) {
                $column_types[strtolower($row['Field'])] = strtolower($row['Type']);
            }
        }

        $this->client->freeResult($result);

        return $column_types;
    }

    /**
     * @return bool
     *
     * @throws UnexpectedValueException
     */
    private function hasMoreThanOnePrimaryKey(): bool
    {
        $query = "SHOW KEYS FROM $this->tableName WHERE Key_name = 'PRIMARY'";

        $result = $this->client->query($query);

        if (!$result) {
            throw new UnexpectedValueException();
        }

        $primaryKeys = $this->client->fetchAll($result);

        return count($primaryKeys) > 1;
    }

    /**
     * @param string $numericPrimaryKey
     * @param array $row
     * @return bool
     */
    private function updateLastNumericInsertId(string $numericPrimaryKey, array $row): bool
    {
        if (!$this->useMemoryExhaustFix) {
            return false;
        }

        if (!empty($numericPrimaryKey)) {
            $lastInsertId = (int)$row[$numericPrimaryKey];

            if ($lastInsertId <= $this->lastNumericInsertId) {
                return true;
            }

            $this->lastNumericInsertId = $lastInsertId;
        }

        return false;
    }
}
