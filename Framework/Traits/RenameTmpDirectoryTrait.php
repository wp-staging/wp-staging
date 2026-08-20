<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Backend\Modules\SystemInfo;

trait RenameTmpDirectoryTrait
{









    public function renameTmpDirectory(string $validationDir)
    {
        $filesystem = WPStaging::make(Filesystem::class);
        $renamedDir = $validationDir . '_old_' . date('Y-m-d_H-i-s');
        if ($filesystem->renameDirect($validationDir, $renamedDir)) {
            return true;
        }

        $validateDirRelativePath = str_replace($filesystem->normalizePath(ABSPATH, true), '', $filesystem->normalizePath($validationDir, true));
        $renamedDirRelativePath  = str_replace($filesystem->normalizePath(ABSPATH, true), '', $filesystem->normalizePath($renamedDir, true));
        $phpUser                 = (new SystemInfo())->getPHPUser();
        if (strpos($phpUser, 'can not detect PHP user name') !== false) {
            $phpUser = '';
        }

        throw new \RuntimeException(
            sprintf(
                "Permission Error: Failed to rename temporary directory '%s' to '%s'.
                Change the folder permissions to 755 and make php user '%s' owner of it.
                Contact support@wp-staging.com for help.",
                $validateDirRelativePath,
                $renamedDirRelativePath,
                $phpUser
            )
        );
    }
}
