<?php

namespace WPStaging\Backup\Task\Tasks\JobBackup\Exceptions;

use WPStaging\Framework\Exceptions\WPStagingException;

class CompressBackupException extends WPStagingException
{
    public static function noFilesInIndex()
    {
        return new self('No files to compress.');
    }
}
