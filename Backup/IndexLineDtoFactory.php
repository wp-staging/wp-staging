<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Core\WPStaging;




class IndexLineDtoFactory
{
    public static function createForBackupFormat(bool $isBackupFormatV1): IndexLineInterface
    {
        if ($isBackupFormatV1) {
            return new BackupFileIndex();
        }

        return WPStaging::make(FileHeader::class);
    }
}
