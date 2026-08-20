<?php

namespace WPStaging\Staging\Tasks\StagingSite\Database;




class CleanupExistingPreservedTablesTask extends CleanupPreservedTablesTask
{
    public static function getTaskName()
    {
        return 'staging_cleanup_existing_preserved_tables';
    }

    public static function getTaskTitle()
    {
        return 'Cleaning Up Existing Preserved Tables';
    }
}
