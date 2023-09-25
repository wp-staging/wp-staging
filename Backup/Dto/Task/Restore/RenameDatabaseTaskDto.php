<?php

namespace WPStaging\Backup\Dto\Task\Restore;

use WPStaging\Backup\Dto\AbstractTaskDto;

class RenameDatabaseTaskDto extends AbstractTaskDto
{
    /** @var array<string> */
    public $tablesBeingRenamed;

    /** @var array<string> */
    public $existingTables;

    /** @var array<string> */
    public $viewsBeingRenamed;

    /** @var array<string> */
    public $existingViews;

    /** @var int */
    public $conflictingTablesRenamed;

    /** @var int */
    public $nonConflictingTablesRenamed;
}
