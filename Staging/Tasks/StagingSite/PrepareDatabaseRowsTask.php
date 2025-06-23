<?php

namespace WPStaging\Staging\Tasks\StagingSite;

use Exception;
use Throwable;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\Task\RowsExporterTaskDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Service\Database\RowsExporter;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class PrepareDatabaseRowsTask extends StagingTask
{
    /** @var RowsExporter */
    protected $rowsExporter;

    /** @var JobDataDto|StagingOperationDtoInterface|StagingDatabaseDtoInterface|StagingSiteDtoInterface $jobDataDto */
    protected $jobDataDto; // @phpstan-ignore-line

    /** @var RowsExporterTaskDto */
    protected $currentTaskDto;

    /** @var Directory */
    protected $directory;

    public function __construct(Directory $directory, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, RowsExporter $rowsExporter)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->rowsExporter = $rowsExporter;
        $this->directory    = $directory;
    }

    public static function getTaskName()
    {
        return 'staging_prepare_database_rows';
    }

    public static function getTaskTitle()
    {
        return 'Prepare Database Records';
    }

    /**
     * @return TaskResponseDto
     * @throws Exception
     */
    public function execute()
    {
        $this->setup();

        do {
            $this->rowsExporter->setTableIndex($this->stepsDto->getCurrent());
            if (!$this->rowsExporter->initiate()) {
                $this->stepsDto->incrementCurrentStep();
                $this->currentTaskDto->reset();
                $this->persistStepsDto();
                $this->setCurrentTaskDto($this->currentTaskDto);
                continue;
            }

            try {
                $this->rowsExporter->export();
            } catch (Throwable $exception) {
                $this->rowsExporter->unlockTables();
            }

            $exporterDto = $this->rowsExporter->getRowsExporterDto();
            $this->currentTaskDto->fromRowExporterDto($exporterDto);
            $this->setCurrentTaskDto($this->currentTaskDto);

            $srcTable = $this->rowsExporter->getTableBeingExported();
            $this->logger->info(sprintf(
                'Preparing table %s: %s of %s records',
                $srcTable,
                number_format_i18n($this->currentTaskDto->rowsOffset),
                number_format_i18n($this->currentTaskDto->totalRows)
            ));

            $this->logger->debug(sprintf(
                'Preparing table %s: Query time: %s Batch Size: %s last query json: %s',
                $srcTable,
                $this->jobDataDto->getDbRequestTime(),
                $this->jobDataDto->getBatchSize(),
                $this->jobDataDto->getLastQueryInfoJSON()
            ));

            if ($exporterDto->isFinished()) {
                $this->stepsDto->incrementCurrentStep();
                $this->currentTaskDto->reset();
                $this->jobDataDto->setTableAverageRowLength(0);
                $this->setCurrentTaskDto($this->currentTaskDto);
                $this->persistStepsDto();
            }
        } while (!$this->stepsDto->isFinished() && !$this->isThreshold());

        return $this->generateResponse(false);
    }

    /** @return string */
    protected function getCurrentTaskType(): string
    {
        return RowsExporterTaskDto::class;
    }

    /**
     * @return void
     */
    protected function setup()
    {
        $tables = $this->jobDataDto->getStagingTables();
        $this->rowsExporter->setStagingPrefix($this->jobDataDto->getDatabasePrefix());
        $this->rowsExporter->inject($this->logger, $this->jobDataDto, $this->currentTaskDto->toRowsExporterDto());
        $this->rowsExporter->setFileName($this->directory->getCacheDirectory() . $this->jobDataDto->getId() . '.wpstgdbtmp.sql');
        $this->rowsExporter->setTables($tables);
        $this->rowsExporter->setTablesToExclude($this->jobDataDto->getExcludedTables());
        $this->rowsExporter->prefixSpecialFields();
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setCurrent(0);
            $this->stepsDto->setTotal(count($tables));
        }
    }
}
