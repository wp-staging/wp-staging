<?php

namespace WPStaging\Backup;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;

class BackupDownload
{
    /**
     * @return void
     */
    public function deleteUnfinishedDownloads()
    {
        $dir       = WPStaging::make(Directory::class)->getDownloadsDirectory();
        if (!is_dir($dir)) {
            return;
        }

        $extension = ".wpstg";  // Extension of the file created when download starts.
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (strpos($file, $extension) !== false) {
                    unlink($dir . '/' . $file);
                }
            }

            closedir($dh);
        }
    }
}
