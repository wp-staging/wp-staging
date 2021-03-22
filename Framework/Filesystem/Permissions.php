<?php

namespace WPStaging\Framework\Filesystem;

class Permissions
{
    /**
     * @return int
     */
    public function getDirectoryOctal()
    {
        $octal = 0755;
        if (defined('FS_CHMOD_DIR')) {
            $octal = FS_CHMOD_DIR;
        }

        return apply_filters('wpstg_folder_permission', $octal);
    }

    /**
     * @return int
     */
    public function getFilesOctal()
    {
        if (defined('FS_CHMOD_FILE')) {
            return FS_CHMOD_FILE;
        }

        return 0644;
    }
}
