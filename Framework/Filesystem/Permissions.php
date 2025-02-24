<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Framework\Traits\ApplyFiltersTrait;

class Permissions
{
    use ApplyFiltersTrait;

    /** @var string */
    const FILTER_FOLDER_PERMISSION = 'wpstg_folder_permission';

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
            return self::DEFAULT_FILE_PERMISSION;
        }

        if ($this->isValidPermission(FS_CHMOD_FILE)) {
            return FS_CHMOD_FILE;
        }

        return self::DEFAULT_FILE_PERMISSION;
    }

    private function isValidPermission(int $permission): bool
    {
        // check if it is octal?
        if (!preg_match('/^[0-7]+$/', ((string)$permission))) {
            return false;
        }

        if (decoct(octdec((string)$permission)) !== (string)$permission) {
            return false;
        }

        return $permission >= 0 && $permission <= 0777;
    }
}
