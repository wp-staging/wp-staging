<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\FileHeader;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Utils\Cache\Cache;








class ExploreCache
{
 
    const LIFETIME = 3600;

 
    const MAX_CACHE_SIZE = 20 * 1024 * 1024;

 
    private $cache;

 
    private $pathIdentifier;

 
    private $filesystem;

    public function __construct(Cache $cache, PathIdentifier $pathIdentifier, Filesystem $filesystem)
    {
        $this->cache          = $cache;
        $this->pathIdentifier = $pathIdentifier;
        $this->filesystem     = $filesystem;
    }








    public function getOrBuild(string $backupFile, BackupMetadata $metadata)
    {
        $this->configureCache($backupFile);

        $cached = $this->read($backupFile);
        if ($cached !== null) {
            return $cached;
        }

        $tree = $this->buildTree($backupFile, $metadata);
        if ($tree === null) {
            return null;
        }

        $this->write($backupFile, $tree);

        return $tree;
    }




    private function configureCache(string $backupFile)
    {
        $this->cache->setLifetime(self::LIFETIME);
        $this->cache->setFilename('backup_explore_' . md5($backupFile));
    }







    private function read(string $backupFile)
    {
        if (!$this->cache->isValid(false)) {
            return null;
        }

        $filePath = $this->cache->getFilePath();
        if (filesize($filePath) > self::MAX_CACHE_SIZE) {
            return null;
        }

        $data = $this->cache->get();
        if (!is_array($data) || !isset($data['mtime'], $data['tree'])) {
            return null;
        }

        $currentMtime = @filemtime($backupFile);
        if ($currentMtime === false || (int)$data['mtime'] !== $currentMtime) {
            return null;
        }

        return $data['tree'];
    }







    private function write(string $backupFile, array $tree)
    {
        $mtime = @filemtime($backupFile);
        if ($mtime === false) {
            return;
        }

        $this->cache->save([
            'mtime' => $mtime,
            'tree'  => $tree,
        ]);
    }








    private function buildTree(string $backupFile, BackupMetadata $metadata)
    {
        $indexLineDto = $this->createIndexLineDto($metadata);
        $fileObject   = new FileObject($backupFile, FileObject::MODE_READ);
        $fileObject->fseek((int)$metadata->getHeaderStart());

 
        $tree = ['' => ['dirs' => [], 'files' => []]];
 
        $dirChildren = [];
        $dirHasSubdirs = [];

        while ($fileObject->valid() && $fileObject->ftell() < (int)$metadata->getHeaderEnd()) {
            $indexOffset  = $fileObject->ftell();
            $rawIndexFile = $fileObject->readAndMoveNext();
            if (!$indexLineDto->isIndexLine($rawIndexFile)) {
                continue;
            }

            $backupFileIndex = $indexLineDto->readIndexLine($rawIndexFile);
            $relativePath    = $this->pathIdentifier->transformIdentifiableToRelativePath($backupFileIndex->getIdentifiablePath());
            $relativePath    = $this->filesystem->normalizePath($relativePath);

            if ($relativePath === '' || $relativePath === '/') {
                continue;
            }

            $lastSlash  = strrpos($relativePath, '/');
            $parentDir  = $lastSlash === false ? '' : substr($relativePath, 0, $lastSlash);
            $fileName   = $lastSlash === false ? $relativePath : substr($relativePath, $lastSlash + 1);

            if ($fileName === '') {
                continue;
            }

 
            if (!isset($tree[$parentDir])) {
                $tree[$parentDir] = ['dirs' => [], 'files' => []];
            }

 
 
            $parts = explode('/', $relativePath);
            $depth = count($parts);

            if ($depth > 1) {
 
                $currentPath = '';
                for ($i = 0; $i < $depth - 1; $i++) {
                    $dirName = $parts[$i];
                    $childPath = $currentPath === '' ? $dirName : $currentPath . '/' . $dirName;

                    if (!isset($tree[$currentPath])) {
                        $tree[$currentPath] = ['dirs' => [], 'files' => []];
                    }

 
                    if (!isset($tree[$currentPath]['dirs'][$dirName])) {
                        $tree[$currentPath]['dirs'][$dirName] = [
                            'name'        => $dirName,
                            'path'        => $childPath,
                            'hasChildren' => false,
                        ];
                    }

 
                    if ($i + 1 < $depth - 1) {
 
                        $dirHasSubdirs[$childPath] = true;
                    }

 
                    $nextPart = $parts[$i + 1];
                    $dirChildren[$childPath][$nextPart] = true;

                    $currentPath = $childPath;
                }
            }

 
            $size = (int)$backupFileIndex->getUncompressedSize();
            $tree[$parentDir]['files'][] = [
                'name'   => $fileName,
                'path'   => $relativePath,
                'size'   => $size,
                'offset' => (int)$indexOffset,
            ];
        }

        $fileObject = null;

 
        foreach ($tree as $folderPath => &$bucket) {
            $dirArray = [];
            foreach ($bucket['dirs'] as $dirName => $dirData) {
                $dirPath = $dirData['path'];
                $dirData['hasChildren'] = isset($dirChildren[$dirPath]) && count($dirChildren[$dirPath]) > 0;
                $dirData['items']       = isset($dirChildren[$dirPath]) ? count($dirChildren[$dirPath]) : 0;
                $dirArray[] = $dirData;
            }

            usort($dirArray, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            $bucket['dirs'] = $dirArray;

            usort($bucket['files'], function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
        }

        unset($bucket);

        return $tree;
    }





    private function createIndexLineDto(BackupMetadata $metadata)
    {
        if ($metadata->getIsBackupFormatV1()) {
            return new BackupFileIndex();
        }

        return WPStaging::make(FileHeader::class);
    }
}
