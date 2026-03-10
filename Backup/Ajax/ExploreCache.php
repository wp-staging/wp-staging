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

/**
 * Caches the parsed backup file index as a pre-built folder tree.
 *
 * Parses the full index once on first request and stores a flat associative
 * array keyed by folder path. Subsequent requests become O(1) lookups
 * instead of full sequential index scans.
 */
class ExploreCache
{
    /** @var int Cache lifetime in seconds (1 hour) */
    const LIFETIME = 3600;

    /** @var int Max cache file size in bytes before falling back to direct scan (20 MB) */
    const MAX_CACHE_SIZE = 20 * 1024 * 1024;

    /** @var Cache */
    private $cache;

    /** @var PathIdentifier */
    private $pathIdentifier;

    /** @var Filesystem */
    private $filesystem;

    public function __construct(Cache $cache, PathIdentifier $pathIdentifier, Filesystem $filesystem)
    {
        $this->cache          = $cache;
        $this->pathIdentifier = $pathIdentifier;
        $this->filesystem     = $filesystem;
    }

    /**
     * Get cached folder tree or build it from the backup index
     *
     * @param string $backupFile Absolute path to the .wpstg backup file
     * @param BackupMetadata $metadata
     * @return array|null Folder tree array keyed by folder path, or null on failure
     */
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

    /**
     * @param string $backupFile
     */
    private function configureCache(string $backupFile)
    {
        $this->cache->setLifetime(self::LIFETIME);
        $this->cache->setFilename('backup_explore_' . md5($backupFile));
    }

    /**
     * Read and validate the cached data
     *
     * @param string $backupFile
     * @return array|null
     */
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

    /**
     * Write the folder tree to cache
     *
     * @param string $backupFile
     * @param array $tree
     */
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

    /**
     * Build the full folder tree by scanning the backup index once
     *
     * @param string $backupFile
     * @param BackupMetadata $metadata
     * @return array
     */
    private function buildTree(string $backupFile, BackupMetadata $metadata)
    {
        $indexLineDto = $this->createIndexLineDto($metadata);
        $fileObject   = new FileObject($backupFile, FileObject::MODE_READ);
        $fileObject->fseek((int)$metadata->getHeaderStart());

        // tree[folderPath] = ['dirs' => [...], 'files' => [...]]
        $tree = ['' => ['dirs' => [], 'files' => []]];
        // Track child counts and subdirectory flags per folder per direct child dir
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

            // Ensure parent folder bucket exists
            if (!isset($tree[$parentDir])) {
                $tree[$parentDir] = ['dirs' => [], 'files' => []];
            }

            // Check if this file is nested deeper — means it belongs to a subdirectory of some ancestor
            // Register directory entries for every ancestor level
            $parts = explode('/', $relativePath);
            $depth = count($parts);

            if ($depth > 1) {
                // Register this file's directory chain
                $currentPath = '';
                for ($i = 0; $i < $depth - 1; $i++) {
                    $dirName = $parts[$i];
                    $childPath = $currentPath === '' ? $dirName : $currentPath . '/' . $dirName;

                    if (!isset($tree[$currentPath])) {
                        $tree[$currentPath] = ['dirs' => [], 'files' => []];
                    }

                    // Track this directory as a child of the current path
                    if (!isset($tree[$currentPath]['dirs'][$dirName])) {
                        $tree[$currentPath]['dirs'][$dirName] = [
                            'name'        => $dirName,
                            'path'        => $childPath,
                            'hasChildren' => false,
                        ];
                    }

                    // Track direct children count for item counting
                    if ($i + 1 < $depth - 1) {
                        // This is an intermediate directory — mark it has subdirs
                        $dirHasSubdirs[$childPath] = true;
                    }

                    // Count direct children of this directory (files + immediate subdirs)
                    $nextPart = $parts[$i + 1];
                    $dirChildren[$childPath][$nextPart] = true;

                    $currentPath = $childPath;
                }
            }

            // Add file to its direct parent
            $size = (int)$backupFileIndex->getUncompressedSize();
            $tree[$parentDir]['files'][] = [
                'name'   => $fileName,
                'path'   => $relativePath,
                'size'   => $size,
                'offset' => (int)$indexOffset,
            ];
        }

        $fileObject = null;

        // Set hasChildren and items counts on directory entries
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

    /**
     * @param BackupMetadata $metadata
     * @return \WPStaging\Backup\Interfaces\IndexLineInterface
     */
    private function createIndexLineDto(BackupMetadata $metadata)
    {
        if ($metadata->getIsBackupFormatV1()) {
            return new BackupFileIndex();
        }

        return WPStaging::make(FileHeader::class);
    }
}
