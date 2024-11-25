<?php

namespace WPStaging\Backup\Task\Tasks;

use WPStaging\Backup\Service\Database\DatabaseImporter;

class CleanupBakTablesTask extends CleanupTmpTablesTask
{
    /**
     * Can be either wpstgtmp_ or wpstgbak_
     *
     * @return string
     */
    public static function getTempTableType(): string
    {
        return DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP;
    }
}
