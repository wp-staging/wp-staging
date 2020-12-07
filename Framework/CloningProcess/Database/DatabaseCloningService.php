<?php


namespace WPStaging\Framework\CloningProcess\Database;


use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Framework\CloningProcess\CloningDto;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Utils\Strings;

class DatabaseCloningService
{
    /**
     * @var CloningDto
     */
    protected $dto;

    /**
     * DatabaseCloningService constructor.
     * @param CloningDto $dto
     */
    public function __construct(CloningDto $dto)
    {
        $this->dto = $dto;
    }

    /**
     * @param string $old
     * @param string $new
     * @param int $offset
     * @param int $limit
     */
    public function copyData($old, $new, $offset, $limit)
    {
        $rows = $offset + $limit;
        $limitation = '';
        if (( int )$limit > 0) {
            $limitation = " LIMIT {$limit} OFFSET {$offset}";
        }
        if ($this->dto->isExternal()) {
            $stagingDb = $this->dto->getStagingDb();
            $this->log(
                "INSERT {$this->dto->getProductionDb()->dbname}.{$old} as {$this->dto->getExternalDatabaseName()}.{$new} from {$offset} to {$rows} records"
            );
            // Get data from production site
            $rows = $this->dto->getProductionDb()->get_results("SELECT * FROM `{$old}` {$limitation}", ARRAY_A);
            // Start transaction
            $stagingDb->query('SET autocommit=0;');
            $stagingDb->query('SET FOREIGN_KEY_CHECKS=0;');
            $stagingDb->query('START TRANSACTION;');
            // Copy into staging site
            foreach ($rows as $row) {
                $escaped_values = $this->mysqlEscapeMimic(array_values($row));
                $values = implode("', '", $escaped_values);
                if ($stagingDb->query("INSERT INTO `{$new}` VALUES ('{$values}')") === false) {
                    $this->log("Can not insert data into table {$new}");
                }
            }
            // Commit transaction
            $this->dto->getStagingDb()->query('COMMIT;');
            $this->dto->getStagingDb()->query('SET autocommit=1;');
        } else {
            $this->log(
                "{$old} as {$new} from {$offset} to {$rows} records"
            );
            $this->dto->getStagingDb()->query(
                "INSERT INTO {$new} SELECT * FROM {$old} {$limitation}"
            );
        }
    }

    /**
     * Drop table from database
     * @param string $name
     */
    public function dropTable($name)
    {
        $stagingDb = $this->dto->getStagingDb();
        $this->log("{$name} already exists, dropping it first");
        $stagingDb->query("SET FOREIGN_KEY_CHECKS=0");
        $stagingDb->query("DROP TABLE {$name}");
        $stagingDb->query("SET FOREIGN_KEY_CHECKS=1");
    }

    /**
     * @param $tableName
     */
    public function tableIsMissing($tableName)
    {
        $result = $this->dto->getProductionDb()->query("SHOW TABLES LIKE '{$tableName}'");
        if ($result === false || $result === 0) {
            $this->log("Table {$this->dto->getExternalDatabaseName()}.{$tableName} doesn't exist. Skipping");
            return true;
        }
        return false;
    }

    /**
     * @param string $new
     * @param string $old
     * @return int Number of rows in old table
     */
    public function createTable($new, $old)
    {
        $stagingDb = $this->dto->getStagingDb();
        $productionDb = $this->dto->getProductionDb();
        if ($this->dto->isExternal()) {
            $this->log("CREATE table {$this->dto->getExternalDatabaseName()}.{$new}");
            $sql = $this->getTableCreateStatement($old);
            if ($this->dto->isMultisite()) {
                $search = '';
                // Get name table users from main site e.g. wp_users
                if ($this->removeDBBasePrefix($old) === 'users') {
                    $search = $productionDb->prefix . 'users';
                }
                // Get name of table usermeta from main site e.g. wp_usermeta
                if ($this->removeDBBasePrefix($old) === 'usermeta') {
                    $search = $productionDb->prefix . 'usermeta';
                }
                // Replace table prefix to the destination prefix
                $sql = str_replace("CREATE TABLE `{$search}`", "CREATE TABLE `{$new}`", $sql);
            }
            // Fix missing underscore issue #251. Replace whole table name. Prevents bug where $old table prefix contains no underscore
            $sql = str_replace("CREATE TABLE `{$old}`", "CREATE TABLE `{$new}`", $sql);
            // Make constraint unique to prevent error:(errno: 121 "Duplicate key on write or update")
            $sql = wpstg_unique_constraint($sql);
            $stagingDb->query('SET FOREIGN_KEY_CHECKS=0;');
            if ($stagingDb->query($sql) === false) {
                throw new FatalException("DB External Copy - Fatal Error: {$stagingDb->last_error} Query: {$sql}");
            }
        } else {
            $this->log("Creating table {$new}");
            $stagingDb->query("CREATE TABLE {$new} LIKE {$old}");
        }
        $rowsInTable = ( int )$productionDb->get_var("SELECT COUNT(1) FROM `{$productionDb->dbname}`.`{$old}`");
        $this->log("Table {$old} contains {$rowsInTable} rows ");
        return $rowsInTable;
    }

    /**
     * @param $tableName
     * @return string
     */
    public function removeDBPrefix($tableName)
    {
        return (new Strings())->str_replace_first($this->dto->getProductionDb()->prefix, null, $tableName);
    }

    /**
     * @param $tableName
     * @return string
     */
    public function removeDBBasePrefix($tableName)
    {
        return (new Strings())->str_replace_first($this->dto->getProductionDb()->base_prefix, null, $tableName);
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
     * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
     * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
     * @access public
     * @param mixed string|array $input The string to escape.
     * @return string|array
     */
    private function mysqlEscapeMimic($input)
    {
        if (is_array($input)) {
            return array_map(__METHOD__, $input);
        }
        if (!empty($input) && is_string($input)) {
            return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $input);
        }

        return $input;
    }

    /**
     * Get MySQL create-table query statement.
     * Only used by external databases
     *
     * @param string $table_name Table name
     * @return array
     */
    private function getTableCreateStatement($tableName)
    {
        $productionDb = $this->dto->getProductionDb();
        // Get the CREATE statement from production table
        $statement = $productionDb->get_results("SHOW CREATE TABLE `{$tableName}`", 'ARRAY_A')[0];

        if ($this->dto->isMultisite()) {
            // Convert prefix and entire table name to lowercase to prevent capitalization issues:
            // https://dev.mysql.com/doc/refman/5.7/en/identifier-case-sensitivity.html
            // @todo Testing! Can lead to issues with CONSTRAINTS
            // Edit: Disabled as we must not change the capitalization of the prefix any longer!
            // This prevented sites from proper cloning where prefix contains capitalized letters
            // Keep this here for historical purposes and to make sure no one tries to implement this again!
            //$row[0] = str_replace($tableName, strtolower($tableName), $row[0]);

            // Get name table users from main site e.g. wp_users
            if ($this->removeDBBasePrefix($tableName) === 'users') {
                $statement = str_replace($tableName, $productionDb->prefix . 'users', $statement);
            }
            // Get name of table usermeta from main site e.g. wp_usermeta
            if ($this->removeDBBasePrefix($tableName) === 'usermeta') {
                $statement = str_replace($tableName, $productionDb->prefix . 'usermeta', $statement);
            }
        }

        // Get create table
        if (isset($statement['Create Table'])) {
            return $statement['Create Table'];
        }
        return [];
    }

}
