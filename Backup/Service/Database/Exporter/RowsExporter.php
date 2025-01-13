<?php
namespace WPStaging\Backup\Service\Database\Exporter;
use Exception;
use UnexpectedValueException;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Traits\DatabaseSearchReplaceTrait;
use WPStaging\Framework\Traits\MySQLRowsGeneratorTrait;
use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use function WPStaging\functions\debug_log;
class RowsExporter extends AbstractExporter
{
    use MySQLRowsGeneratorTrait;
    use DatabaseSearchReplaceTrait;
    protected $tableIndex = 0;
    protected $tableRowsOffset = 0;
    protected $totalRowsExported;
    protected $totalRowsInCurrentTable;
    protected $tables = [];
    protected $prefixedValues = [];
    protected $queriesToInsert = '';
    protected $jobDataDto;
    protected $logger;
    protected $writeToFileCount = 0;
    protected $searchReplaceForPrefix;
    protected $isRetryingAfterRepair = false;
    protected $tableName;
    protected $databaseName;
    protected $maxSplitSize;
    protected $isBackupSplit = false;
    protected $exceedSplitSize = false;
    protected $unInsertedSqlSize = 0;
    protected $lastNumericInsertId = -PHP_INT_MAX;
    protected $specialFields;
    protected $nonWpTables;

    public function __construct(Database $database, JobDataDto $jobDataDto)
    {
        parent::__construct($database);
        $this->jobDataDto = $jobDataDto;
        $this->specialFields = ['user_roles', 'capabilities', 'user_level', 'dashboard_quick_press_last_post_id', 'user-settings', 'user-settings-time'];
        $this->databaseName = $this->database->getWpdba()->getClient()->__get('dbname');
    }

    public function getTableRowsOffset(): int
    {
        return $this->tableRowsOffset;
    }

    public function setTableRowsOffset(int $tableRowsOffset)
    {
        $this->tableRowsOffset = $tableRowsOffset;
    }

    public function getTableIndex(): int
    {
        return $this->tableIndex;
    }

    public function isTableExcluded(): bool
    {
        return in_array($this->getTableBeingBackup(), $this->excludedTables);
    }

    public function setTableIndex(int $tableIndex)
    {
        $this->tableIndex = $tableIndex;
        if (!array_key_exists($this->tableIndex, $this->tables)) {
            throw new \RuntimeException('Table not found.');
        }
        $this->tableName = $this->tables[$this->tableIndex];
    }

    public function setTotalRowsExported(int $totalRowsExported)
    {
        $this->totalRowsExported = $totalRowsExported;
    }

    public function setTotalRowsInCurrentTable(int $totalRows)
    {
        $this->totalRowsInCurrentTable = $totalRows;
    }

    public function getTotalRowsExported(): int
    {
        return $this->totalRowsExported;
    }

    public function getTableBeingBackup(): string
    {
        return array_key_exists($this->tableIndex, $this->tables) ? $this->tables[$this->tableIndex] : '';
    }

    public function setIsBackupSplit(bool $isBackupSplit)
    {
        $this->isBackupSplit = $isBackupSplit;
    }

    public function setMaxSplitSize(int $maxSplitSize)
    {
        $this->maxSplitSize = $maxSplitSize;
    }

    public function doExceedSplitSize(): bool
    {
        return $this->exceedSplitSize;
    }

    public function countTotalRows(): int
    {
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
                case 144: case 145: if ($this->client->query("REPAIR TABLE `$this->tableName`;")) {
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
        $total = $this->client->fetchObject($result);
        if (isset($total->totalRows)) {
            return (int)$total->totalRows;
        }
        return 0;
    }

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

    protected function setupBackupSearchReplace()
    {
        $this->searchReplaceForPrefix = new SearchReplace([$this->getPrefix()], ['{WPSTG_FINAL_PREFIX}'], true, []);
    }

    public function backup($jobId, LoggerInterface $logger)
    {
        $this->client->query("SET SESSION sql_mode = ''");
        $this->logger = $logger;
        $isMultisiteBackup = is_multisite() && !$this->isNetworkSiteBackup;
        $prefixedTableName = $this->tableName;
        if (!in_array($this->tableName, $this->nonWpTables)) {
            $prefixedTableName = $isMultisiteBackup ? $this->getPrefixedBaseTableName($this->tableName) : $this->getPrefixedTableName($this->tableName);
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
                if ($this->writeToFileCount >= 10) {
                    $this->file->fwrite($this->queriesToInsert);
                    $this->queriesToInsert = '';
                    $this->updateOffset($numericPrimaryKey ?? '', $this->writeToFileCount);
                    $this->writeToFileCount = 0;
                }
            }
            if (!empty($this->queriesToInsert) && $this->useMemoryExhaustFix) {
                $this->file->fwrite($this->queriesToInsert);
                $this->queriesToInsert = '';
                $this->updateOffset($numericPrimaryKey ?? '', $this->writeToFileCount);
                $this->writeToFileCount = 0;
            }
            $rowsLeftToProcess = max($this->totalRowsInCurrentTable - $this->totalRowsExported, 0);
        } while (!$this->isThreshold() && $rowsLeftToProcess > 0);
        if (!empty($this->queriesToInsert)) {
            $this->file->fwrite($this->queriesToInsert);
            $this->queriesToInsert = '';
            $this->updateOffset($numericPrimaryKey ?? '', $this->writeToFileCount);
        }
        return $rowsLeftToProcess;
    }

    public function lockTable()
    {
        if (!$result = $this->client->query("LOCK TABLES `$this->tableName` WRITE;")) {
            debug_log("Backup: Could not lock table $this->tableName");
        }
        return $result;
    }

    public function unLockTables()
    {
        if (!$result = $this->client->query("UNLOCK TABLES;")) {
            debug_log("WP STAGING: Could not unlock tables after locking tables $this->tableName");
        }
        return $result;
    }

    public function setTables(array $tables = [])
    {
        $this->tables = $tables;
    }

    public function setNonWpTables(array $nonWpTables = [])
    {
        $this->nonWpTables = $nonWpTables;
    }

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

    public function prefixSpecialFields()
    {
        $prefix = $this->getPrefix();
        $this->prefixedValues = array_flip(array_map(function ($unprefixedValue) use ($prefix) {
            return $prefix . $unprefixedValue;
        }, $this->specialFields));
    }

    protected function getPrefix(): string
    {
        return $this->database->getBasePrefix();
    }

    protected function isOtherSitePrefixValue(string $value): bool
    {
        return false;
    }

    protected function writeQueryInsert(array $row, string $prefixedTableName, array $tableColumns)
    {
        try {
            foreach ($row as $column => &$value) {
                if (is_null($value)) {
                    $nullFlag = DatabaseImporter::NULL_FLAG;
                    $value    = "'$nullFlag'";
                    continue;
                }
                $columnLower = strtolower($column);
                if (
                    isset($tableColumns[$columnLower]) && (
                    strpos($tableColumns[$columnLower], 'binary') !== false ||
                    strpos($tableColumns[$columnLower], 'blob') !== false )
                ) {
                    $value      = bin2hex($value);
                    $binaryFlag = DatabaseImporter::BINARY_FLAG;
                    $value      = "'$binaryFlag$value'";
                    continue;
                }
                if ($prefixedTableName === '{WPSTG_TMP_PREFIX}options' && $column === 'option_name') {
                    if (substr($value, 0, 1) === '_') {
                        foreach (['_transient_', '_site_transient_', '_wc_session_'] as $excludedOption) {
                            if (strpos($value, $excludedOption) === 0) {
                                throw new \OutOfBoundsException();
                            }
                        }
                    }
                    if (substr($value, 0, 22) === 'wpstg_analytics_event_') {
                        throw new \OutOfBoundsException();
                    }
                    if (isset($this->prefixedValues[$value])) {
                        $value = $this->searchReplaceForPrefix->replace($value);
                    }
                }
                if ($prefixedTableName === '{WPSTG_TMP_PREFIX}usermeta' && $column === 'meta_key') {
                    if ($this->isOtherSitePrefixValue($value)) {
                        throw new \OutOfBoundsException();
                    }
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
            }
    }

    protected function throwUnableToCountException()
    {
        throw new \RuntimeException(sprintf(
            'We could not count the rows of a given table. Table: %s MySQL Error No: %s MySQL Error: %s',
            $this->tableName,
            $this->client->errno(),
            $this->client->error()
        ));
    }

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
