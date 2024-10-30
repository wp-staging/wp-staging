<?php

namespace WPStaging\Staging\Dto\Job;

use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingDataDtoInterface;
use WPStaging\Staging\Traits\StagingDatabaseDtoTrait;
use WPStaging\Staging\Traits\StagingDataDtoTrait;

class StagingSiteDeleteDataDto extends JobDataDto implements StagingDatabaseDtoInterface, StagingDataDtoInterface
{
    use StagingDatabaseDtoTrait;
    use StagingDataDtoTrait;

    /** @var bool */
    private $isDeletingFiles = false;

    /** @var bool */
    private $isDeletingTables = false;

    /**
     * @param bool $deletingFiles
     * @return void
     */
    public function setIsDeletingFiles(bool $deletingFiles)
    {
        $this->isDeletingFiles = $deletingFiles;
    }

    /**
     * @return bool
     */
    public function getIsDeletingFiles(): bool
    {
        return $this->isDeletingFiles;
    }

    /**
     * @param bool $deletingDatabase
     * @return void
     */
    public function setIsDeletingTables(bool $deletingDatabase)
    {
        $this->isDeletingTables = $deletingDatabase;
    }

    /**
     * @return bool
     */
    public function getIsDeletingTables(): bool
    {
        return $this->isDeletingTables;
    }
}
