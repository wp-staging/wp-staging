<?php

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Framework\Exceptions\WPStagingException;

class DiskNotWritableException extends WPStagingException
{
    public static function fileNotWritable($file)
    {
        $message = sprintf(__('We cannot proceed, as we could not write files to disk. Please check if the file %s is writeable, and if there\'s enough free disk space on the server.', 'wp-staging'), $file);

        // 32 bits PHP
        if (PHP_INT_SIZE === 4) {
            $message .= ' ' . __('You are running a 32-bit version of PHP, which is heavily obsolete and cannot handle any file over 2GB. Please ask your hosting company to upgrade you to a 64-bit PHP installation.', 'wp-staging');
        }

        return new self($message, 100);
    }
    public static function diskNotWritable()
    {
        $message = __('We cannot proceed, as we could not write files to disk. It is likely that the server disk is full, the maximum number of files were reached (inode limit) or there is no write permission to directory wp-content/uploads. Please free up disk space on the server or correct the folder permission to 755.', 'wp-staging');

        // 32 bits PHP
        if (PHP_INT_SIZE === 4) {
            $message .= ' ' . __('You are running a 32-bit version of PHP, which is heavily obsolete and cannot handle any file over 2GB. Please ask your hosting company to upgrade you to a 64-bit PHP installation.', 'wp-staging');
        }

        return new self($message, 100);
    }

    public static function willExceedFreeDiskSpace($neededBytes)
    {
        return new self(sprintf(__('Not enough disk space. Please free up at least %s in the server and try again.', 'wp-staging'), size_format($neededBytes)), 200);
    }
}
