<?php

namespace WPStaging\Backup\Dto\Task\Restore;

use WPStaging\Backup\Dto\Service\DatabaseImporterDto;
use WPStaging\Framework\Job\Dto\AbstractTaskDto;

class RestoreDatabaseTaskDto extends AbstractTaskDto
{
 
    public $subsiteId;

 
    public $tableToRestore;





    public function fromDatabaseImporterDto(DatabaseImporterDto $datatabaseImporterDto)
    {
        $this->subsiteId      = $datatabaseImporterDto->getSubsiteId();
        $this->tableToRestore = $datatabaseImporterDto->getTableToRestore();
    }
}
