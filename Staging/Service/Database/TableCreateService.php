<?php

namespace WPStaging\Staging\Service\Database;

use RuntimeException;
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
    protected $productionDb;

    /** @var Database */
    protected $stagingDb;

    /** @var TableService */
    protected $tableService;

    /** @var string */
    protected $databaseName;

    /** @var string */
    protected $productionPrefix;

    /** @var string */
    protected $stagingPrefix;

    /**
     * @param Database $database
     */
    public function __construct(Database $productionDb, TableService $tableService)
    {
        $this->productionDb = $productionDb;
        $this->tableService = $tableService;
    }

    public function setup(LoggerInterface $logger, Database $stagingDb)
    {
        $this->logger           = $logger;
        $this->stagingDb        = $stagingDb;
        $this->databaseName     = $this->productionDb->getWpdba()->getClient()->__get('dbname');
        $this->productionPrefix = $this->productionDb->getPrefix();
        $this->stagingPrefix    = $this->stagingDb->getPrefix();
    }

    public function getDestinationTable(string $srcTableName): string
    {
        if (strpos($srcTableName, $this->productionPrefix) !== 0) {
            return $this->stagingPrefix . $srcTableName;
        }

        return $this->stagingPrefix . substr($srcTableName, strlen($this->productionPrefix));
    }

    /**
     * @param string $destTableName
     * @param string $srcTableName
     * @return void
     */
    public function createStagingSiteTable(string $srcTableName, string $destTableName)
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

        $result = $this->stagingDb->getClient()->query($createTableQuery);
        if ($result === false) {
            $error = $this->stagingDb->getWpdb()->last_error;
            throw new RuntimeException("Create Table - Cannot clone table $srcTableName to $destTableName. Error: $error");
        }
    }

    /**
     * @param string $destTableName
     * @return void
     */
    private function dropDestinationTableIfExists(string $destTableName)
    {
        if (!$this->tableService->tableExists($destTableName)) {
            return;
        }

        $this->logger->warning(sprintf("Create Table - Table %s already exists, dropping it first", esc_html($destTableName)));
        if ($this->tableService->dropTable($destTableName)) {
            return;
        }

        throw new RuntimeException("Create Table - Cannot drop table $destTableName");
    }
}
