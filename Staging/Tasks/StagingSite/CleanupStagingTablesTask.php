<?php

namespace WPStaging\Staging\Tasks\StagingSite;

use Exception;
use WPStaging\Framework\Adapter\DatabaseInterface;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Staging\Traits\WithStagingDatabase;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class CleanupStagingTablesTask extends StagingTask
{
    use WithStagingDatabase;

    /** @var array An array with the name of all existing tables. */
    protected $tables = [];

    /** @var array An array with the name of all existing views. */
    protected $views = [];

    /** @var DatabaseInterface */
    protected $productionDb;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, DatabaseInterface $productionDb, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->productionDb = $productionDb;
    }

    public static function getTaskName()
    {
        return "staging_cleanup_tables";
    }

    public static function getTaskTitle()
    {
        return 'Cleaning Up Staging Site Tables';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute()
    {
        $stagingPrefix = $this->prepareCleanupTask();

        if (empty($stagingPrefix)) {
            return $this->generateResponse(true);
        }

        $this->views  = $this->getStagingViews($stagingPrefix);
        $this->tables = $this->getStagingTables($stagingPrefix);

        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            $tableOrViewName = $this->taskQueue->dequeue();

            // Double-check we are deleting a temporary table just to be extra-careful.
            if (strpos($tableOrViewName, $stagingPrefix) !== 0) {
                $this->logger->warning(sprintf(
                    '%s: Staging site table "%s" did not start with staging prefix "%s" and was skipped.',
                    static::getTaskTitle(),
                    esc_html($tableOrViewName),
                    esc_html($stagingPrefix)
                ));

                continue;
            }

            $label = 'table';

            if (in_array($tableOrViewName, $this->views)) {
                $label = 'view';
                $deleted = $this->tableService->deleteViews([$tableOrViewName]);
            } elseif (in_array($tableOrViewName, $this->tables)) {
                $deleted = $this->tableService->deleteTables([$tableOrViewName]);
            } else {
                $deleted = false;
            }

            $this->stepsDto->incrementCurrentStep();

            if ($deleted) {
                $this->logger->debug(sprintf(
                    '%s: Deleted staging site %s "%s".',
                    static::getTaskTitle(),
                    esc_html($label),
                    esc_html($tableOrViewName)
                ));
            } else {
                $this->logger->warning(sprintf(
                    '%s: Staging site %s "%s" was not successfully deleted.',
                    static::getTaskTitle(),
                    esc_html($label),
                    esc_html($tableOrViewName)
                ));
            }
        }

        if ($this->taskQueue->isFinished()) {
            $this->stepsDto->finish();
        }

        if ($this->stepsDto->isFinished()) {
            // Successfully deleted
            $this->logger->info(sprintf(
                '%s: Tables with staging site prefix "%s" successfully cleaned up.',
                static::getTaskTitle(),
                esc_html($stagingPrefix)
            ));
        }


        return $this->generateResponse(false);
    }

    /**
     * @return string
     */
    public function prepareCleanupTask(): string
    {
        if (!$this->jobDataDto instanceof StagingSiteDtoInterface) {
            throw new Exception('Clone ID not found in job data.');
        }

        /** @var StagingSiteDtoInterface */
        $jobDataDto  = $this->jobDataDto;

        $this->initStagingDatabase($this->getStagingSiteDto($this->jobDataDto->getCloneId()));
        $this->tableService = new TableService($this->stagingDb);

        // Early bail: Already prepared
        if ($this->stepsDto->getTotal() > 0) {
            return $jobDataDto->getStagingSite()->getUsedPrefix();
        }

        $stagingSiteDto = $this->getStagingSiteDto($jobDataDto->getCloneId());
        $jobDataDto->setStagingSite($stagingSiteDto);

        $stagingPrefix = $stagingSiteDto->getUsedPrefix();

        if (!$stagingSiteDto->getIsExternalDatabase() && $this->productionDb->getPrefix() === $stagingPrefix) {
            $this->logger->warning(sprintf(
                '%s: Staging site prefix "%s" is the same as the WordPress table prefix, it is also not a external database connection. This is not allowed.',
                static::getTaskTitle(),
                esc_html($stagingPrefix)
            ));

            return '';
        }

        $tmpViews  = $this->getStagingViews($stagingPrefix);
        $tmpTables = $this->getStagingTables($stagingPrefix);
        $toDelete  = array_merge($tmpViews, $tmpTables);
        $count     = 0;

        foreach ($toDelete as $tableOrViewName) {
            $count++;
            $this->taskQueue->enqueue($tableOrViewName);
        }

        $this->taskQueue->seek(0);

        $this->stepsDto->setTotal($count);

        return $stagingPrefix;
    }
}
