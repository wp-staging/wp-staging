<?php

namespace WPStaging\Backup\Dto\Task\Restore;

use WPStaging\Framework\Job\Dto\AbstractTaskDto;

class RenameDatabaseTaskDto extends AbstractTaskDto
{
 
    public $tablesBeingRenamed;

 
    public $customTablesBeingRenamed;

 
    public $existingTables;

 
    public $viewsBeingRenamed;

 
    public $existingViews;

 
    public $conflictingTablesRenamed;

 
    public $nonConflictingTablesRenamed;

 
    public $customTablesRenamed;

 
    public $totalTablesToRename;

 
    public $totalTablesRenamed;

 
    public $dataToPreserve;
}
