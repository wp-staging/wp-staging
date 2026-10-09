<?php

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Exceptions\WPStagingException;




class FileSizeLimitException extends WPStagingException
{
 
    const WRITE_NOTICE_MARKER = 'errno=27';





    public static function writeNoticeReportsFileSizeLimit(string $writeNotice): bool
    {
        return strpos($writeNotice, self::WRITE_NOTICE_MARKER) !== false;
    }





    public static function forFile(string $file): self
    {
        /* translators: %s: path of the backup file that cannot grow any further */
        $message = sprintf(__('The backup file %s has reached the largest file size this server allows, so no more files can be added to it. This is not a lack of free disk space. Exclude large folders from the backup or back them up separately so that each backup file stays below that limit, or ask your hosting provider to raise it.', 'wp-staging'), $file);

        if (!WPStaging::isBasic()) {
            /* translators: %s: link to the documentation of the multipart backup filters */
            $message .= ' ' . sprintf(__('The recommended solution is a multipart backup, which splits the backup into several smaller files so that none of them reaches this limit. You turn it on, or choose a smaller part size if it is already on, with a short code snippet described here: %s', 'wp-staging'), 'https://wp-staging.com/docs/actions-and-filters/#Activate_MultiPart_Backups');
        }

        if (PHP_INT_SIZE === 4) {
            $message .= ' ' . __('You are running a 32-bit version of PHP, which is heavily obsolete and cannot handle any file over 2GB. Please ask your hosting company to upgrade you to a 64-bit PHP installation.', 'wp-staging');
        }

        return new self($message);
    }
}
