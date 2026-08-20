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

 
    protected $jobDataDto;

 
    protected $tableService;

 
    protected $logger;

 
    protected $rowsExporterDto;

 
    protected $searchReplace;





    protected $tableIndex = 0;




    protected $tableRowsOffset = 0;




    protected $totalRowsExported = 0;




    protected $totalRowsInCurrentTable = 0;

 
    protected $tables = [];

 
    protected $prefixedValues = [];

 
    protected $pendingQueriesSql = '';

 
    protected $pendingQueriesCount = 0;

 
    protected $isRetryingAfterRepair = false;

 
    protected $tableName = '';

 
    protected $databaseName = '';

 
    protected $lastInsertedNumericPrimaryKeyValue = -PHP_INT_MAX;

 
    protected $specialFields = [];

 
    protected $nonWpTables = [];





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




    public function initiate(): bool
    {
        if ($this->isTableExcluded()) {
            $this->logger->info("Skipping table {$this->tableName} as it is excluded from data copying...");
            return false;
        }

 
        if ($this->rowsExporterDto->getTotalRows() > 0) {
            return true;
        }

        $this->rowsExporterDto->init($this->tableIndex, $this->tableName, 0);

 
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
                $this->logger->notice("The table {$this->tableName} has no compatible primary key and is large, so copying it may take a while. WP STAGING will briefly pause changes to it while copying and resume automatically when done. No action is required.");
            } else {
                $this->logger->notice("The table {$this->tableName} has no compatible primary key, so WP STAGING will briefly pause changes to it while copying and resume automatically when done. No action is required.");
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




    public function isTableExcluded(): bool
    {
        return in_array($this->getTableBeingExported(), $this->excludedTables);
    }




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




    public function getTableBeingExported(): string
    {
        return $this->tableName;
    }




    public function countTotalRows(): int
    {
 
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
                case 144: 
                case 145: 
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

 
 
            $generatorOffset = !empty($numericPrimaryKey) ? $this->lastInsertedNumericPrimaryKeyValue : $this->tableRowsOffset;

            $data = $this->rowsGenerator($this->databaseName, $this->tableName, $numericPrimaryKey, $generatorOffset, $requestId, $this->client, $this->jobDataDto);

            foreach ($data as $row) {
                if ($this->isLastInsertedNumericKeyValue($numericPrimaryKey ?? '', $row)) {
                    continue;
                }

                $this->writeQueryInsert($row, $finalTableName, $tableColumns);
                $this->pendingQueriesCount++;





                if ($this->pendingQueriesCount >= 10) {
                    $this->file->fwrite($this->pendingQueriesSql);
                    $this->pendingQueriesSql = '';
                    $this->updateRowsExporterDto($numericPrimaryKey ?? '', $this->pendingQueriesCount);
                    $this->pendingQueriesCount = 0;
                }
            }
        } while (!$this->isThreshold() && ($this->pendingQueriesCount + $this->rowsExporterDto->getRowsOffset() < $this->rowsExporterDto->getTotalRows()));

 
        if (!empty($this->pendingQueriesSql)) {
            $this->file->fwrite($this->pendingQueriesSql);
            $this->pendingQueriesSql = '';
            $this->updateRowsExporterDto($numericPrimaryKey ?? '', $this->pendingQueriesCount);
            $this->pendingQueriesCount = 0;
        }

        $this->unlockTables();
    }




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
        return $this->tableService->getNumericPrimaryKey($this->databaseName, $this->tableName);
    }




    public function prefixSpecialFields()
    {
        $prefix = $this->getPrefix();






        $this->prefixedValues = array_flip(array_map(function ($unprefixedValue) use ($prefix) {
            return $prefix . $unprefixedValue;
        }, $this->specialFields));
    }




    abstract protected function getFinalTableName();




    abstract protected function setupSearchReplace();




    protected function getPrefix(): string
    {
        return $this->database->getBasePrefix();
    }









    protected function writeQueryInsert(array $row, string $prefixedTableName, array $tableColumns)
    {
        try {
 
 
            $skipSearchReplace = $this->isRowSearchReplaceExcluded($prefixedTableName, $row);

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

                if ($this->isRecordExcluded($prefixedTableName, $column, $value)) {
                    throw new \OutOfBoundsException();
                }

                if (!$skipSearchReplace) {
                    $value = $this->searchReplace->replace($value);
                }

                $value = "'{$this->client->escape($value)}'";
            }

            $insertQuery = "INSERT INTO `{$prefixedTableName}` VALUES (" . implode(',', $row) . ");\n";
            $this->appendInsertQuery($insertQuery);
        } catch (Exception $e) {
 
        }
    }









    protected function isRowSearchReplaceExcluded(string $prefixedTableName, array $row): bool
    {
        return false;
    }





    protected function appendInsertQuery(string $insertQuery)
    {
        $this->pendingQueriesSql .= $insertQuery;
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
 
            if (substr($value, 0, 1) === '_') {
                foreach (['_transient_', '_site_transient_', '_wc_session_'] as $excludedOption) {
                    if (strpos($value, $excludedOption) === 0) {
                        return true;
                    }
                }
            }

 
            if (substr($value, 0, 22) === 'wpstg_analytics_event_') {
                return true;
            }
        }

        return false;
    }
}
