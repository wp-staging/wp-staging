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
     * @return int
     */
    public function getFilesOctal(): int
    {
        if (!defined('FS_CHMOD_FILE')) {
            return $this->applyFilters(self::FILTER_FILE_PERMISSION, self::DEFAULT_FILE_PERMISSION);
        }

        if ($this->isValidPermission(FS_CHMOD_FILE)) {
            return $this->applyFilters(self::FILTER_FILE_PERMISSION, FS_CHMOD_FILE);
        }

        return $this->applyFilters(self::FILTER_FILE_PERMISSION, self::DEFAULT_FILE_PERMISSION);
    }

    /**
     * Validates if a permission value is within valid Unix permission range.
     *
     * Valid Unix permissions are 0 to 0777 (511 decimal).
     * Note: This validates the permission value itself, not its octal representation,
     * since octal is just a way to represent the number.
     *
     * @param int $permission The permission value to validate
     * @return bool True if permission is valid, false otherwise
     */
    private function isValidPermission(int $permission): bool
    {
        // Valid Unix permissions are 0 to 0777 (511 decimal)
        return $permission >= 0 && $permission <= 0777;
    }
}
