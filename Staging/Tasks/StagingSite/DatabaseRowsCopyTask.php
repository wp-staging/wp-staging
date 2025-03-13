<?php

namespace WPStaging\Staging\Tasks\StagingSite;

use Exception;
use Throwable;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Dto\Task\DatabaseRowsCopyTaskDto;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Service\Database\RowsCopier;
use WPStaging\Staging\Traits\WithStagingDatabase;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Staging\Tasks\StagingTask;

class DatabaseRowsCopyTask extends StagingTask
{
    use WithStagingDatabase;

    /** @var RowsCopier */
    protected $rowsCopier;

    /** @var array */
    protected $tables = [];

    /** @var JobDataDto|StagingOperationDtoInterface|StagingDatabaseDtoInterface|StagingSiteDtoInterface $jobDataDto */
    protected $jobDataDto; // @phpstan-ignore-line

    /** @var DatabaseRowsCopyTaskDto */
    protected $currentTaskDto;

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, RowsCopier $rowsCopier)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->rowsCopier = $rowsCopier;
    }

    public static function getTaskName()
    {
        return 'staging_copying_rows';
    }

    public static function getTaskTitle()
    {
        return 'Copying Database Records';
    }

    /**
     * @return TaskResponseDto
     * @throws Exception
     */
    public function execute()
    {
        $this->setup();

        do {
            $tableIndex = $this->stepsDto->getCurrent();
            $tablesInfo = $this->tables[$tableIndex];
            $srcTable   = $tablesInfo['source'];
            $destTable  = $tablesInfo['destination'];

            $rowsCount = $this->rowsCopier->setTablesInfo($tableIndex, $srcTable, $destTable);
            if ($rowsCount === 0) {
                $this->stepsDto->incrementCurrentStep();
                $this->currentTaskDto->reset();
                $this->persistStepsDto();
                $this->setCurrentTaskDto($this->currentTaskDto);
                continue;
            }

            try {
                $this->rowsCopier->execute();
            } catch (Throwable $exception) {
                $this->rowsCopier->unlockTable();
            }

            $this->currentTaskDto->fromRowCopierDto($this->rowsCopier->getRowsCopierDto());
            $this->setCurrentTaskDto($this->currentTaskDto);

            $this->logger->info(sprintf(
                'Database rows copying: Table %s. Rows: %s/%s',
                $srcTable,
                number_format_i18n($this->currentTaskDto->rowsCopied),
                number_format_i18n($this->currentTaskDto->totalRows)
            ));

            $this->logger->debug(sprintf(
                'Database rows copying: Table %s. Query time: %s Batch Size: %s last query json: %s',
                $srcTable,
                $this->jobDataDto->getDbRequestTime(),
                $this->jobDataDto->getBatchSize(),
                $this->jobDataDto->getLastQueryInfoJSON()
            ));

            if ($this->rowsCopier->isTableCopyingFinished()) {
                $this->stepsDto->incrementCurrentStep();
                $this->currentTaskDto->reset();
                $this->jobDataDto->setTableAverageRowLength(0);
                $this->persistStepsDto();
            }
        } while (!$this->stepsDto->isFinished() && !$this->isThreshold());

        return $this->generateResponse(false);
    }

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return DatabaseRowsCopyTaskDto::class;
    }

    /**
     * @return void
     */
    protected function setup()
    {
        $this->initStagingDatabase($this->jobDataDto->getStagingSite());
        $this->tables = $this->jobDataDto->getStagingTables();
        $this->rowsCopier->setup($this->logger, $this->jobDataDto, $this->currentTaskDto->toRowCopierDto(), $this->stagingDb);
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal(count($this->tables));
        }
    }
}
