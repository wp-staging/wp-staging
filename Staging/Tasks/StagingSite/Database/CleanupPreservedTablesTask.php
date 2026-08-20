<?php

namespace WPStaging\Staging\Tasks\StagingSite\Database;

use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Staging\Dto\Job\StagingSiteJobsDataDto;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Staging\Traits\WithStagingDatabase;




class CleanupPreservedTablesTask extends StagingTask
{
    use WithStagingDatabase;




    protected $jobDataDto; // @phpstan-ignore-line

    public static function getTaskName()
    {
        return 'staging_cleanup_preserved_tables';
    }

    public static function getTaskTitle()
    {
        return 'Cleaning Up Preserved Tables';
    }




    public function execute()
    {
        $this->setup();

        $prefix = DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP;
        if ($this->tableService->deleteTablesStartWith($prefix, [], true)) {
            $this->logger->info(sprintf('Tables with preserved prefix "%s" successfully cleaned up.', esc_html($prefix)));

            return $this->generateResponse();
        }

        $this->logger->warning(sprintf('Tables with preserved prefix "%s" were not successfully cleaned up.', esc_html($prefix)));

        return $this->generateResponse();
    }




    protected function setup()
    {
        $this->initStagingDatabase($this->jobDataDto->getStagingSite());

        if ($this->tableService === null) {
            $this->tableService = new TableService($this->stagingDb);
        }
    }
}
