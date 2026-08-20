<?php

namespace WPStaging\Staging\Dto\Job;

use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Traits\StagingDatabaseDtoTrait;
use WPStaging\Staging\Traits\WithStagingSiteDto;

class StagingSiteDeleteDataDto extends JobDataDto implements StagingDatabaseDtoInterface, StagingSiteDtoInterface
{
    use StagingDatabaseDtoTrait;
    use WithStagingSiteDto;

 
    private $isDeletingFiles = false;

 
    private $isDeletingTables = false;





    public function setIsDeletingFiles(bool $deletingFiles)
    {
        $this->isDeletingFiles = $deletingFiles;
    }




    public function getIsDeletingFiles(): bool
    {
        return $this->isDeletingFiles;
    }





    public function setIsDeletingTables(bool $deletingDatabase)
    {
        $this->isDeletingTables = $deletingDatabase;
    }




    public function getIsDeletingTables(): bool
    {
        return $this->isDeletingTables;
    }
}
