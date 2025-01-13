<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use SplFileInfo;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;

class RestoreOtherFilesInWpContentTask extends FileRestoreTask
{
    /**
     * Old filter, cannot be renamed to new pattern
     * @var string
     */
    const FILTER_EXCLUDE_OTHER_FILES_DURING_RESTORE = 'wpstg.backup.restore.exclude.other.files';

    /** @var string */
    const FILTER_REPLACE_EXISTING_OTHER_FILES = 'wpstg.backup.restore.replace_existing_other_files';

    /**
     * Old filter, cannot be renamed to new pattern
     * @var string
     */
    const FILTER_KEEP_EXISTING_OTHER_FILES = 'wpstg.backup.restore.keepExistingOtherFiles';

    public static function getTaskName(): string
    {
        return 'backup_restore_wp_content';
    }

    public static function getTaskTitle(): string
    {
        return 'Restoring Other Files in wp-content';
    }

    protected function isSkipped(): bool
    {
        return $this->isBackupPartSkipped(PartIdentifier::WP_CONTENT_PART_IDENTIFIER);
    }

    /**
     * @return array
     */
    protected function getParts(): array
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getOthersParts();
    }

    /**
     * The most critical step because it has to run in one request
     * @return void
     */
    protected function buildQueue()
    {
        $this->cleanUpExistingFiles();
        $this->moveBackupFilesToDestination();
    }

    /**
     * Clean up all files and folders in root level of wp-content folder that do not exist in:
     * plugins, mu-plugins, uploads, themes
     *
     * This is to ensure the restored site is identical to the backup
     *
     * @return void
     */
    protected function cleanUpExistingFiles()
    {
        // Early bail if subsite restore
        if ($this->isRestoreOnSubsite()) {
            return;
        }

        // Don't delete existing files if filter is set to true
        if (Hooks::applyFilters(self::FILTER_KEEP_EXISTING_OTHER_FILES, false)) {
            return;
        }

        $wpContentDir = $this->directory->getWpContentDirectory();

        $wpContentIt = new \DirectoryIterator($wpContentDir);

        foreach ($wpContentIt as $files) {
            if ($files->isLink() || $this->isDot($files)) {
                continue;
            }

            if ($files->isFile()) {
                $absoluteFilePath = $files->getRealPath();
                $fileName         = str_replace($wpContentDir, '', $absoluteFilePath);
                if ($fileName === 'debug.log' || $fileName === 'index.php') {
                    continue;
                }

                if (in_array($fileName, PartIdentifier::DROP_IN_FILES, true)) {
                    $this->jobDataDto->addFileChecksum($fileName, sha1_file($absoluteFilePath));
                    continue;
                }

                $this->enqueueDelete($absoluteFilePath);
            }

            if ($files->isDir()) {
                $normalizedPath                  = $this->filesystem->normalizePath($files->getPathname(), true);
                $defaultWordPressFoldersWithLang = $this->getDefaultWordPressDirectoriesWithLang();
                if (!in_array($normalizedPath, $defaultWordPressFoldersWithLang)) {
                    $this->enqueueDelete($normalizedPath);
                }
            }
        }
    }

    /**
     * @return void
     */
    protected function moveBackupFilesToDestination()
    {
        try {
            $otherFilesToRestore = $this->getOtherFilesToRestore();
        } catch (\Exception $e) {
            // Folder does not exist. Likely there are no other files in wp-content to restore.
            $otherFilesToRestore = [];
        }

        $destinationDir = $this->directory->getWpContentDirectory();

        try {
            $existingOtherFiles = $this->getExistingOtherFiles($destinationDir);
        } catch (\Exception $e) {
            $existingOtherFiles = [];
        }

        foreach ($otherFilesToRestore as $relativePath => $absSourcePath) {
            $absDestPath = $destinationDir . $relativePath;

            /**
             * Scenario: Skip restoring drop-ins whose destination is symlink and the site is hosted on WordPress.com
             */
            if ($this->isSiteHostedOnWordPressCom && is_link($absDestPath)) {
                continue;
            }

            if ($this->isExcludedOtherFile($absDestPath) || $this->isExcludedFile($absDestPath)) {
                continue;
            }

            /**
             * Scenario: Rename drop-in files if their checksums differ between the existing files and the backup files
             */
            if (in_array($relativePath, PartIdentifier::DROP_IN_FILES, true) && sha1_file($absSourcePath) !== $this->jobDataDto->getFileChecksum($relativePath)) {
                $this->logger->warning("$relativePath checksum does not match. Restoring $relativePath as wpstg_bak.$relativePath to avoid issues.");

                if ($relativePath === 'object-cache.php') {
                    $this->jobDataDto->setObjectCacheSkipped(true);
                }

                $this->enqueueMove($absSourcePath, $destinationDir . 'wpstg_bak.' . $relativePath);
                continue;
            }


            /**
             * Scenario: Restoring a file that already exists
             * If subsite restore and no filter is used to override the behaviour then preserve existing file
             * Otherwise:
             * 1. Replace the file
             */
            if (array_key_exists($relativePath, $existingOtherFiles)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_OTHER_FILES, false)) {
                    continue;
                }

                $this->enqueueMove($absSourcePath, $absDestPath);
                continue;
            }

            /**
             * Scenario 2: Restoring a other file that does not yet exist
             */
            $this->enqueueMove($absSourcePath, $absDestPath);
        }
    }

    /**
     * Skip these files from restoring.
     * Note: These files will still be cleaned up from the root of wp-content if they already exist while restoring.
     * @param string $excludedFilePath
     * @return bool
     */
    protected function isExcludedOtherFile($excludedFilePath)
    {
        $excludedFiles = apply_filters_deprecated(
            self::FILTER_EXCLUDE_OTHER_FILES_DURING_RESTORE, // filter name
            [[]], // old args that used to be passed to apply_filters().
            '5.9.1', // version from which it is deprecated.
            self::FILTER_EXCLUDE_FILES_DURING_RESTORE, // new filter to use
            sprintf('This filter will be removed in the upcoming version, use %s filter instead.', self::FILTER_EXCLUDE_FILES_DURING_RESTORE)
        );

        foreach ($excludedFiles as $excludedFile) {
            if (strpos(wp_normalize_path($excludedFilePath), wp_normalize_path($excludedFile)) > 0) {
                return true;
            }
        }

        return false;
    }

    protected function isDot(SplFileInfo $fileInfo): bool
    {
        return $fileInfo->getBasename() === '.' || $fileInfo->getBasename() === '..';
    }

    /**
     * @return string[]
     */
    protected function getDefaultWordPressDirectoriesWithLang(): array
    {
        return array_merge(
            $this->directory->getDefaultWordPressFolders(),
            [
                $this->directory->getLangsDirectory(),
                $this->directory->getPluginWpContentDirectory(),
                trailingslashit($this->directory->getStagingSiteDirectoryInsideWpcontent($createDir = false))
            ]
        );
    }

    /**
     * @return array An array of paths of [other] files found in the root of the temporary extracted wp-content backup folder,
     *               where the index is the path relative to the wp-content folder, and the value is the absolute path.
     * @example [
     *              'debug.log' => '/var/www/single/wp-content/uploads/wp-staging/tmp/restore/655bb61a54f5/wpstg_c_/debug.log',
     *              'custom-folder/custom-file.png' => '/var/www/single/wp-content/uploads/wp-staging/tmp/restore/655bb61a54f5/wpstg_c_/custom-folder/custom-file.png',
     *          ]
     *
     */
    private function getOtherFilesToRestore(): array
    {
        $path = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_WP_CONTENT;
        $path = trailingslashit($path);

        return $this->filesystem->findFilesInDir($path);
    }

    /**
     * @param string $path
     * @return array An array of paths of existing other files.
     */
    private function getExistingOtherFiles(string $path): array
    {
        // If not a restore on subsite then return empty array
        if (!$this->isRestoreOnSubsite()) {
            return [];
        }

        $path = trailingslashit($path);
        $path = $this->filesystem->normalizePath($path);

        $files = [];

        $this->filesystem->setDirectory($path)
            ->setDotSkip()
            ->setExcludePaths($this->getDefaultWordPressDirectoriesWithLang())
            ->setRecursive(true);

        $iterator = $this->filesystem->get();

        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            // Early bail: We don't want dots, links or anything that is not a file.
            if (!$item->isFile() || $item->isLink()) {
                continue;
            }

            $pathName = $this->filesystem->normalizePath($item->getPathname());

            $relativePath = str_replace($path, '', $pathName);

            $files[$relativePath] = $pathName;
        }

        return $files;
    }
}
