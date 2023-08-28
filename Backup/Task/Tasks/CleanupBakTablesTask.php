<?php

namespace WPStaging\Backup\Task\Tasks;

use WPStaging\Backup\Ajax\Restore\PrepareRestore;

class CleanupBakTablesTask extends CleanupTmpTablesTask
{
    /**
     * Can be either wpstgtmp_ or wpstgbak_
     *
     * @return string
     */
    public static function getTempTableType(): string
    {
        return PrepareRestore::TMP_DATABASE_PREFIX_TO_DROP;
    }
}
