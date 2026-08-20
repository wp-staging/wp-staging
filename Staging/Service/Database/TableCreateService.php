<?php

namespace WPStaging\Staging\Service\Database;

use RuntimeException;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\TableService;
use WPStaging\Vendor\Psr\Log\LoggerInterface;







class TableCreateService
{
 
    protected $logger;

 
    protected $sourceDb;

 
    protected $destinationDb;

 
    protected $tableService;

 
    protected $databaseName;

 
    protected $sourcePrefix;

 
    protected $destinationPrefix;

 
    protected $isResetExistingTables = false;




    public function __construct(Database $sourceDb, TableService $tableService)
    {
        $this->sourceDb     = $sourceDb;
        $this->tableService = $tableService;
    }

    public function setup(LoggerInterface $logger, Database $destinationDb)
    {
        $this->logger            = $logger;
        $this->destinationDb     = $destinationDb;
        $this->databaseName      = $this->sourceDb->getWpdba()->getClient()->__get('dbname');
        $this->sourcePrefix      = $this->sourceDb->getPrefix();
        $this->destinationPrefix = $this->destinationDb->getPrefix();
    }

    public function getTableWithoutPrefix(string $srcTableName): string
    {
        if (strpos($srcTableName, $this->sourcePrefix) !== 0) {
            return $srcTableName;
        }

        return substr($srcTableName, strlen($this->sourcePrefix));
    }

    public function getDestinationTable(string $srcTableName): string
    {
        if (empty($srcTableName)) {
            throw new RuntimeException("Get Destination Table - Source table name is empty");
        }

        if (empty($this->destinationPrefix)) {
            throw new RuntimeException("Get Destination Table - Destination table prefix is empty");
        }

        if (strpos($srcTableName, $this->sourcePrefix) === 0) {
            return $this->destinationPrefix . substr($srcTableName, strlen($this->sourcePrefix));
        }

        $basePrefix = $this->sourceDb->getBasePrefix();
        if (
            in_array($srcTableName, [
                $basePrefix . 'users',
                $basePrefix . 'usermeta',
            ], true)
        ) {
            return $this->destinationPrefix . substr($srcTableName, strlen($basePrefix));
        }

        return $this->destinationPrefix . $srcTableName;
    }





    public function setIsResetExistingTables(bool $isResetExistingTables)
    {
        $this->isResetExistingTables = $isResetExistingTables;
    }

    public function isTableExist(string $tableName): bool
    {
        return $this->tableService->tableExists($tableName);
    }

    public function preserveExistingTable(string $tableName, string $tableWithoutPrefix): string
    {
        $newTableName = DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP . $tableWithoutPrefix;
        $this->dropOrphanedBackupTable($newTableName);
        $this->logger->info(sprintf("Preserving existing table %s by renaming it to %s", esc_html($tableName), esc_html($newTableName)));
        if ($this->tableService->renameTable($tableName, $newTableName)) {
            return $newTableName;
        }

        throw new RuntimeException("Cleanup Table - Cannot preserve existing table. Error: Unable to rename table $tableName to $newTableName");
    }










    protected function dropOrphanedBackupTable(string $backupTableName)
    {
        if (!$this->tableService->tableExists($backupTableName)) {
            return;
        }

        $this->logger->warning(sprintf("Cleanup Table - Dropping orphaned backup table %s left by a previous job", esc_html($backupTableName)));
        $this->tableService->dropTable($backupTableName);
    }






    public function createDestinationTable(string $srcTableName, string $destTableName)
    {
        $this->logger->info(sprintf("Creating table %s -> %s", esc_html($srcTableName), esc_html($destTableName)));
        $this->dropDestinationTableIfExists($destTableName);

        $createTableQuery = $this->tableService->getCreateTableQuery($srcTableName);
        if (empty($createTableQuery)) {
            throw new RuntimeException("Create Table - Cannot clone table $srcTableName to $destTableName. Error: Unable to find create table query");
        }

        $createTableQuery = str_replace($srcTableName, $destTableName, $createTableQuery);
        $createTableQuery = $this->tableService->replaceTableConstraints($createTableQuery);
        $createTableQuery = $this->tableService->replaceTableOptions($createTableQuery);
        if (empty($createTableQuery)) {
            throw new RuntimeException("Create Table - Cannot clone table $srcTableName to $destTableName. Error: Unable to replace contraints");
        }

        $result = $this->destinationDb->getClient()->query($createTableQuery);
        if ($result === false) {
            $error = $this->destinationDb->getWpdb()->last_error;
            throw new RuntimeException("Create Table - Cannot clone table $srcTableName to $destTableName. Error: $error");
        }
    }





    protected function dropDestinationTableIfExists(string $destTableName)
    {
        if (!$this->tableService->tableExists($destTableName)) {
            return;
        }

        if (!$this->isResetExistingTables) {
            throw new RuntimeException("Create Table - Cannot clone table. Error: Destination table $destTableName already exists.");
        }

        $this->logger->warning(sprintf("Create Table - Table %s already exists, dropping it first", esc_html($destTableName)));
        if ($this->tableService->dropTable($destTableName)) {
            return;
        }

        throw new RuntimeException("Create Table - Cannot drop table $destTableName");
    }
}
