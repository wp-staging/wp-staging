<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use SplFileInfo;
use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Framework\Facades\Hooks;

class RestoreOtherFilesInWpContentTask extends FileRestoreTask
{




    const FILTER_EXCLUDE_OTHER_FILES_DURING_RESTORE = 'wpstg.backup.restore.exclude.other.files';

 
    const FILTER_REPLACE_EXISTING_OTHER_FILES = 'wpstg.backup.restore.replace_existing_other_files';





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




    protected function getParts(): array
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getOthersParts();
    }





    protected function buildQueue()
    {
        $this->cleanUpExistingFiles();
        $this->moveBackupFilesToDestination();
    }









    protected function cleanUpExistingFiles()
    {
 
        if ($this->isRestoreOnSubsite()) {
            return;
        }

 
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
                $fileName         = $files->getFilename();
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




    protected function moveBackupFilesToDestination()
    {
        try {
            $otherFilesToRestore = $this->getOtherFilesToRestore();
        } catch (\Exception $e) {
 
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




            if ($this->isSiteHostedOnWordPressCom && is_link($absDestPath)) {
                continue;
            }

            if ($this->isExcludedOtherFile($absDestPath) || $this->isExcludedFile($absDestPath)) {
                continue;
            }

 
            if ($relativePath === 'debug.log' || $relativePath === 'index.php') {
                continue;
            }




            if (in_array($relativePath, PartIdentifier::DROP_IN_FILES, true) && sha1_file($absSourcePath) !== $this->jobDataDto->getFileChecksum($relativePath)) {
                $this->logger->warning("$relativePath checksum does not match. Restoring $relativePath as wpstg_bak.$relativePath to avoid issues.");

                if ($relativePath === 'object-cache.php') {
                    $this->jobDataDto->setObjectCacheSkipped(true);
                }

                $this->enqueueMove($absSourcePath, $destinationDir . 'wpstg_bak.' . $relativePath);
                continue;
            }








            if (array_key_exists($relativePath, $existingOtherFiles)) {
                if ($this->isRestoreOnSubsite() && Hooks::applyFilters(self::FILTER_REPLACE_EXISTING_OTHER_FILES, false)) {
                    continue;
                }

                $this->enqueueMove($absSourcePath, $absDestPath);
                continue;
            }




            $this->enqueueMove($absSourcePath, $absDestPath);
        }
    }







    protected function isExcludedOtherFile($excludedFilePath)
    {
        $excludedFiles = apply_filters_deprecated(
            self::FILTER_EXCLUDE_OTHER_FILES_DURING_RESTORE, 
            [[]], 
            '5.9.1', 
            self::FILTER_EXCLUDE_FILES_DURING_RESTORE, 
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




    protected function getDefaultWordPressDirectoriesWithLang(): array
    {
        return array_merge(
            $this->directory->getDefaultWordPressFolders(),
            [
                $this->directory->getLangsDirectory(),
                $this->directory->getPluginWpContentDirectory(),
                trailingslashit($this->directory->getStagingSiteDirectoryInsideWpcontent($createDir = false)),
            ]
        );
    }










    private function getOtherFilesToRestore(): array
    {
        $path = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_WP_CONTENT;
        $path = trailingslashit($path);

        return $this->filesystem->findFilesInDir($path);
    }





    private function getExistingOtherFiles(string $path): array
    {
 
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

 
        foreach ($iterator as $item) {
 
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
