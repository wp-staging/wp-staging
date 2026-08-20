<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Backup\Task\FileRestoreTask;

trait RestoreFileExclusionTrait
{







    protected function isExcludedFile(string $filePath, array $defaultExcluded = []): bool
    {
        $normalizedFilePath     = wp_normalize_path($filePath);
        $normalizedFilePathTrim = rtrim($normalizedFilePath, '/') . '/';

        $excludedFiles = Hooks::applyFilters(FileRestoreTask::FILTER_EXCLUDE_FILES_DURING_RESTORE, $defaultExcluded);
        foreach ($excludedFiles as $excludedFile) {
            $normalizedExcludedFile     = wp_normalize_path($excludedFile);
            $normalizedExcludedFileTrim = rtrim($normalizedExcludedFile, '/') . '/';
            if (strpos($normalizedFilePathTrim, $normalizedExcludedFileTrim) === 0) { 
                return true;
            }

            if (!$this->isFileNameFormat($normalizedFilePath) && strpos($normalizedExcludedFile, 'wp-staging') !== false && strpos($normalizedFilePath, $normalizedExcludedFile) === 0) { 
                return true;
            }
        }

        return false;
    }

    protected function isFileNameFormat(string $path): bool
    {
 
        if (strpos(basename($path), '.') !== false) {
            return true;
        }

        return false;
    }
}
