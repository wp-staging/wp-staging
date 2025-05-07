<?php

namespace WPStaging\Backup;

/**
 * This class is responsible for holding the glitch reasons for the backup process.
 * Glitch is something that unexpected happened during the backup process but was automatically fixed.
 * Analytics are hard to track, that is why we use `Glitch Modal` so user can explicitly report us when this happens.
 *
 * @todo Refactor the slow backup creation logic to use the `Glitch` class and set the reason in FinishBackupTask
 */
class BackupGlitchReason
{
    /** @var string */
    const SLOW_BACKUP_CREATION = 'REASON_SLOW_BACKUP_CREATION';

    /** @var string */
    const WRONG_TOTAL_FILES_COUNT = 'REASON_WRONG_TOTAL_FILES_COUNT';
}
