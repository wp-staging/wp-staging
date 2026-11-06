<?php








namespace WPStaging\Framework\Traits;

use Generator;






trait DbRowsGeneratorTrait
{
    protected $tableName = '';

 
    private $stagingSiteDb;

 
    public $lastFetchedPrimaryKeyValue = false;

 
    public $numericPrimaryKey = null;

 
    public $noResultRows = false;





    public $executeNumericPrimaryKeyQuery = true;





    protected $rowsMemoryFactor = 4;





    public $rowsBatchSize = 0;





    protected $tableAverageRowLengths = [];






    protected function getUniqueIntegerKeyColumn()
    {
 
        if (!$this->executeNumericPrimaryKeyQuery) {
            return false;
        }

        $integerColumns = [];
        foreach ((array)$this->stagingSiteDb->get_results('DESCRIBE ' . $this->tableName) as $column) {
            if ($column->Null === 'NO' && preg_match('/^(big|small|medium)?int\b/', $column->Type)) {
                $integerColumns[] = $column->Field;
            }
        }

        $uniqueKeys = [];
        foreach ((array)$this->stagingSiteDb->get_results('SHOW INDEX FROM ' . $this->tableName) as $index) {
            if ((int)$index->Non_unique === 0) {
                $uniqueKeys[$index->Key_name][] = $index->Column_name;
            }
        }

        foreach ($uniqueKeys as $keyColumnNames) {
            if (count($keyColumnNames) === 1 && in_array($keyColumnNames[0], $integerColumns, true)) {
                return $this->numericPrimaryKey = $keyColumnNames[0];
            }
        }

        return false;
    }


















    protected function rowsGenerator($table, $offset, $limit, $db = null)
    {














        $this->tableName = $table;

        if (null === $db) {
            global $wpdb;
            $db = $wpdb;
        }

        $this->stagingSiteDb = $db;

 
        $numericPrimaryKey = ($this->lastFetchedPrimaryKeyValue !== false || (int)$offset === 0) ? $this->getUniqueIntegerKeyColumn() : false;

        $suppressErrorsOriginal = $db->suppress_errors;
        $db->suppress_errors(false);

 
        $this->setTimeLimit(min(10, max((int)$this->findExecutionTimeLimit(), 1)));

        $rows = [];
        $processed = 0;
        $batchSize = $this->rowsBatchSize > 0
            ? min($this->rowsBatchSize, $this->getMaximumBatchSizeForQueryLimit($limit))
            : $this->calculateBatchSizeForTable($table, $limit, $db);
        $lastFetch = false;

        do {
            if (count($rows) === 0) {
                if ($lastFetch) {
                    break;
                }

 
                if (!empty($numericPrimaryKey)) {
                    $whereCondition = '';
                    if ($this->lastFetchedPrimaryKeyValue !== false) {
                        $whereCondition = "WHERE `{$numericPrimaryKey}` > {$this->lastFetchedPrimaryKeyValue}";
                    }

                    $query = <<<SQL
SELECT  *
FROM `{$table}`
{$whereCondition}
ORDER BY `{$numericPrimaryKey}` ASC
LIMIT 0, {$batchSize}
SQL;
                } else {
                    $query = "SELECT * FROM `{$table}` LIMIT {$offset}, {$batchSize}";
                }

                $this->noResultRows = false;
                $rows = $db->get_results($query, ARRAY_A);

 
                $db->flush();

                if (!empty($db->last_error)) {
                    \WPStaging\functions\debug_log($db->last_error);
                }

 
                if (empty($rows) || count($rows) === 0) {
                    $this->noResultRows = true;
                    break;
                }

                if (!is_array($rows)) {
                    \WPStaging\functions\debug_log(sprintf('DbRowsGenerator: $rows is not an array. Actual type: %s', gettype($rows)));
                }

                $offset += $batchSize;
 
                $lastFetch = count($rows) < $batchSize;
            }

 
            $row = array_shift($rows);

 
            if (null === $row) {
                break;
            }

 
            if (!empty($numericPrimaryKey)) {
                $this->lastFetchedPrimaryKeyValue = $row[$numericPrimaryKey];
            }

            yield $row;

 
            $processed++;
        } while (!$this->isThreshold() && $processed < $limit);

 
        $batchWasLeftUnprocessed = !empty($rows) && $processed < $batchSize;
        $this->rowsBatchSize     = $batchWasLeftUnprocessed ? max(1, (int)floor($batchSize / 2)) : $batchSize;

        $db->suppress_errors($suppressErrorsOriginal);
    }











    protected function calculateBatchSizeForTable($table, $limit, \wpdb $db)
    {
        $maximumBatchSize = $this->getMaximumBatchSizeForQueryLimit($limit);
        $averageRowLength = $this->getTableAverageRowLength($table, $db);

        if ($averageRowLength < 1) {
            return $maximumBatchSize;
        }

        $freeMemory = $this->getScriptMemoryLimit() - $this->getMemoryUsage();
        $batchSize  = (int)floor(($freeMemory / $averageRowLength) / $this->rowsMemoryFactor);

        return (int)max(1, min($maximumBatchSize, $batchSize));
    }







    protected function getMaximumBatchSizeForQueryLimit($limit)
    {
        return (int)max(1, ceil($limit / 5));
    }








    protected function getTableAverageRowLength($table, \wpdb $db)
    {
        if (array_key_exists($table, $this->tableAverageRowLengths)) {
            return $this->tableAverageRowLengths[$table];
        }

        $averageRowLength = $db->get_var($db->prepare(
            'SELECT AVG_ROW_LENGTH FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
            $table
        ));

        return $this->tableAverageRowLengths[$table] = is_numeric($averageRowLength) ? (int)$averageRowLength : 0;
    }
}
