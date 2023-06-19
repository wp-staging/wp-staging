<?php

namespace WPStaging\Backup\Task\Tasks;

use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Backup\Ajax\Restore\PrepareRestore;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Task\AbstractTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\Cache;

class CleanupTmpTablesTask extends AbstractTask
{
    private $tableService;

    /** @var array An array with the name of all existing tables. */
    protected $tables = [];

    /** @var array An array with the name of all existing views. */
    protected $views = [];

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, TableService $tableService, SeekableQueueInterface $taskQueue)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->tableService = $tableService;
    }

    public static function getTaskName()
    {
        return 'backup_restore_cleanup_tables';
    }

    public static function getTaskTitle()
    {
        return 'Cleaning Up Restore Tables';
    }

    /**
     * @return \WPStaging\Backup\Dto\TaskResponseDto
     */
    public function execute()
    {
        $this->prepareCleanupRestoreTask();

        $this->tables = $this->tableService->findTableNamesStartWith(null);
        $this->views = $this->tableService->findViewsNamesStartWith(null);

        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            $tableOrViewName = $this->taskQueue->dequeue();

            // Double-check we are deleting a temporary table just to be extra-careful.
            if (strpos($tableOrViewName, PrepareRestore::TMP_DATABASE_PREFIX) !== 0) {
                $this->logger->warning(sprintf(
                    __('%s: Temporary table "%s" did not start with temporary prefix "%s" and was skipped.', 'wp-staging'),
                    static::getTaskTitle(),
                    $tableOrViewName,
                    PrepareRestore::TMP_DATABASE_PREFIX
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

            if ($deleted) {
                $this->stepsDto->incrementCurrentStep();
                $this->logger->debug(sprintf(
                    __('%s: Deleted temporary %s "%s".', 'wp-staging'),
                    static::getTaskTitle(),
                    $label,
                    $tableOrViewName
                ));
            } else {
                $this->stepsDto->incrementCurrentStep();
                $this->logger->warning(sprintf(
                    __('%s: Temporary %s "%s" was not successfully cleaned up.', 'wp-staging'),
                    static::getTaskTitle(),
                    $label,
                    $tableOrViewName
                ));
            }
        }

        if ($this->taskQueue->isFinished()) {
            $this->stepsDto->finish();

            // Successfully deleted
            $this->logger->info(sprintf(
                __('%s: Tables with temporary prefix "%s" successfully cleaned up.', 'wp-staging'),
                static::getTaskTitle(),
                PrepareRestore::TMP_DATABASE_PREFIX
            ));
        }


        return $this->generateResponse(false);
    }

    public function prepareCleanupRestoreTask()
    {
        // Early bail: Already prepared
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        $tmpViews = $this->tableService->findViewsNamesStartWith(PrepareRestore::TMP_DATABASE_PREFIX);
        $tmpTables = $this->tableService->findTableNamesStartWith(PrepareRestore::TMP_DATABASE_PREFIX);

        $toDelete = array_merge($tmpViews, $tmpTables);

        $count = 0;

        foreach ($toDelete as $tableOrViewName) {
            $count++;
            $this->taskQueue->enqueue($tableOrViewName);
        }

        $this->taskQueue->seek(0);

        $this->stepsDto->setTotal($count);
    }
}
