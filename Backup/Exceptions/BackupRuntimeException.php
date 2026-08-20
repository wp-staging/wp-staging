<?php







namespace WPStaging\Backup\Exceptions;

use WPStaging\Framework\Exceptions\WPStagingException;








class BackupRuntimeException extends WPStagingException
{









    public static function cannotCreateBackupsDirectory($dir)
    {
        return new self(
            __(
                "We cannot proceed, as we could not create the Backups directory folder. It is likely that the server " .
                "disk is full or there is no write permission to the directory {$dir}." .
                "Please free up disk space on the server or correct the folder permission to 755.",
                'wp-staging'
            ),
            100
        );
    }









    public static function backupsDirectoryNotReadable($dir)
    {
        return new self(
            __(
                "We cannot proceed, as the backup directory is not readable. It is likely that there is no read permission " .
                "to the directory {$dir}." .
                " Please correct the folder permission to 755.",
                'wp-staging'
            ),
            101
        );
    }









    public static function backupsDirectoryNotWriteable($dir)
    {
        return new self(
            __(
                "We cannot proceed, as the backup directory is not writeable. It is likely that there is no write permission " .
                "to the directory {$dir}." .
                " Please correct the folder permission to 755.",
                'wp-staging'
            ),
            102
        );
    }
}
