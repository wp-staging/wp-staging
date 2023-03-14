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
    /**
     * @var CloningDto
     */
    protected $dto;

    /**
     * @var SelectQuery
     */
    protected $selectQueryBuilder;

    /**
     * DatabaseCloningService constructor.
     * @param CloningDto $dto
     */
    public function __construct(CloningDto $dto)
    {
        $this->dto = $dto;

        $this->selectQueryBuilder = new SelectQuery();
    }

    /**
     * @param string $srcTableName
     * @param string $destTableName
     * @param int $offset
     * @param int $limit
     */
    public function copyData($srcTableName, $destTableName, $offset, $limit)
    {
        // Don't replace the table name if the table prefix is a custom prefix and if table is cloned into external database
        if (!$this->shouldRenameTable($srcTableName)) {
            $destTableName = $srcTableName;
        }

        $rows = $offset + $limit;

        $selectQuery = $this->selectQueryBuilder->prepareQueryWithFilter($srcTableName, $limit, $offset);
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

            // Get data from production site
            $rows = $this->dto->getProductionDb()->get_results($preparedQuery, ARRAY_A);
            // Start transaction
            $stagingDb->query('SET autocommit=0;');
            $stagingDb->query('SET FOREIGN_KEY_CHECKS=0;');
            $stagingDb->query('START TRANSACTION;');
            // Copy into staging site
            foreach ($rows as $row) {
                $escapedValues = WPStaging::make(Escape::class)->mysqlRealEscapeString(array_values($row));
                $values = is_array($escapedValues) ? implode("', '", $escapedValues) : $escapedValues;
                $query = "INSERT INTO `$destTableName` VALUES ('$values')";
                if ($stagingDb->query($query) === false) {
                    $this->log("Can not insert data into table $destTableName");
                    $this->debugLog("Failed Query: " . $query . " Error: " . $stagingDb->last_error);
                }
            }
            // Commit transaction
            $this->dto->getStagingDb()->query('COMMIT;');
            $this->dto->getStagingDb()->query('SET autocommit=1;');
        } else {
            $this->log("Copy data from $srcTableName to $destTableName - $offset to $rows records");

            $this->dto->getStagingDb()->query("SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO'");

            $preparedQuery = "INSERT INTO `$destTableName` $selectQuery";
            if (count($preparedValues) > 0) {
                $preparedQuery = $this->dto->getStagingDb()->prepare($preparedQuery, $preparedValues);
            }

            $result = $this->dto->getStagingDb()->query($preparedQuery);

            if (!$result) {
                $this->log("DB Data Copy Error:" . $this->dto->getStagingDb()->last_error, Logger::TYPE_WARNING);
            }
        }
    }

    /**
     * @param string $tableName
     *
     * @return boolean
     */
    public function isMissingTable($tableName)
    {
        $result = $this->dto->getProductionDb()->query("SHOW TABLES LIKE '$tableName'");
        if ($result === false || $result === 0) {
            $this->log("Table {$this->dto->getExternalDatabaseName()}.{$tableName} doesn't exist. Skipping");
            return true;
        }

        return false;
    }

    /**
     * Check if table already exists
     * @param $srcTableName
     * @param $destTableName
     * @return bool
     */
    private function isDestTableExist($srcTableName, $destTableName)
    {
        if (!$this->shouldRenameTable($srcTableName)) {
            $destTableName = $srcTableName;
        }

        $stagingDb = $this->dto->getStagingDb();
        $existingTable = $stagingDb->get_var($stagingDb->prepare("SHOW TABLES LIKE %s", $destTableName));

        return ($destTableName === $existingTable);
    }

    /**
     * Drop table from database
     *
     * @param string $srcTableName
     * @param string $destTableName
     */
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

    /**
     * @param $srcTable
     * @return bool
     */
    private function beginsWithWordPressPrefix($srcTable)
    {
        $productionDb = $this->dto->getProductionDb();
        if (strpos($srcTable, $productionDb->prefix) === 0) {
            return true;
        }
        return false;
    }

    /**
     * If table is not multisite user or usermeta table and does not
     * @param string
     * @return bool
     */
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

    /**
     * @param $tableName
     * @return bool
     */
    private function isMultisiteWpCoreTable($tableName)
    {
        $basePrefix = $this->dto->getProductionDb()->base_prefix;

        $coreTables = [
            $basePrefix . 'users',
            $basePrefix . 'usermeta'
        ];

        if (in_array($tableName, $coreTables)) {
            return true;
        }
        return false;
    }

    /**
     * @param string $destTableName
     * @param string $srcTableName
     * @return int Number of rows in source table
     */
    public function createTable($srcTableName, $destTableName)
    {
        if ($this->isDestTableExist($srcTableName, $destTableName)) {
            $this->dropDestTable($srcTableName, $destTableName);
        }

        $stagingDb = $this->dto->getStagingDb();
        $productionDb = $this->dto->getProductionDb();
        if ($this->dto->isExternal()) {
            $this->log("COPY table {$this->dto->getExternalDatabaseName()}.$srcTableName");
            $sql = $this->getTableCreateStatement($srcTableName);

            // Replace whole table name if it begins with WordPress prefix.
            // Don't replace it if it's a custom table beginning with another prefix #1303
            // Prevents bug where $old table prefix contains no underscore | Fix missing underscore issue #251.
            if ($this->beginsWithWordPressPrefix($srcTableName) || $this->isMultisiteWpCoreTable($srcTableName)) {
                $sql = str_replace("CREATE TABLE `$srcTableName`", "CREATE TABLE `$destTableName`", $sql);
            }

            // Make constraint unique to prevent error:(errno: 121 "Duplicate key on write or update")
            $sql = wpstg_unique_constraint($sql);
            $stagingDb->query('SET FOREIGN_KEY_CHECKS=0;');
            //\WPStaging\functions\debug_log(" DB Query " . $sql);
            if ($stagingDb->query($sql) === false) {
                throw new FatalException("DB External Copy - Fatal Error: $stagingDb->last_error Query: $sql");
            }
        } else {
            $this->log("Creating table $destTableName");
            $query = "CREATE TABLE `{$destTableName}` LIKE `{$srcTableName}`";
            if ($stagingDb->query($query) === false) {
                throw new FatalException("DB Internal Copy - Fatal Error: {$stagingDb->last_error} Query: {$query}");
            }
        }
        $rowsInTable = (int)$productionDb->get_var("SELECT COUNT(1) FROM `$productionDb->dbname`.`$srcTableName`");
        $this->log("Table $srcTableName contains $rowsInTable rows ");
        return $rowsInTable;
    }

    /**
     * @param $tableName
     * @return string
     */
    public function removeDBPrefix($tableName)
    {
        return (new Strings())->str_replace_first(WPStaging::getTablePrefix(), null, $tableName);
    }

    /**
     * @param $tableName
     * @return string
     */
    public function removeDbBasePrefix($tableName)
    {
        return (new Strings())->str_replace_first(WPStaging::getTableBasePrefix(), null, $tableName);
    }

    /**
     * @param string $message
     * @param string $type
     */
    protected function log($message, $type = Logger::TYPE_INFO)
    {
        $prependString = $this->dto->isExternal() ? "DB External Copy: " : "DB Copy: ";
        $this->dto->getJob()->log($prependString . $message, $type);
    }

    /**
     * @param string $message
     * @param string $type
     */
    protected function debugLog($message, $type = Logger::TYPE_INFO)
    {
        $prependString = $this->dto->isExternal() ? "DB External Copy: " : "DB Copy: ";
        $this->dto->getJob()->debugLog($prependString . $message, $type);
    }

    /**
     * Get MySQL create-table query statement.
     * Only used by external databases
     *
     * @param string $tableName Table name
     * @return array
     */
    private function getTableCreateStatement($tableName)
    {
        $productionDb = $this->dto->getProductionDb();

        // Get the CREATE statement from production table
        $statement = $productionDb->get_results("SHOW CREATE TABLE `$tableName`", 'ARRAY_A')[0];

        if ($this->dto->isMultisite()) {
            // Convert prefix and entire table name to lowercase to prevent capitalization issues:
            // https://dev.mysql.com/doc/refman/5.7/en/identifier-case-sensitivity.html

            // @todo Testing! Can lead to issues with CONSTRAINTS

            // Edit: Disabled as we must not change the capitalization of the prefix any longer!
            // This prevented sites from proper cloning where prefix contains capitalized letters
            // Keep this here for reference purposes and to make sure no one EVER tries to implement this again!
            // $row[0] = str_replace($tableName, strtolower($tableName), $row[0]);

            // Build full qualified statement for table [prefix_]users from main site e.g. wp_users
            if ($this->removeDbBasePrefix($tableName) === 'users') {
                $statement = str_replace($tableName, $productionDb->base_prefix . 'users', $statement);
            }
            // Build full qualified statement for table [prefix_]usermeta from main site e.g. wp_usermeta
            if ($this->removeDbBasePrefix($tableName) === 'usermeta') {
                $statement = str_replace($tableName, $productionDb->base_prefix . 'usermeta', $statement);
            }
        }

        // Return create table statement
        if (isset($statement['Create Table'])) {
            return $statement['Create Table'];
        }
        return [];
    }
}
