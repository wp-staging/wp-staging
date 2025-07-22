<?php

namespace WPStaging\Staging\Tasks;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Staging\Traits\WithStagingDatabase;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class DatabaseAdjustmentTask extends DataAdjustmentTask
{
    use WithStagingDatabase;

    /**
     * @var ?Database
     */
    protected $database = null;

    /**
     * @var ?\wpdb
     */
    protected $wpdb = null;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Urls $urls
     * @param Database $database
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls, Database $database)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue, $urls);
        $this->database = $database;
    }

    /**
     * @return void
     */
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

    /**
     * Check if the table exists in the staging database.
     * @param string $tableName
     * @return bool
     */
    protected function isTableExists(string $tableName): bool
    {
        return $this->tableService->tableExists($tableName);
    }

    /**
     * Check if the table excluded.
     * @param string $tableNameWithoutPrefix
     * @return bool
     */
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

    protected function getPrefixedStagingTableName(string $tableName): string
    {
        return $this->jobDataDto->getDatabasePrefix() . $tableName;
    }

    protected function isOptionsTableExcluded(): bool
    {
        $optionsTable = $this->getOptionsTableName();
        if ($this->isTableExcluded($optionsTable)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $optionName
     * @param string|null $optionValue
     * @param bool $autoload
     * @return bool
     */
    protected function insertOption(string $optionName, $optionValue, bool $autoload = false): bool
    {
        // Let delete the option regardless it exists or not
        $this->deleteOption($optionName);

        $optionTable = $this->getOptionsTableName();
        return $this->executeQuery(
            "INSERT INTO `{$optionTable}` (option_name, option_value, autoload) VALUES (%s, %s, %s)",
            $optionName,
            $optionValue,
            $autoload ? 'on' : 'off'
        );
    }

    /**
     * @param string $optionName
     * @param string $optionValue
     * @return bool
     */
    protected function updateOption(string $optionName, string $optionValue): bool
    {
        $optionTable = $this->getOptionsTableName();
        return $this->executeQuery(
            "UPDATE `{$optionTable}` SET `option_value` = %s WHERE `option_name` = %s;",
            $optionValue,
            $optionName
        );
    }

    /**
     * @param string $optionName
     * @return bool
     */
    protected function deleteOption(string $optionName): bool
    {
        $optionTable = $this->getOptionsTableName();
        return $this->executeQuery(
            "DELETE FROM `{$optionTable}` WHERE `option_name` = %s;",
            $optionName
        );
    }

    protected function getOptionsTableName(): string
    {
        return $this->getPrefixedStagingTableName('options');
    }

    /**
     * @param string $query
     * @param array $parameters
     * @return bool
     */
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

    protected function lastError(): string
    {
        return $this->wpdb->last_error;
    }
}
