<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;

class RestoreOtherFilesInWpContentTask extends FileRestoreTask
{
    public static function getTaskName()
    {
        return 'backup_restore_wp_content';
    }

    public static function getTaskTitle()
    {
        return 'Restoring Other Files in wp-content';
    }

    /**
     * @inheritDoc
     */
    protected function getParts()
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getOthersParts();
    }

    /**
     * The most critical step because it has to run in one request
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
        // Don't delete existing files if filter is set to true
        if (apply_filters('wpstg.backup.restore.keepExistingOtherFiles', false)) {
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
                $fileName = str_replace($wpContentDir, '', $absoluteFilePath);
                if ($fileName === 'debug.log' || $fileName === 'index.php') {
                    continue;
                }

                if ($fileName === 'object-cache.php') {
                    $this->jobDataDto->addFileChecksum('object-cache.php', sha1_file($absoluteFilePath));
                }

                $this->enqueueDelete($absoluteFilePath);
            }

            if ($files->isDir()) {
                $normalizedPath = $this->filesystem->normalizePath($files->getPathname(), true);
                $defaultWordPressFoldersWithLang = array_merge($this->directory->getDefaultWordPressFolders(), [$this->directory->getLangsDirectory(), trailingslashit($this->directory->getStagingSiteDirectoryInsideWpcontent($createDir = false))]);
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

        $destinationWpContentDir = $this->directory->getWpContentDirectory();

        foreach ($otherFilesToRestore as $relativePath => $absSourcePath) {
            $absDestPath = $destinationWpContentDir . $relativePath;

            if ($this->isExcludedOtherFile($absDestPath)) {
                continue;
            }

            /**
             * Scenario: Skip restoring drop-ins whose destination is symlink and the site is hosted on WordPress.com
             */
            if ($this->isSiteHostedOnWordPressCom && is_link($absDestPath)) {
                continue;
            }

            if ($relativePath === 'object-cache.php' && sha1_file($absSourcePath) !== $this->jobDataDto->getFileChecksum('object-cache.php')) {
                $this->logger->warning('object-cache.php checksum does not match. Skipped restoring object-cache to avoid issues.');
                $this->jobDataDto->setObjectCacheSkipped(true);
                continue;
            }

            /*
             * Scenario: Restoring another file that exists or do not exist
             * 1. Overwrite conflicting files with what's in the backup
             */
            $this->enqueueMove($absSourcePath, $absDestPath);
        }
    }

    /**
     * Skip these files from restoring.
     * Note: These files will still be cleaned up from the root of wp-content if they already exist while restoring.
     * @param $excludedFilePath
     * @return bool
     */
    protected function isExcludedOtherFile($excludedFilePath)
    {
        $excludedFiles = apply_filters('wpstg.backup.restore.exclude.other.files', []);

        foreach ($excludedFiles as $excludedFile) {
            if (strpos(wp_normalize_path($excludedFilePath), wp_normalize_path($excludedFile)) > 0) {
                return true;
            }
        }

        return false;
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
    private function getOtherFilesToRestore()
    {
        $path = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_WP_CONTENT;
        $path = trailingslashit($path);

        return $this->filesystem->findFilesInDir($path);
    }

    protected function isDot(\SplFileInfo $fileInfo)
    {
        return $fileInfo->getBasename() === '.' || $fileInfo->getBasename() === '..';
    }
}
