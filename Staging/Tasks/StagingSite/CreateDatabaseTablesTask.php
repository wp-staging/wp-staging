<?php

namespace WPStaging\Staging\Tasks\StagingSite;

use Exception;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Service\Database\TableCreateService;
use WPStaging\Staging\Traits\WithStagingDatabase;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Staging\Tasks\StagingTask;

class CreateDatabaseTablesTask extends StagingTask
{
    use WithStagingDatabase;

    /** @var TableCreateService */
    protected $tableCreateService;

    /** @var array */
    protected $tables = [];

    /** @var JobDataDto|StagingOperationDtoInterface|StagingDatabaseDtoInterface|StagingSiteDtoInterface $jobDataDto */
    protected $jobDataDto; // @phpstan-ignore-line

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, TableCreateService $tableCreateService)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->tableCreateService = $tableCreateService;
    }

    public static function getTaskName()
    {
        return 'staging_creating_tables';
    }

    public static function getTaskTitle()
    {
        return 'Creating Database Tables';
    }

    /**
     * @return TaskResponseDto
     * @throws Exception
     */
    public function execute()
    {
        $this->setup();

        while (!$this->stepsDto->isFinished() && !$this->isThreshold()) {
            $srcTable  = $this->tables[$this->stepsDto->getCurrent()];
            $destTable = $this->tableCreateService->getDestinationTable($srcTable);

            $this->tableCreateService->createStagingSiteTable($srcTable, $destTable);
            $this->jobDataDto->addStagingTable($srcTable, $destTable);

            $this->stepsDto->incrementCurrentStep();
        }

        if ($this->stepsDto->isFinished()) {
            $this->logger->info('All tables created on staging site...');
        }

        return $this->generateResponse(false);
    }

    /**
     * @return void
     */
    protected function setup()
    {
        $this->initStagingDatabase($this->jobDataDto->getStagingSite());
        $this->tables = $this->jobDataDto->getSelectedTables();
        $this->tableCreateService->setup($this->logger, $this->stagingDb);
        if (!$this->stepsDto->getTotal()) {
            $selectedTables = new SelectedTables();
            $selectedTables->setAllTablesExcluded($this->jobDataDto->getAllTablesExcluded());
            $this->tables = $selectedTables->getSelectedTables();
            $this->stepsDto->setTotal(count($this->tables));
            $this->jobDataDto->setSelectedTables($this->tables);
        }
    }
}
