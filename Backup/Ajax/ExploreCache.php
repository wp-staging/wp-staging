<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\IndexLineDtoFactory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Utils\Cache\Cache;








class ExploreCache
{
 
    const LIFETIME = 3600;

 
    const FORMAT_VERSION = 3;

 
    const TRAILER_LENGTH = 256;

 
    const ABANDONED_BUILD_AGE = 600;

 
    const WRITE_BUFFER_SIZE = 1048576;

 
    private $cache;

 
    private $pathIdentifier;

 
    private $backupFileWithFailedBuild = '';

    public function __construct(Cache $cache, PathIdentifier $pathIdentifier)
    {
        $this->cache          = $cache;
        $this->pathIdentifier = $pathIdentifier;
    }






    public function openOrBuildTreeReader(string $backupFile, BackupMetadata $metadata)
    {
        $this->configureCache($backupFile);

        $treeReader = $this->openTreeReader($backupFile);
        if ($treeReader !== null || $backupFile === $this->backupFileWithFailedBuild) {
            return $treeReader;
        }

        $isBuilt    = $this->build($backupFile, $metadata);
        $treeReader = $this->openTreeReader($backupFile);
        if (!$isBuilt && $treeReader === null) {
            $this->backupFileWithFailedBuild = $backupFile;
        }

        return $treeReader;
    }





    private function configureCache(string $backupFile)
    {
        $this->cache->setLifetime(self::LIFETIME);
        $this->cache->setFilename('backup_explore_' . md5($backupFile));
    }





    private function openTreeReader(string $backupFile)
    {
        if (!$this->cache->isValid(false)) {
            return null;
        }

        try {
            $file         = new FileObject($this->cache->getFilePath(), FileObject::MODE_READ);
            $trailerStart = $file->getSize() - self::TRAILER_LENGTH;
        } catch (\Throwable $e) {
            return null;
        }

        if ($trailerStart < strlen(Cache::PHP_HEADER) || $file->fread(strlen(Cache::PHP_HEADER)) !== Cache::PHP_HEADER) {
            return null;
        }

        $file->fseek($trailerStart);
        $trailer = json_decode(trim($file->fread(self::TRAILER_LENGTH)), true);
        if (!$this->trailerDescribesBackup($trailer, $backupFile)) {
            return null;
        }

        return new ExploreTreeReader($file, (int)$trailer['folderStart'], (int)$trailer['folderCount'], (int)$trailer['offsetTableStart'], (int)$trailer['offsetWidth']);
    }






    private function trailerDescribesBackup($trailer, string $backupFile): bool
    {
        if (!is_array($trailer) || !isset($trailer['format'], $trailer['backupModifiedTime'], $trailer['backupSize'], $trailer['folderStart'], $trailer['folderCount'], $trailer['offsetTableStart'], $trailer['offsetWidth'])) {
            return false;
        }

        if ((int)$trailer['format'] !== self::FORMAT_VERSION) {
            return false;
        }

        return (int)$trailer['backupModifiedTime'] === filemtime($backupFile) && (int)$trailer['backupSize'] === filesize($backupFile);
    }








    private function build(string $backupFile, BackupMetadata $metadata): bool
    {
        $this->deleteAbandonedBuilds();

        $backupModifiedTime = filemtime($backupFile);
        $backupSize         = filesize($backupFile);
        $buildFile          = $this->cache->getPath() . $this->cache->getFilename() . '-' . uniqid() . '.' . Cache::FILE_EXTENSION;

        try {
            $this->writeTree($buildFile, $this->collectFolders($backupFile, $metadata), $backupModifiedTime, $backupSize);
        } catch (\Throwable $e) {
            $this->deleteFile($buildFile);
            return false;
        }

        if (!$this->moveBuildIntoPlace($buildFile, $this->cache->getFilePath())) {
            $this->deleteFile($buildFile);
            return false;
        }

        return true;
    }






    protected function moveBuildIntoPlace(string $buildFile, string $cacheFile): bool
    {
        return $this->callQuietly(function () use ($buildFile, $cacheFile) {
            return rename($buildFile, $cacheFile);
        });
    }












    private function collectFolders(string $backupFile, BackupMetadata $metadata): array
    {
        $indexLineDto = IndexLineDtoFactory::createForBackupFormat($metadata->getIsBackupFormatV1());
        $fileObject   = new FileObject($backupFile, FileObject::MODE_READ);
        $fileObject->fseek((int)$metadata->getHeaderStart());

        $fileRecordsByFolder    = [];
        $subfolderNamesByFolder = ['' => ''];
        $sizeByFolder           = [];

        while ($fileObject->valid() && $fileObject->ftell() < (int)$metadata->getHeaderEnd()) {
            $indexOffset  = $fileObject->ftell();
            $rawIndexFile = $fileObject->readAndMoveNext();
            if (!$indexLineDto->isIndexLine($rawIndexFile)) {
                continue;
            }

            $backupFileIndex  = $indexLineDto->readIndexLine($rawIndexFile);
            $identifiablePath = $backupFileIndex->getIdentifiablePath();
            if (!$this->pathIdentifier->isSafeIdentifiablePath($identifiablePath)) {
                continue;
            }

            $relativePath = $this->normalizeSlashes($this->pathIdentifier->transformIdentifiableToRelativePath($identifiablePath));
            $lastSlash    = strrpos($relativePath, '/');
            $folder       = $lastSlash === false ? '' : substr($relativePath, 0, $lastSlash);
            $fileName     = $lastSlash === false ? $relativePath : substr($relativePath, $lastSlash + 1);
            if ($fileName === '') {
                continue;
            }

            if (!isset($subfolderNamesByFolder[$folder])) {
                $this->registerFolderAndAncestors($subfolderNamesByFolder, $folder);
            }

            $size       = (int)$backupFileIndex->getUncompressedSize();
            $fileRecord = $fileName . "\0" . $size . "\0" . (int)$indexOffset;
            if (isset($fileRecordsByFolder[$folder])) {
                $fileRecordsByFolder[$folder] .= '/' . $fileRecord;
                $sizeByFolder[$folder]        += $size;
                continue;
            }

            $fileRecordsByFolder[$folder] = $fileRecord;
            $sizeByFolder[$folder]        = $size;
        }

        $fileObject = null;

        return [
            'files'      => $fileRecordsByFolder,
            'subfolders' => $subfolderNamesByFolder,
            'sizes'      => $sizeByFolder,
        ];
    }






    private function registerFolderAndAncestors(array &$subfolderNamesByFolder, string $folder)
    {
        $subfolderNames = explode('/', $folder);
        $deepestIndex   = count($subfolderNames) - 1;
        $parentFolder   = '';
        foreach ($subfolderNames as $index => $subfolderName) {
            $subfolder = $index === $deepestIndex ? $folder : ExploreTreeReader::joinPath($parentFolder, $subfolderName);
            if (!isset($subfolderNamesByFolder[$subfolder])) {
                $subfolderNamesByFolder[$subfolder]     = '';
                $subfolderNamesByFolder[$parentFolder] .= ($subfolderNamesByFolder[$parentFolder] === '' ? '' : '/') . $subfolderName;
            }

            $parentFolder = $subfolder;
        }
    }








    private function writeTree(string $cacheFile, array $tree, int $backupModifiedTime, int $backupSize)
    {
        $file = new FileObject($cacheFile, FileObject::MODE_WRITE);
        list($recordLines, $recordOffsets) = $this->writeFolderEntries($file, $tree);

        $folderStart = $file->ftell();
        $this->writeOrFail($file, $recordLines);
        $recordLines = null;

        $offsetTableStart = $file->ftell();
        $offsetWidth      = strlen((string)max($recordOffsets));
        $offsetTable      = '';
        foreach ($recordOffsets as $recordOffset) {
            $offsetTable .= str_pad((string)$recordOffset, $offsetWidth, '0', STR_PAD_LEFT);
            $offsetTable  = $this->flushWhenFull($file, $offsetTable);
        }

        $this->writeOrFail($file, $offsetTable);

        $trailer = json_encode([
            'format'             => self::FORMAT_VERSION,
            'backupModifiedTime' => $backupModifiedTime,
            'backupSize'         => $backupSize,
            'folderStart'        => $folderStart,
            'folderCount'        => count($recordOffsets),
            'offsetTableStart'   => $offsetTableStart,
            'offsetWidth'        => $offsetWidth,
        ]);

        $this->writeOrFail($file, str_pad($trailer, self::TRAILER_LENGTH - 1) . "\n");
        $file = null;
    }








    private function writeFolderEntries(FileObject $file, array $tree): array
    {
        $fileRecordsByFolder    = $tree['files'];
        $subfolderNamesByFolder = $tree['subfolders'];
        list($totalFileCountByFolder, $totalSizeByFolder) = $this->sumFilesBelowEachFolder($fileRecordsByFolder, $tree['sizes']);

        $folders = array_map('strval', array_keys($subfolderNamesByFolder));
        sort($folders, SORT_STRING);

        $buffer        = Cache::PHP_HEADER;
        $recordLines   = '';
        $recordOffsets = [];
        foreach ($folders as $folder) {
            $subfolderNames = $this->splitPackedList($subfolderNamesByFolder[$folder]);
            usort($subfolderNames, 'strcasecmp');

            $fileRecords = $this->splitPackedList($fileRecordsByFolder[$folder] ?? '');
            usort($fileRecords, 'strcasecmp');

            $recordOffsets[] = strlen($recordLines);
            $recordLines    .= implode("\t", [
                ExploreTreeReader::escapeField($folder),
                $file->ftell() + strlen($buffer),
                count($subfolderNames),
                count($fileRecords),
                $totalFileCountByFolder[$folder] ?? 0,
                $totalSizeByFolder[$folder] ?? 0,
            ]) . "\n";

            foreach ($subfolderNames as $subfolderName) {
                $subfolder = ExploreTreeReader::joinPath($folder, $subfolderName);
                $items     = $this->countPackedList($subfolderNamesByFolder[$subfolder]) + $this->countPackedList($fileRecordsByFolder[$subfolder] ?? '');
                $buffer   .= ExploreTreeReader::escapeField($subfolderName) . "\t" . $items . "\n";
                $buffer    = $this->flushWhenFull($file, $buffer);
            }

            foreach ($fileRecords as $fileRecord) {
                list($fileName, $size, $indexOffset) = explode("\0", $fileRecord, 3);
                $buffer .= ExploreTreeReader::escapeField($fileName) . "\t" . $size . "\t" . $indexOffset . "\n";
                $buffer  = $this->flushWhenFull($file, $buffer);
            }
        }

        $this->writeOrFail($file, $buffer);

        return [$recordLines, $recordOffsets];
    }






    private function sumFilesBelowEachFolder(array $fileRecordsByFolder, array $sizeByFolder): array
    {
        $totalFileCountByFolder = [];
        $totalSizeByFolder      = [];
        foreach ($fileRecordsByFolder as $folder => $fileRecords) {
            $fileCount = $this->countPackedList($fileRecords);
            $size      = $sizeByFolder[$folder];
            $ancestor  = (string)$folder;
            while (true) {
                $totalFileCountByFolder[$ancestor] = ($totalFileCountByFolder[$ancestor] ?? 0) + $fileCount;
                $totalSizeByFolder[$ancestor]      = ($totalSizeByFolder[$ancestor] ?? 0) + $size;
                if ($ancestor === '') {
                    break;
                }

                $ancestor = $this->parentFolderOf($ancestor);
            }
        }

        return [$totalFileCountByFolder, $totalSizeByFolder];
    }





    private function splitPackedList(string $packedList): array
    {
        return $packedList === '' ? [] : explode('/', $packedList);
    }





    private function countPackedList(string $packedList): int
    {
        return $packedList === '' ? 0 : substr_count($packedList, '/') + 1;
    }







    private function writeOrFail(FileObject $file, string $content): int
    {
        $writtenBytes = $file->fwriteSafe($content);
        if ($writtenBytes === false) {
            throw new \RuntimeException('Could not write the backup explorer cache.');
        }

        return $writtenBytes;
    }






    private function flushWhenFull(FileObject $file, string $buffer): string
    {
        if (strlen($buffer) < self::WRITE_BUFFER_SIZE) {
            return $buffer;
        }

        $this->writeOrFail($file, $buffer);

        return '';
    }








    private function normalizeSlashes(string $path): string
    {
        return (string)preg_replace('|/+|', '/', str_replace('\\', '/', $path));
    }





    private function parentFolderOf(string $path): string
    {
        $lastSlash = strrpos($path, '/');

        return $lastSlash === false ? '' : substr($path, 0, $lastSlash);
    }






    private function deleteAbandonedBuilds()
    {
        $buildFiles = glob($this->cache->getPath() . $this->cache->getFilename() . '-*.' . Cache::FILE_EXTENSION);
        $this->callQuietly(function () use ($buildFiles) {
            foreach ($buildFiles ?: [] as $buildFile) {
                if (filemtime($buildFile) < time() - self::ABANDONED_BUILD_AGE) {
                    $this->deleteFile($buildFile);
                }
            }
        });
    }







    private function callQuietly(callable $callback)
    {
        set_error_handler(function () {
            return true;
        });

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }





    private function deleteFile(string $filePath)
    {
        $this->callQuietly(function () use ($filePath) {
            if (is_file($filePath)) {
                unlink($filePath);
            }
        });
    }
}
