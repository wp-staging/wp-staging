<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Backup\Task\FileRestoreTask;

trait RestoreFileExclusionTrait
{
    /**
     * Skip these files from restoring.
     * @param string $filePath file that is being processed.
     * @param array $defaultExcluded default files excluded will be sent as filter param.
     *
     * @return bool
     */
    protected function isExcludedFile(string $filePath, array $defaultExcluded = []): bool
    {
        $normalizedFilePath     = wp_normalize_path($filePath);
        $normalizedFilePathTrim = rtrim($normalizedFilePath, '/') . '/';

        $excludedFiles = Hooks::applyFilters(FileRestoreTask::FILTER_EXCLUDE_FILES_DURING_RESTORE, $defaultExcluded);
        foreach ($excludedFiles as $excludedFile) {
            $normalizedExcludedFile     = wp_normalize_path($excludedFile);
            $normalizedExcludedFileTrim = rtrim($normalizedExcludedFile, '/') . '/';
            if (strpos($normalizedFilePathTrim, $normalizedExcludedFileTrim) === 0) { // only exclude file/folder that begins by the value given by the filter.
                return true;
            }

            if (!$this->isFileNameFormat($normalizedFilePath) && strpos($normalizedExcludedFile, 'wp-staging') !== false && strpos($normalizedFilePath, $normalizedExcludedFile) === 0) { // skip all plugins that start with 'wp-staging*'
                return true;
            }
        }

        return false;
    }

    protected function isFileNameFormat(string $path): bool
    {
        // Check if the path contains a dot (.) which likely indicates a file with an extension
        if (strpos(basename($path), '.') !== false) {
            return true;
        }

        return false;
    }
}
