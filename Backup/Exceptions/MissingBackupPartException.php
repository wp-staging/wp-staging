<?php

namespace WPStaging\Backup\Exceptions;






class MissingBackupPartException extends BackupRuntimeException
{
    const CODE_MISSING_BACKUP_PART = 110;





    public static function forPartIndex(int $index): MissingBackupPartException
    {
        return new self(
            sprintf('Backup part %d is missing from the job data. The backup could not be finalized.', $index),
            self::CODE_MISSING_BACKUP_PART
        );
    }
}
