<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Traits\ApplyFiltersTrait;

class Permissions
{
    use ApplyFiltersTrait;

    /** @var string */
    const FILTER_FOLDER_PERMISSION = 'wpstg_folder_permission';

    /** @var string */
    const FILTER_FILE_PERMISSION = 'wpstg_file_permission';

    /** @var int */
    const DEFAULT_FILE_PERMISSION = 0644;

    /** @var int */
    const DEFAULT_DIR_PERMISSION = 0755;

    /**
     * @return int
     */
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

    /**
     * @param string $filePath Absolute path to the file, or empty string when no path is known.
     *                         Passed as a second arg to wpstg_file_permission; callbacks that
     *                         want per-file control must register with accepted_args = 2.
     * @return int
     */
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

    /**
     * @param int $permission
     * @return bool
     */
    private function isValidPermission(int $permission): bool
    {
        return $permission >= 0 && $permission <= 0777;
    }
}
