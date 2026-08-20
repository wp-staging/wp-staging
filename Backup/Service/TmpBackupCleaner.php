<?php

namespace WPStaging\Backup\Service;

use DirectoryIterator;
use Exception;
use WPStaging\Framework\Network\RemoteDownloader;




class TmpBackupCleaner
{








    public function clean(string $directory, int $maxAge = 0, int $now = 0): int
    {
        $directory = rtrim($directory, '/\\');
        if (!is_dir($directory)) {
            return 0;
        }

        try {
            $iterator = new DirectoryIterator($directory);
        } catch (Exception $e) {
            return 0;
        }

        $deletedFiles = 0;
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                continue;
            }

            if (!$this->isTmpBackup($fileInfo->getFilename())) {
                continue;
            }

            $path = $fileInfo->getPathname();
            if ($maxAge > 0 && !$this->isOlderThan($path, $maxAge, $now)) {
                continue;
            }

            if (@unlink($path)) {
                $deletedFiles++;
            }
        }

        return $deletedFiles;
    }







    private function isTmpBackup(string $filename): bool
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        if ($extension === Archiver::TMP_BACKUP_EXTENSION) {
            return true;
        }

        if ($extension !== RemoteDownloader::UPLOADING_EXTENSION) {
            return false;
        }

        $filenameWithoutUploading = pathinfo($filename, PATHINFO_FILENAME);
        $innerExtension           = pathinfo($filenameWithoutUploading, PATHINFO_EXTENSION);

        return $innerExtension === Archiver::TMP_BACKUP_EXTENSION;
    }









    private function isOlderThan(string $path, int $maxAge, int $now): bool
    {
        if ($now <= 0) {
            $now = time();
        }

        $mtime = @filemtime($path);
        if ($mtime === false) {
            return false;
        }

        return ($now - $mtime) >= $maxAge;
    }
}
