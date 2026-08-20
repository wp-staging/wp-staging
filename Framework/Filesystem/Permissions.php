<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Traits\ApplyFiltersTrait;

class Permissions
{
    use ApplyFiltersTrait;

 
    const FILTER_FOLDER_PERMISSION = 'wpstg_folder_permission';

 
    const FILTER_FILE_PERMISSION = 'wpstg_file_permission';

 
    const DEFAULT_FILE_PERMISSION = 0644;

 
    const DEFAULT_DIR_PERMISSION = 0755;




    public function getDirectoryOctal(): int
    {
        if (!defined('FS_CHMOD_DIR')) {
            return $this->applyFilters(self::FILTER_FOLDER_PERMISSION, self::DEFAULT_DIR_PERMISSION);
        }

        if ($this->isValidPermission(FS_CHMOD_DIR)) {
            return $this->applyFilters(self::FILTER_FOLDER_PERMISSION, FS_CHMOD_DIR);
        }

        return $this->applyFilters(self::FILTER_FOLDER_PERMISSION, self::DEFAULT_DIR_PERMISSION);
    }







    public function getFilePermission(string $filePath): int
    {
        $permission = self::DEFAULT_FILE_PERMISSION;
        if (defined('FS_CHMOD_FILE') && $this->isValidPermission(FS_CHMOD_FILE)) {
            $permission = FS_CHMOD_FILE;
        }

        $filtered = $this->applyFilters(self::FILTER_FILE_PERMISSION, $permission, $filePath);

        if (!is_int($filtered) || !$this->isValidPermission($filtered)) {
            return $permission;
        }

        return $filtered;
    }





    private function isValidPermission(int $permission): bool
    {
        return $permission >= 0 && $permission <= 0777;
    }
}
