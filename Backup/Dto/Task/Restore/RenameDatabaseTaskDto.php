<?php

namespace WPStaging\Backup\Dto\Task\Restore;

use WPStaging\Framework\Job\Dto\AbstractTaskDto;

class RenameDatabaseTaskDto extends AbstractTaskDto
{
    /** @var string[] */
    public $tablesBeingRenamed;

    /** @var string[] */
    public $customTablesBeingRenamed;

    /** @var string[] */
    public $existingTables;

    /** @var string[] */
    public $viewsBeingRenamed;

    /** @var string[] */
    public $existingViews;

    /** @var int */
    public $conflictingTablesRenamed;

    /** @var int */
    public $nonConflictingTablesRenamed;

    /** @var int */
    public $customTablesRenamed;
}
