<?php

namespace WPStaging\Framework\CloningProcess\Database;

use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Framework\CloningProcess\CloningDto;
use WPStaging\Framework\Database\QueryBuilder\SelectQuery;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\Escape;

class DatabaseCloningService
{



    protected $dto;




    protected $selectQueryBuilder;





    public function __construct(CloningDto $dto)
    {
        $this->dto = $dto;

        $this->selectQueryBuilder = new SelectQuery();
    }







    public function copyData($srcTableName, $destTableName, $offset, $limit)
    {
 
        if (!$this->shouldRenameTable($srcTableName)) {
            $destTableName = $srcTableName;
        }

        $rows = $offset + $limit;

        $selectQuery    = $this->selectQueryBuilder->prepareQueryWithFilter($srcTableName, $limit, $offset);
        $preparedValues = $this->selectQueryBuilder->getPreparedValues();

        if ($this->dto->isExternal()) {
            $stagingDb = $this->dto->getStagingDb();
            $this->log(
                "INSERT {$this->dto->getProductionDb()->dbname}.$srcTableName as {$this->dto->getExternalDatabaseName()}.$destTableName from $offset to $rows records"
            );

            $preparedQuery = $selectQuery;
            if (count($preparedValues) > 0) {
                $preparedQuery = $this->dto->getProductionDb()->prepare($preparedQuery, $preparedValues);
            }

 
            $result = $this->dto->getProductionDb()->get_results($preparedQuery, ARRAY_A);
 
            $stagingDb->query('SET autocommit=0;');
            $stagingDb->query('SET FOREIGN_KEY_CHECKS=0;');
            $stagingDb->query('START TRANSACTION;');

 
            $escapeUtil   = WPStaging::make(Escape::class);
            $tableColumns = $this->getColumnTypes($srcTableName);
            $isCommitted = false;

            try {
 
                foreach ($result as $row) {
 
                    $values      = $this->prepareValuesStatement($row, $tableColumns, $escapeUtil);
                    $query       = "INSERT INTO `$destTableName` VALUES ($values)";
                    $insertQuery = $query;
                    $inserted    = $stagingDb->query($insertQuery);

                    if ($inserted === false && $this->isDuplicateEntryError($stagingDb->last_error)) {
                        $this->log(
                            "DB Data Copy Warning: {$stagingDb->last_error}. "
                            . "Retrying the row while skipping duplicate keys.",
                            Logger::TYPE_WARNING
                        );

                        $insertQuery = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $query, 1);
                        $inserted    = is_string($insertQuery) ? $stagingDb->query($insertQuery) : false;

                        if ($inserted !== false) {
                            $this->throwOnUnexpectedInsertWarnings($stagingDb, $srcTableName, $destTableName);
                        }
                    }

                    if ($inserted === false) {
                        $lastError = $stagingDb->last_error;
                        $this->debugLog("Failed Query: " . $insertQuery . " Error: " . $lastError);

                        throw new FatalException(
                            "DB Data Copy Error: Failed to copy data from {$srcTableName} "
                            . "to {$destTableName}. {$lastError}"
                        );
                    }
                }

 
                $stagingDb->query('COMMIT;');
                $isCommitted = true;
            } finally {
                if (!$isCommitted) {
                    $stagingDb->query('ROLLBACK;');
                }

                $stagingDb->query('SET FOREIGN_KEY_CHECKS=1;');
                $stagingDb->query('SET autocommit=1;');
            }
        } else {
            $this->log("Copy data from $srcTableName to $destTableName - $offset to $rows records");

            $this->dto->getStagingDb()->query("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");

            $preparedQuery = "INSERT INTO `$destTableName` $selectQuery";
            if (count($preparedValues) > 0) {
                $preparedQuery = $this->dto->getStagingDb()->prepare($preparedQuery, $preparedValues);
            }

            $stagingDb = $this->dto->getStagingDb();
            $result    = $stagingDb->query($preparedQuery);

            if ($result === false && $this->isDuplicateEntryError($stagingDb->last_error)) {
                $this->log(
                    "DB Data Copy Warning: {$stagingDb->last_error}. Retrying the batch while skipping duplicate keys.",
                    Logger::TYPE_WARNING
                );

                $preparedQuery = preg_replace('/^INSERT\s+INTO/i', 'INSERT IGNORE INTO', $preparedQuery, 1);
                $result        = is_string($preparedQuery) ? $stagingDb->query($preparedQuery) : false;

                if ($result !== false) {
                    $this->throwOnUnexpectedInsertWarnings($stagingDb, $srcTableName, $destTableName);
                }
            }

            if ($result === false) {
                throw new FatalException(
                    "DB Data Copy Error: Failed to copy data from {$srcTableName} to {$destTableName}. {$stagingDb->last_error}"
                );
            }
        }
    }

    private function isDuplicateEntryError(string $message): bool
    {
        return stripos($message, 'Duplicate entry') !== false;
    }








    private function throwOnUnexpectedInsertWarnings($stagingDb, string $srcTableName, string $destTableName)
    {
        $warnings = $stagingDb->get_results('SHOW WARNINGS', ARRAY_A);
        foreach ((array)$warnings as $warning) {
            $warningCode = isset($warning['Code']) ? (int)$warning['Code'] : 0;
            if ($warningCode === 1062) {
                continue;
            }

            $warningMessage = isset($warning['Message']) ? $warning['Message'] : 'Unknown database warning.';
            throw new FatalException(
                "DB Data Copy Error: Failed to copy data from {$srcTableName} to {$destTableName}. "
                . "Unexpected INSERT IGNORE warning {$warningCode}: {$warningMessage}"
            );
        }
    }






    public function isMissingTable($tableName)
    {
        $result = $this->dto->getProductionDb()->query("SHOW TABLES LIKE '$tableName'");
        if ($result === false || $result === 0) {
            $this->log("Table {$this->dto->getExternalDatabaseName()}.{$tableName} doesn't exist. Skipping");
            return true;
        }

        return false;
    }







    private function isDestTableExist($srcTableName, $destTableName)
    {
        if (!$this->shouldRenameTable($srcTableName)) {
            $destTableName = $srcTableName;
        }

        $stagingDb     = $this->dto->getStagingDb();
        $existingTable = $stagingDb->get_var($stagingDb->prepare("SHOW TABLES LIKE %s", $destTableName));

        return ($destTableName === $existingTable);
    }







    private function dropDestTable($srcTableName, $destTableName)
    {
        if (!$this->shouldRenameTable($srcTableName)) {
            $destTableName = $srcTableName;
        }

        $stagingDb = $this->dto->getStagingDb();
        $this->log("Table $destTableName already exists, dropping it first");
        $stagingDb->query("SET FOREIGN_KEY_CHECKS=0");
        $stagingDb->query("DROP TABLE {$destTableName}");
        $stagingDb->query("SET FOREIGN_KEY_CHECKS=1");
    }





    private function beginsWithWordPressPrefix($srcTable)
    {
        $productionDb = $this->dto->getProductionDb();
        if (strpos($srcTable, $productionDb->prefix) === 0) {
            return true;
        }

        return false;
    }






    private function shouldRenameTable($srcTable)
    {
        if ($this->dto->isExternal() && $this->isMultisiteWpCoreTable($srcTable)) {
            return true;
        }

        if ($this->dto->isExternal() && !$this->beginsWithWordPressPrefix($srcTable)) {
            return false;
        }

        return true;
    }





    private function isMultisiteWpCoreTable($tableName)
    {
        $basePrefix = $this->dto->getProductionDb()->base_prefix;

        $coreTables = [
            $basePrefix . 'users',
            $basePrefix . 'usermeta',
        ];

        if (in_array($tableName, $coreTables)) {
            return true;
        }

        return false;
    }






    public function createTable($srcTableName, $destTableName)
    {
        if ($this->isDestTableExist($srcTableName, $destTableName)) {
            $this->dropDestTable($srcTableName, $destTableName);
        }

        $stagingDb    = $this->dto->getStagingDb();
        $productionDb = $this->dto->getProductionDb();
        if ($this->dto->isExternal()) {
            $this->log("COPY table {$this->dto->getExternalDatabaseName()}.$srcTableName");
            $sql = $this->getTableCreateStatement($srcTableName);

 
            if ($sql === []) {
                throw new FatalException("DB External Copy - Fatal Error: Could not get CREATE statement for table $srcTableName");
            }

 
 
 
            if ($this->beginsWithWordPressPrefix($srcTableName) || $this->isMultisiteWpCoreTable($srcTableName)) {
                $sql = str_replace("CREATE TABLE `$srcTableName`", "CREATE TABLE `$destTableName`", $sql);
            }

 
            $sql = wpstg_unique_constraint($sql);
            $stagingDb->query('SET FOREIGN_KEY_CHECKS=0;');
 
            if ($stagingDb->query($sql) === false) {
                throw new FatalException("DB External Copy - Fatal Error: $stagingDb->last_error Query: $sql");
            }
        } else {
            $this->log("Creating table $srcTableName -> $destTableName");
            $query = "CREATE TABLE `{$destTableName}` LIKE `{$srcTableName}`";
            if ($stagingDb->query($query) === false) {
                throw new FatalException("DB Internal Copy - Fatal Error: {$stagingDb->last_error} Query: {$query}");
            }
        }

 
        $tableName = empty($productionDb->dbname) ? "`" . $srcTableName . "`" : "`" . $productionDb->dbname . "`.`" . $srcTableName . "`";

        $rowsInTable = (int)$productionDb->get_var("SELECT COUNT(1) FROM " . $tableName);
        $this->log("Table $srcTableName contains $rowsInTable rows ");
        return $rowsInTable;
    }





    public function removeDBPrefix($tableName)
    {
        return (new Strings())->str_replace_first(WPStaging::getTablePrefix(), '', $tableName);
    }





    public function removeDbBasePrefix($tableName)
    {
        return (new Strings())->str_replace_first(WPStaging::getTableBasePrefix(), '', $tableName);
    }





    protected function log($message, $type = Logger::TYPE_INFO)
    {
        $prependString = $this->dto->isExternal() ? "DB External Copy: " : "DB Copy: ";
        $this->dto->getJob()->log($prependString . $message, $type);
    }





    protected function debugLog($message, $type = Logger::TYPE_INFO)
    {
        $prependString = $this->dto->isExternal() ? "DB External Copy: " : "DB Copy: ";
        $this->dto->getJob()->debugLog($prependString . $message, $type);
    }








    private function getTableCreateStatement($tableName)
    {
        $productionDb = $this->dto->getProductionDb();

 
        $statement = $productionDb->get_results("SHOW CREATE TABLE `$tableName`", 'ARRAY_A')[0];

        if ($this->dto->isMultisite()) {
 
 

 

 
 
 
 

 
            if ($this->removeDbBasePrefix($tableName) === 'users') {
                $statement = str_replace($tableName, $productionDb->base_prefix . 'users', $statement);
            }

 
            if ($this->removeDbBasePrefix($tableName) === 'usermeta') {
                $statement = str_replace($tableName, $productionDb->base_prefix . 'usermeta', $statement);
            }
        }

 
        if (isset($statement['Create Table'])) {
            return $statement['Create Table'];
        }

        return [];
    }








    protected function prepareValuesStatement($row, $tableColumns, $escapeUtil)
    {
        $preparedValues = [];
        foreach ($row as $column => $value) {
            if (is_null($value)) {
                $preparedValues[] = 'NULL';
                continue;
            }

            if (
                strpos($tableColumns[strtolower($column)], 'binary') !== false ||
                strpos($tableColumns[strtolower($column)], 'blob') !== false
            ) {
                $preparedValues[] = "UNHEX('" . bin2hex($value) . "')";
                continue;
            }

            $value            = $escapeUtil->mysqlRealEscapeString($value);
            $preparedValues[] = "'$value'";
        }

        return implode(', ', $preparedValues);
    }






    private function getColumnTypes($tableName)
    {
        $column_types = [];

        $result = $this->dto->getProductionDb()->get_results("SHOW COLUMNS FROM `{$tableName}`", ARRAY_A);
        foreach ($result as $row) {
            if (isset($row['Field'])) {
                $column_types[strtolower($row['Field'])] = strtolower($row['Type']);
            }
        }

        return $column_types;
    }
}
