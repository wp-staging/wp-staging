<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use WPStaging\Framework\Filesystem\PartIdentifier;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;

class RestoreOtherFilesInWpRootTask extends FileRestoreTask
{
    public static function getTaskName(): string
    {
        return 'backup_restore_wp_root';
    }

    public static function getTaskTitle(): string
    {
        return 'Restoring Other Files in WP root';
    }

    protected function isSkipped(): bool
    {
        return $this->isBackupPartSkipped(PartIdentifier::WP_ROOT_PART_IDENTIFIER);
    }




    protected function getParts(): array
    {
        return $this->jobDataDto->getBackupMetadata()->getMultipartMetadata()->getOtherWpRootParts();
    }






    protected function buildQueue()
    {
 
        $dirAdapter = WPStaging::make(Directory::class);
        if (is_writeable($dirAdapter->getAbsPath())) {
            $this->moveBackupFilesToDestination();
        } else {
            $this->logger->info($this->getTaskTitle() . ': Skipped - the root dir is not writable.');
        }
    }




    protected function moveBackupFilesToDestination()
    {
        try {
            $otherFilesToRestore = $this->getOtherRootFilesToRestore();
        } catch (\Exception $e) {
 
            $otherFilesToRestore = [];
        }

        $destinationWpRootDir = $this->directory->getAbsPath();

        foreach ($otherFilesToRestore as $relativePath => $absSourcePath) {
            $absDestPath = $destinationWpRootDir . $relativePath;

            if ($this->isExcludedFile($absDestPath)) {
                continue;
            }





            $this->enqueueMove($absSourcePath, $absDestPath);
        }
    }





    protected function isDot(\SplFileInfo $fileInfo): bool
    {
        return $fileInfo->getBasename() === '.' || $fileInfo->getBasename() === '..';
    }










    private function getOtherRootFilesToRestore(): array
    {
        $path = $this->jobDataDto->getTmpDirectory() . PathIdentifier::IDENTIFIER_ABSPATH;
        $path = trailingslashit($path);

        return $this->filesystem->findFilesInDir($path);
    }
}
