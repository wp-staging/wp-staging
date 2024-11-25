<?php

namespace WPStaging\Backup\Task\Tasks;

use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Task\AbstractTask;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

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

    /**
     * Can be either wpstgtmp_ or wpstgbak_
     *
     * @return string
     */
    public static function getTempTableType(): string
    {
        return DatabaseImporter::TMP_DATABASE_PREFIX;
    }

    public static function getTaskName(): string
    {
        $cleaningType = static::getTempTableType();
        return "backup_restore_cleanup_{$cleaningType}tables";
    }

    public static function getTaskTitle(): string
    {
        return 'Cleaning Up Restore Tables';
    }

    /**
     * @return TaskResponseDto
     */
    public function execute(): TaskResponseDto
    {
        $tmpTableType = static::getTempTableType();
        $this->prepareCleanupRestoreTask($tmpTableType);

        $this->tables = $this->tableService->findTableNamesStartWith();
        $this->views = $this->tableService->findViewsNamesStartWith();

        while (!$this->isThreshold() && !$this->stepsDto->isFinished()) {
            $tableOrViewName = $this->taskQueue->dequeue();

            // Double-check we are deleting a temporary table just to be extra-careful.
            if (strpos($tableOrViewName, $tmpTableType) !== 0) {
                $this->logger->warning(sprintf(
                    '%s: Temporary table "%s" did not start with temporary prefix "%s" and was skipped.',
                    static::getTaskTitle(),
                    esc_html($tableOrViewName),
                    esc_html($tmpTableType)
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
                    '%s: Deleted temporary %s "%s".',
                    static::getTaskTitle(),
                    esc_html($label),
                    esc_html($tableOrViewName)
                ));
            } else {
                $this->logger->warning(sprintf(
                    '%s: Temporary %s "%s" was not successfully cleaned up.',
                    static::getTaskTitle(),
                    esc_html($label),
                    esc_html($tableOrViewName)
                ));
            }
        }

        if ($this->taskQueue->isFinished()) {
            $this->stepsDto->finish();

            // Successfully deleted
            $this->logger->info(sprintf(
                '%s: Tables with temporary prefix "%s" successfully cleaned up.',
                static::getTaskTitle(),
                esc_html($tmpTableType)
            ));
        }


        return $this->generateResponse(false);
    }

    /**
     * @param string $tmpTableType
     */
    public function prepareCleanupRestoreTask(string $tmpTableType)
    {
        // Early bail: Already prepared
        if ($this->stepsDto->getTotal() > 0) {
            return;
        }

        global $wpdb;

        if ($wpdb->prefix === $tmpTableType) {
            $this->logger->warning(sprintf(
                '%s: Temporary table prefix "%s" is the same as the WordPress table prefix. This is not allowed.',
                static::getTaskTitle(),
                esc_html($tmpTableType)
            ));

            return;
        }

        $tmpViews = $this->tableService->findViewsNamesStartWith($tmpTableType);
        $tmpTables = $this->tableService->findTableNamesStartWith($tmpTableType);

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
