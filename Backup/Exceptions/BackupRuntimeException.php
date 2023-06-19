<?php

/**
 * An exception thrown in the context of the Backup execution whose nature is run-time dependant.
 *
 * @package WPStaging\Backup\Exceptions
 */

namespace WPStaging\Backup\Exceptions;

use WPStaging\Framework\Exceptions\WPStagingException;

/**
 * Class RuntimeException
 *
 * @since   TBD
 *
 * @package WPStaging\Backup\Exceptions
 */
class BackupRuntimeException extends WPStagingException
{

    /**
     * Returns an instance of the Exception meant to signal the default, or filtered, Backup directory
     * cannot be created.
     *
     * @param string $dir The absolute path to the filtered Backup directory.
     *
     * @return BackupRuntimeException A reference to a ready-to-throw Exception instance.
     */
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

    /**
     * Returns an instance of the Exception meant to signal the default, or filtered, Backup directory
     * is not readable.
     *
     * @param string $dir The absolute path to the filtered Backup directory.
     *
     * @return BackupRuntimeException A reference to a ready-to-throw Exception instance.
     */
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

    /**
     * Returns an instance of the Exception meant to signal the default, or filtered, Backup directory
     * is not writeable.
     *
     * @param string $dir The absolute path to the filtered Backup directory.
     *
     * @return BackupRuntimeException A reference to a ready-to-throw Exception instance.
     */
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
