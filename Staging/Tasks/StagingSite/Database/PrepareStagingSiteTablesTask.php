<?php

namespace WPStaging\Staging\Tasks\StagingSite\Database;

use Exception;
use WPStaging\Backup\Transfer\TransferSessionTable;
use WPStaging\Framework\Database\SelectedTables;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingNetworkDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Service\Database\TableCreateService;
use WPStaging\Staging\Traits\WithStagingDatabase;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Staging\Tasks\StagingTask;






class PrepareStagingSiteTablesTask extends StagingTask
{
    use WithStagingDatabase;

 
    protected $tableCreateService;

 
    protected $tables = [];

 
    protected $jobDataDto; // @phpstan-ignore-line

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, TableCreateService $tableCreateService)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->tableCreateService = $tableCreateService;
    }

    public static function getTaskName()
    {
        return 'staging_cleanup_tables';
    }

    public static function getTaskTitle()
    {
        return 'Backup existing tables and cleaning up database';
    }





    public function execute()
    {
        $this->setup();

        while (!$this->stepsDto->isFinished() && !$this->isThreshold()) {
            $srcTable  = $this->tables[$this->stepsDto->getCurrent()];
            $destTable = $this->tableCreateService->getDestinationTable($srcTable);

            if ($this->isExistingDownloadLinkTable($srcTable, $destTable)) {
                $this->stepsDto->incrementCurrentStep();
                continue;
            }

            $this->preserveDestinationTable($srcTable, $destTable);
            $this->tableCreateService->createDestinationTable($srcTable, $destTable);
            $this->jobDataDto->addStagingTable($srcTable, $destTable);

            $this->stepsDto->incrementCurrentStep();
        }

        if ($this->stepsDto->isFinished()) {
            $this->logger->info('All tables created on staging site...');
        }

        $this->persistJobDataDto();

        return $this->generateResponse(false);
    }




    protected function setup()
    {
        $this->initStagingDatabase($this->jobDataDto->getStagingSite());
        $this->tables = $this->jobDataDto->getSelectedTables();
        $this->tableCreateService->setIsResetExistingTables($this->jobDataDto->getIsUpdateOrResetJob());
        $this->tableCreateService->setup($this->logger, $this->stagingDb);
        if (!$this->stepsDto->getTotal()) {
            $isFirstRequest = empty($this->tables);
            $selectedTables = new SelectedTables();
            $selectedTables->setIncludedTables($this->jobDataDto->getIncludedTables());
            $selectedTables->setExcludedTables($this->jobDataDto->getExcludedTables());
            $selectedTables->setSelectedTablesWithoutPrefix($this->jobDataDto->getNonSiteTables());
            $selectedTables->setAllTablesExcluded($this->jobDataDto->getAllTablesExcluded());
            $this->tables = $selectedTables->getSelectedTables($this->jobDataDto->getIsStagingNetwork());
            $this->stepsDto->setTotal(count($this->tables));
            $this->jobDataDto->setSelectedTables($this->tables);
            if ($isFirstRequest) {
                $this->tableCreateService->dropOrphanedBackupTables($this->tables);
            }

            $this->persistJobDataDto();
            $this->persistStepsDto();
        }
    }





    private function preserveDestinationTable(string $srcTable, string $destTable)
    {
        $tableWithoutPrefix = $this->tableCreateService->getTableWithoutPrefix($srcTable);
        $backupTable        = $this->tableCreateService->getPreservedTableName($tableWithoutPrefix);
        if ($this->tableCreateService->isTableExist($backupTable)) {
            $this->logger->info(sprintf("Table %s was already preserved as %s, keeping it", esc_html($destTable), esc_html($backupTable)));
            $this->jobDataDto->addPreserveTable($destTable, $backupTable);
            return;
        }

        if (!$this->tableCreateService->isTableExist($destTable)) {
            return;
        }

        $this->tableCreateService->preserveExistingTable($destTable, $tableWithoutPrefix);
        $this->jobDataDto->addPreserveTable($destTable, $backupTable);
    }





    private function isExistingDownloadLinkTable(string $srcTable, string $destTable): bool
    {
        if ($this->tableCreateService->getTableWithoutPrefix($srcTable) !== TransferSessionTable::TABLE_NAME) {
            return false;
        }

        return $this->tableCreateService->isTableExist($destTable);
    }
}
