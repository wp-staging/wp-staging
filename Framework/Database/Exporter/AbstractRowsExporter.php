<?php

namespace WPStaging\Framework\Database\Exporter;

use Exception;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Traits\DatabaseSearchReplaceTrait;
use WPStaging\Framework\Traits\MySQLRowsGeneratorTrait;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Job\Dto\Database\RowsExporterDto;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

use function WPStaging\functions\debug_log;

abstract class AbstractRowsExporter extends AbstractExporter
{
    use MySQLRowsGeneratorTrait;
    use DatabaseSearchReplaceTrait;

    /** @var JobDataDto */
    protected $jobDataDto;

    /** @var TableService */
    protected $tableService;

    /** @var LoggerInterface */
    protected $logger;

    /** @var RowsExporterDto */
    protected $rowsExporterDto;

    /** @var SearchReplace */
    protected $searchReplace;

    /**
     * Current table
     * @var int
     */
    protected $tableIndex = 0;

    /**
     * @var int How many rows were exported on this current table
     */
    protected $tableRowsOffset = 0;

    /**
     * @var int How many rows were exported across all tables
     */
    protected $totalRowsExported = 0;

    /**
     * @var int How many rows the current table has to back up
     */
    protected $totalRowsInCurrentTable = 0;

    /** @var array */
    protected $tables = [];

    /** @var array */
    protected $prefixedValues = [];

    /** @var string A concatenated series of queries to store to the file */
    protected $pendingQueriesSql = '';

    /** @var int */
    protected $pendingQueriesCount = 0;

    /** @var bool */
    protected $isRetryingAfterRepair = false;

    /** @var string The name of the table being exported. */
    protected $tableName = '';

    /** @var string The name of the database being exported. */
    protected $databaseName = '';

    /** @var int */
    protected $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

    /** @var array Fields/Option name which need special care for search replace */
    protected $specialFields = [];

    /** @var array */
    protected $nonWpTables = [];

    /**
     * @param Database $database
     * @param TableService $tableService
     */
    public function __construct(Database $database, TableService $tableService)
    {
        parent::__construct($database);

        $this->tableService  = $tableService;
        $this->specialFields = ['user_roles', 'capabilities', 'user_level', 'dashboard_quick_press_last_post_id', 'user-settings', 'user-settings-time'];
        $this->databaseName  = $this->database->getWpdba()->getClient()->__get('dbname');
    }

    public function inject(LoggerInterface $logger, JobDataDto $jobDataDto, RowsExporterDto $rowsExporterDto)
    {
        $this->logger          = $logger;
        $this->jobDataDto      = $jobDataDto;
        $this->rowsExporterDto = $rowsExporterDto;
        $this->tableIndex      = $this->rowsExporterDto->getTableIndex();
    }

    /**
     * @return bool
     */
    public function initiate(): bool
    {
        if ($this->isTableExcluded()) {
            $this->logger->info("Skipping table {$this->tableName} as it is excluded from data copying...");
            return false;
        }

        // Table rows already counted
        if ($this->rowsExporterDto->getTotalRows() > 0) {
            return true;
        }

        $this->rowsExporterDto->init($this->tableIndex, $this->tableName, 0);

        // Note: fix for SQLITE
        $tableName = empty($this->databaseName) ? "`" . $this->tableName . "`" : "`" . $this->databaseName . "`.`" . $this->tableName . "`";
        $rowsCount = $this->tableService->getRowsCount($tableName, false);

        if ($rowsCount === 0) {
            $this->logger->info("Found table {$this->tableName} with no rows to export...");
            return false;
        }

        $this->rowsExporterDto->setTotalRows($rowsCount);

        $numericPrimaryKey = null;
        try {
            $numericPrimaryKey = $this->tableService->getNumericPrimaryKey($this->databaseName, $this->tableName);
        } catch (Exception $e) {
            if ($rowsCount > 300000) {
                $this->logger->warning("The table {$this->tableName} does not have a compatible primary key, so it will get slower the more rows from it be exported and will be locked...");
            } else {
                $this->logger->warning("The table {$this->tableName} does not have a compatible primary key, so it will be locked...");
            }

            $this->rowsExporterDto->setLocked(true);
        }

        $this->rowsExporterDto->setNumericPrimaryKey($numericPrimaryKey);
        $this->logger->info("Found table {$this->tableName} with {$rowsCount} rows to export...");

        return true;
    }

    public function getRowsExporterDto(): RowsExporterDto
    {
        return $this->rowsExporterDto;
    }

    /**
     * @return bool
     */
    public function isTableExcluded(): bool
    {
        return in_array($this->getTableBeingExported(), $this->excludedTables);
    }

    /**
     * @param int $tableIndex
     */
    public function setTableIndex(int $tableIndex)
    {
        if ($this->tableIndex !== $tableIndex) {
            $this->rowsExporterDto->reset();
        }

        $this->tableIndex = $tableIndex;
        if (!array_key_exists($this->tableIndex, $this->tables)) {
            throw new \RuntimeException('Table not found.');
        }

        $this->tableName = $this->tables[$this->tableIndex];
    }

    /**
     * @return string empty if no table is being exported
     */
    public function getTableBeingExported(): string
    {
        return $this->tableName;
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

        $total = $this->client->fetchObject($result);
        if (isset($total->totalRows)) {
            return (int)$total->totalRows;
        }

        return 0;
    }

    /**
     * @return void
     */
    public function export()
    {
        $requestId         = "rowsExporter_" . $this->jobDataDto->getId();
        $finalTableName    = $this->getFinalTableName();
        $tableColumns      = $this->tableService->getColumnTypes($this->tableName);
        $numericPrimaryKey = $this->rowsExporterDto->getNumericPrimaryKey();

        $this->setupSearchReplace();

        if ($this->rowsExporterDto->isLocked()) {
            $this->tableService->lockTable($this->tableName);
        }

        do {
            $this->tableRowsOffset                    = $this->rowsExporterDto->getRowsOffset();
            $this->lastInsertedNumericPrimaryKeyValue = (int)$this->rowsExporterDto->getLastInsertedNumericPrimaryKeyValue();

            $data = $this->rowsGenerator($this->databaseName, $this->tableName, $numericPrimaryKey, $this->tableRowsOffset, $requestId, $this->client, $this->jobDataDto);

            foreach ($data as $row) {
                if ($this->isLastInsertedNumericKeyValue($numericPrimaryKey ?? '', $row)) {
                    continue;
                }

                $this->writeQueryInsert($row, $finalTableName, $tableColumns);
                $this->pendingQueriesCount++;

                /**
                 * This can run hundreds of thousands of times,
                 * so let's write sometimes.
                 */
                if ($this->pendingQueriesCount >= 10) {
                    $this->file->fwrite($this->pendingQueriesSql);
                    $this->pendingQueriesSql = '';
                    $this->updateRowsExporterDto($numericPrimaryKey ?? '', $this->pendingQueriesCount);
                    $this->pendingQueriesCount = 0;
                }
            }
        } while (!$this->isThreshold() && ($this->pendingQueriesCount + $this->rowsExporterDto->getRowsOffset() < $this->rowsExporterDto->getTotalRows()));

        // Commit to file any leftover queries left to write
        if (!empty($this->pendingQueriesSql)) {
            $this->file->fwrite($this->pendingQueriesSql);
            $this->pendingQueriesSql = '';
            $this->updateRowsExporterDto($numericPrimaryKey ?? '', $this->pendingQueriesCount);
            $this->pendingQueriesCount = 0;
        }

        $this->unlockTables();
    }

    /**
     * @return bool
     */
    public function lockTable()
    {
        try {
            $this->tableService->lockTable($this->tableName);
        } catch (Exception $ex) {
            debug_log("Could not lock table $this->tableName");
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function unlockTables(): bool
    {
        if (!$this->rowsExporterDto->isLocked()) {
            return true;
        }

        try {
            $this->tableService->unlockTables();
        } catch (Exception $ex) {
            debug_log("Could not unlock tables after locking tables $this->tableName");
            return false;
        }

        return true;
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

    public function getPrimaryKey(): string
    {
        return $this->tableService->getNumericPrimaryKey($this->databaseName, $this->tableName);
    }

    /**
     * @return void
     */
    public function prefixSpecialFields()
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
    abstract protected function getFinalTableName();

    /**
     * @return void
     */
    abstract protected function setupSearchReplace();

    /**
     * @return string
     */
    protected function getPrefix(): string
    {
        return $this->database->getBasePrefix();
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
                    $nullFlag = DatabaseImporter::NULL_FLAG;
                    $value    = "'$nullFlag'";
                    continue;
                }

                // binary, varbinary, blob
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

                if ($this->isRecordExcluded($prefixedTableName, $column, $value)) {
                    throw new \OutOfBoundsException();
                }

                $value = $this->searchReplace->replace($value);
                $value = "'{$this->client->escape($value)}'";
            }

            $insertQuery = "INSERT INTO `{$prefixedTableName}` VALUES (" . implode(',', $row) . ");\n";
            $this->appendInsertQuery($insertQuery);
        } catch (Exception $e) {
            // Row skipped, no-op
        }
    }

    /**
     * @param string $insertQuery
     * @return void
     */
    protected function appendInsertQuery(string $insertQuery)
    {
        $this->pendingQueriesSql .= $insertQuery;
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
     * @param array $row
     * @return bool
     */
    protected function isLastInsertedNumericKeyValue(string $numericPrimaryKey, array $row): bool
    {
        if (empty($numericPrimaryKey)) {
            return false;
        }

        $lastInsertedValue = (int)$row[$numericPrimaryKey];
        if ($lastInsertedValue <= $this->lastInsertedNumericPrimaryKeyValue) {
            return true;
        }

        $this->lastInsertedNumericPrimaryKeyValue = $lastInsertedValue;

        return false;
    }

    /**
     * @param string $numericPrimaryKey
     * @param int $rowsExported
     * @return void
     */
    protected function updateRowsExporterDto(string $numericPrimaryKey, int $rowsExported)
    {
        $this->rowsExporterDto->setTotalRowsExported($this->rowsExporterDto->getTotalRowsExported() + $rowsExported);
        if (!empty($numericPrimaryKey)) {
            $this->tableRowsOffset = $this->lastInsertedNumericPrimaryKeyValue;
        } else {
            $this->tableRowsOffset = $this->rowsExporterDto->getRowsOffset() + $rowsExported;
        }

        $this->rowsExporterDto->setRowsOffset($this->tableRowsOffset);
        $this->rowsExporterDto->setLastInsertedNumericPrimaryKeyValue($this->lastInsertedNumericPrimaryKeyValue);
    }

    protected function isRecordExcluded(string $prefixedTableName, string $column, string $value): bool
    {
        if ($prefixedTableName === $this->getFinalPrefix() . 'options' && $column === 'option_name') {
            // Rows not to export
            if (substr($value, 0, 1) === '_') {
                foreach (['_transient_', '_site_transient_', '_wc_session_'] as $excludedOption) {
                    if (strpos($value, $excludedOption) === 0) {
                        return true;
                    }
                }
            }

            // Don't include analytics events when exporting
            if (substr($value, 0, 22) === 'wpstg_analytics_event_') {
                return true;
            }
        }

        return false;
    }
}
