<?php

namespace WPStaging\Framework\Job\Task\Tasks;

use WPStaging\Backup\Service\Database\DatabaseImporter;

class CleanupBakTablesTask extends CleanupTmpTablesTask
{





    public static function getTempTableType(): string
    {
        return DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP;
    }
}
