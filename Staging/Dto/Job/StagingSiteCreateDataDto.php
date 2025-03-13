<?php

namespace WPStaging\Staging\Dto\Job;

use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Staging\Interfaces\AdvanceStagingOptionsInterface;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Staging\Traits\StagingDatabaseDtoTrait;
use WPStaging\Staging\Traits\StagingOperationDtoTrait;
use WPStaging\Staging\Traits\WithAdvanceStagingOptions;
use WPStaging\Staging\Traits\WithStagingSiteDto;

class StagingSiteCreateDataDto extends JobDataDto implements StagingDatabaseDtoInterface, StagingSiteDtoInterface, StagingOperationDtoInterface, AdvanceStagingOptionsInterface
{
    use WithAdvanceStagingOptions, WithStagingSiteDto, StagingOperationDtoTrait, StagingDatabaseDtoTrait {
        StagingOperationDtoTrait::setExcludedTables insteadof StagingDatabaseDtoTrait;
        StagingOperationDtoTrait::getExcludedTables insteadof StagingDatabaseDtoTrait;
        WithAdvanceStagingOptions::getDatabasePrefix insteadof StagingDatabaseDtoTrait;
        WithAdvanceStagingOptions::setDatabasePrefix insteadof StagingDatabaseDtoTrait;
    }

    /** @var string */
    private $cloneId = '';

    /** @var string */
    private $name = '';

    /**
     * @param string $cloneId
     * @return void
     */
    public function setCloneId(string $cloneId)
    {
        $this->cloneId = $cloneId;
    }

    /**
     * @return string
     */
    public function getCloneId(): string
    {
        return $this->cloneId;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
