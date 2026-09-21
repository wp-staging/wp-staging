<?php

namespace WPStaging\Staging\Tasks\StagingSite\Database;

use Exception;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Job\Traits\DatabaseDumpImportTaskTrait;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Tasks\StagingTask;
use WPStaging\Staging\Traits\WithStagingDatabase;





class ImportDatabaseRowsTask extends StagingTask
{
    use DatabaseDumpImportTaskTrait;
    use WithStagingDatabase;





    const MAX_RETRIES = 3;





    const MAX_EXECUTION_TIME_ALLOWED = 60;

 
    protected $jobDataDto; // @phpstan-ignore-line

    public static function getTaskName()
    {
        return 'staging_import_rows';
    }

    public static function getTaskTitle()
    {
        return 'Importing Database Records into Staging Site';
    }





    public function execute()
    {
        $this->initStagingDatabase($this->jobDataDto->getStagingSite());
        $this->databaseImporter->setDatabase($this->stagingDb);

        return $this->importDatabaseDumpForThisRequest($this->jobDataDto->getDatabasePrefix());
    }
}
