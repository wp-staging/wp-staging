<?php

namespace WPStaging\Staging\Tasks\StagingSite\Database;

use Exception;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\Task\RowsExporterTaskDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Traits\DatabaseRowsExportTaskTrait;
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
    use DatabaseRowsExportTaskTrait;

 
    protected $rowsExporter;

 
    protected $jobDataDto; // @phpstan-ignore-line

 
    protected $currentTaskDto;

 
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





    public function execute()
    {
        $this->setupRowsExporterForStagingTables();

        return $this->exportDatabaseRows();
    }
}
