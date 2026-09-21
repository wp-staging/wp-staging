<?php

namespace WPStaging\Staging\Tasks;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Traits\TablePrefixValidator;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Staging\Traits\WithStagingDatabase;
use WPStaging\Staging\Traits\WithStagingOptionsTable;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class DatabaseAdjustmentTask extends DataAdjustmentTask
{
    use TablePrefixValidator;
    use WithStagingDatabase;
    use WithStagingOptionsTable;




    protected $database = null;




    protected $wpdb = null;









    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Database $database)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls);
        $this->database = $database;
    }




    public function setup()
    {
        $this->initStagingDatabase($this->getStagingSiteDto($this->jobDataDto->getCloneId()));
        if ($this->tableService === null) {
            $this->tableService = new TableService($this->stagingDb);
        }

        if ($this->wpdb === null) {
            $this->wpdb = $this->stagingDb->getWpdb();
        }
    }






    protected function isTableExists(string $tableName): bool
    {
        return $this->tableService->tableExists($tableName);
    }






    protected function isTableExcluded(string $tableNameWithoutPrefix): bool
    {
        $tableName = $this->getPrefixedStagingTableName($tableNameWithoutPrefix);

        if (!$this->isTableExists($tableName)) {
            return true;
        }

        if (in_array($tableNameWithoutPrefix, $this->jobDataDto->getExcludedTables())) {
            return true;
        }

        return false;
    }

    protected function isOptionsTableExcluded(): bool
    {
        if ($this->isTableExcluded('options')) {
            return true;
        }

        return false;
    }






    protected function executeQuery(string $query, ...$parameters): bool
    {
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                $query,
                $parameters
            )
        );

        if ($result === false) {
            $this->logger->debug("Database adjustment failed. Query: {$query}.");
            return false;
        }

        return true;
    }
}
