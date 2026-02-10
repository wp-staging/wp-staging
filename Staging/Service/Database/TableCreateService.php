<?php

namespace WPStaging\Staging\Service\Database;

use RuntimeException;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\TableService;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * This class is similar to the src\Framework\CloningProcess\Database\DatabaseCloningService class.
 * It is adjusted to use Backuper Logic and it only focus on creating a table in the staging site.
 * @see src\Framework\CloningProcess\Database\DatabaseCloningService for existing logic
 * @see src\Staging\Service\Database\RowsCopier for logic related to copying tables rows
 */
class TableCreateService
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Database */
    protected $sourceDb;

    /** @var Database */
    protected $destinationDb;

    /** @var TableService */
    protected $tableService;

    /** @var string */
    protected $databaseName;

    /** @var string */
    protected $sourcePrefix;

    /** @var string */
    protected $destinationPrefix;

    /** @var bool */
    protected $isResetExistingTables = false;

    /**
     * @param Database $database
     */
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
        if (strpos($srcTableName, $this->sourcePrefix) !== 0) {
            return $this->destinationPrefix . $srcTableName;
        }

        return $this->destinationPrefix . substr($srcTableName, strlen($this->sourcePrefix));
    }

    /**
     * @param bool $isResetExistingTables
     * @return void
     */
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
        $this->logger->info(sprintf("Preserving existing table %s by renaming it to %s", esc_html($tableName), esc_html($newTableName)));
        if ($this->tableService->renameTable($tableName, $newTableName)) {
            return $newTableName;
        }

        throw new RuntimeException("Cleanup Table - Cannot preserve existing table. Error: Unable to rename table $tableName to $newTableName");
    }

    /**
     * @param string $destTableName
     * @param string $srcTableName
     * @return void
     */
    public function createDestinationTable(string $srcTableName, string $destTableName)
    {
        $this->logger->info(sprintf("Creating table %s", esc_html($destTableName)));
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

    /**
     * @param string $destTableName
     * @return void
     */
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
