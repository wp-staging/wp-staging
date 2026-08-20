<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\BackupFileIndex;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\FileHeader;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Component\AbstractTemplateComponent;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\TemplateEngine\TemplateEngine;







class Explore extends AbstractTemplateComponent
{



    const MAX_PER_PAGE = 200;




    const MAX_TREE_ITEMS = 300;




    private $pathIdentifier;

 
    private $backupPathResolver;

 
    private $filesystem;

 
    private $exploreCache;

    public function __construct(
        TemplateEngine $templateEngine,
        PathIdentifier $pathIdentifier,
        BackupPathResolver $backupPathResolver,
        Filesystem $filesystem,
        ExploreCache $exploreCache
    ) {
        parent::__construct($templateEngine);
        $this->pathIdentifier     = $pathIdentifier;
        $this->backupPathResolver = $backupPathResolver;
        $this->filesystem         = $filesystem;
        $this->exploreCache       = $exploreCache;
    }






    public function browse()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $filePath = isset($_POST['filePath']) ? sanitize_text_field(wp_unslash($_POST['filePath'])) : '';
        if (empty($filePath)) {
            wp_send_json_error(['message' => __('Backup file is missing.', 'wp-staging')]);
        }

        $backupFile = $this->backupPathResolver->resolveBackupPath($filePath);
        if (empty($backupFile) || !file_exists($backupFile)) {
            wp_send_json_error(['message' => __('Backup file not found.', 'wp-staging')]);
        }

        try {
            $metadata = (new BackupMetadata())->hydrateByFilePath($backupFile);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        $perPage = isset($_POST['perPage']) ? absint($_POST['perPage']) : self::MAX_PER_PAGE;
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));

        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $page = max(1, $page);

        $folder = isset($_POST['folder']) ? sanitize_text_field(wp_unslash($_POST['folder'])) : '';
        $folder = $this->normalizeFolder($folder);

        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $sort   = 'name_asc';

        $withTree = isset($_POST['withTree'])
            ? filter_var(wp_unslash($_POST['withTree']), FILTER_VALIDATE_BOOLEAN)
            : false;

        try {
            $entries = $this->getDirectoryEntries($backupFile, $metadata, $folder, $search, $sort);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        $totalEntries = count($entries);
        $totalPages   = (int)ceil($totalEntries / $perPage);
        $offset       = ($page - 1) * $perPage;
        $pagedEntries = array_slice($entries, $offset, $perPage);

        $response = [
            'entries' => $pagedEntries,
            'paging'  => [
                'totalItems' => $totalEntries,
                'totalPages' => $totalPages,
                'page'       => $page,
                'hasMore'    => $page < $totalPages,
            ],
        ];

        if ($withTree) {
            try {
                $response['directories'] = $this->getDirectoryTree($backupFile, $metadata, $folder);
            } catch (\Throwable $e) {
                $response['directories'] = [];
            }
        }

        wp_send_json_success($response);
    }




    public function listFiles()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $filePath = isset($_POST['filePath']) ? sanitize_text_field(wp_unslash($_POST['filePath'])) : '';
        if (empty($filePath)) {
            wp_send_json_error(['message' => __('Backup file is missing.', 'wp-staging')]);
        }

        $backupFile = $this->backupPathResolver->resolveBackupPath($filePath);
        if (empty($backupFile) || !file_exists($backupFile)) {
            wp_send_json_error(['message' => __('Backup file not found.', 'wp-staging')]);
        }

        try {
            $metadata = (new BackupMetadata())->hydrateByFilePath($backupFile);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        $perPage = isset($_POST['perPage']) ? absint($_POST['perPage']) : self::MAX_PER_PAGE;
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));

        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $page = max(1, $page);

        $folder = isset($_POST['folder']) ? sanitize_text_field(wp_unslash($_POST['folder'])) : '';
        $folder = $this->normalizeFolder($folder);

        $search = isset($_POST['search']) ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
        $sort   = 'name_asc';

        try {
            $entries = $this->getDirectoryEntries($backupFile, $metadata, $folder, $search, $sort);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        $totalEntries = count($entries);
        $totalPages   = (int)ceil($totalEntries / $perPage);
        $offset       = ($page - 1) * $perPage;
        $pagedEntries = array_slice($entries, $offset, $perPage);

        wp_send_json_success([
            'entries' => $pagedEntries,
            'paging'  => [
                'totalItems' => $totalEntries,
                'totalPages' => $totalPages,
                'page'       => $page,
                'hasMore'    => $page < $totalPages,
            ],
        ]);
    }




    public function listTree()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $filePath = isset($_POST['filePath']) ? sanitize_text_field(wp_unslash($_POST['filePath'])) : '';
        if (empty($filePath)) {
            wp_send_json_error(['message' => __('Backup file is missing.', 'wp-staging')]);
        }

        $backupFile = $this->backupPathResolver->resolveBackupPath($filePath);
        if (empty($backupFile) || !file_exists($backupFile)) {
            wp_send_json_error(['message' => __('Backup file not found.', 'wp-staging')]);
        }

        try {
            $metadata = (new BackupMetadata())->hydrateByFilePath($backupFile);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        $folder = isset($_POST['folder']) ? sanitize_text_field(wp_unslash($_POST['folder'])) : '';
        $folder = $this->normalizeFolder($folder);

        try {
            $directories = $this->getDirectoryTree($backupFile, $metadata, $folder);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        wp_send_json_success([
            'directories' => $directories,
        ]);
    }




    public function listDirectoryFiles()
    {
        if (!$this->canRenderAjax()) {
            return;
        }

        $filePath = isset($_POST['filePath']) ? sanitize_text_field(wp_unslash($_POST['filePath'])) : '';
        if (empty($filePath)) {
            wp_send_json_error(['message' => __('Backup file is missing.', 'wp-staging')]);
        }

        $backupFile = $this->backupPathResolver->resolveBackupPath($filePath);
        if (empty($backupFile) || !file_exists($backupFile)) {
            wp_send_json_error(['message' => __('Backup file not found.', 'wp-staging')]);
        }

        try {
            $metadata = (new BackupMetadata())->hydrateByFilePath($backupFile);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        $folder = isset($_POST['folder']) ? sanitize_text_field(wp_unslash($_POST['folder'])) : '';
        $folder = $this->normalizeFolder($folder);
        $summaryOnly = isset($_POST['summaryOnly'])
            ? filter_var(wp_unslash($_POST['summaryOnly']), FILTER_VALIDATE_BOOLEAN)
            : false;

        try {
            if ($summaryOnly) {
                $summary = $this->getDirectoryStatsForSelection($backupFile, $metadata, $folder);
                wp_send_json_success([
                    'summary' => $summary,
                ]);
            }

            $files = $this->getDirectoryFilesForSelection($backupFile, $metadata, $folder);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }

        wp_send_json_success([
            'files' => $files,
        ]);
    }











    private function getDirectoryEntries(string $backupFile, BackupMetadata $metadata, string $folder, string $search, string $sort): array
    {
        $isSearching = $search !== '';

 
        if (!$isSearching) {
            $tree = $this->exploreCache->getOrBuild($backupFile, $metadata);
            if ($tree !== null) {
                return $this->getEntriesFromCache($tree, $folder, $sort);
            }
        }

        return $this->getDirectoryEntriesFromIndex($backupFile, $metadata, $folder, $search, $sort);
    }









    private function getEntriesFromCache(array $tree, string $folder, string $sort): array
    {
        if (!isset($tree[$folder])) {
            return [];
        }

        $bucket = $tree[$folder];
        $directories = [];
        foreach ($bucket['dirs'] as $dir) {
            $directories[] = [
                'type'        => 'dir',
                'name'        => $dir['name'],
                'path'        => $dir['path'],
                'items'       => $dir['items'] ?? 0,
                'hasChildren' => $dir['hasChildren'] ?? false,
            ];
        }

        $files = [];
        foreach ($bucket['files'] as $file) {
            $files[] = [
                'type'          => 'file',
                'name'          => $file['name'],
                'path'          => $file['path'],
                'size'          => $file['size'],
                'sizeFormatted' => size_format($file['size'], 2),
                'offset'        => $file['offset'],
            ];
        }

        $directories = $this->sortDirectories($directories, $sort);
        $files       = $this->sortFiles($files, $sort);

        return array_merge($directories, $files);
    }









    private function getDirectoryTree(string $backupFile, BackupMetadata $metadata, string $folder): array
    {
        $tree = $this->exploreCache->getOrBuild($backupFile, $metadata);
        if ($tree !== null) {
            return $this->getTreeFromCache($tree, $folder);
        }

        return $this->getDirectoryTreeFromIndex($backupFile, $metadata, $folder);
    }








    private function getTreeFromCache(array $tree, string $folder): array
    {
        if (!isset($tree[$folder])) {
            return [];
        }

        $dirs = $tree[$folder]['dirs'];

        $result = [];
        foreach ($dirs as $dir) {
            $result[] = [
                'name'        => $dir['name'],
                'path'        => $dir['path'],
                'hasChildren' => $dir['hasChildren'] ?? false,
            ];
        }

        usort($result, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return array_slice($result, 0, self::MAX_TREE_ITEMS);
    }









    private function getDirectoryStatsForSelection(string $backupFile, BackupMetadata $metadata, string $folder): array
    {
        $tree = $this->exploreCache->getOrBuild($backupFile, $metadata);
        if ($tree !== null) {
            return $this->getStatsFromCache($tree, $folder);
        }

        return $this->getDirectoryStatsFromIndex($backupFile, $metadata, $folder);
    }








    private function getStatsFromCache(array $tree, string $folder): array
    {
        $count = 0;
        $size  = 0;

        $stack = [$folder];
        while (!empty($stack)) {
            $dir = array_pop($stack);
            if (!isset($tree[$dir])) {
                continue;
            }

            foreach ($tree[$dir]['files'] as $file) {
                $count++;
                $size += $file['size'];
            }

            foreach ($tree[$dir]['dirs'] as $subdir) {
                $stack[] = $subdir['path'];
            }
        }

        return [
            'count' => $count,
            'size'  => $size,
        ];
    }









    private function getDirectoryFilesForSelection(string $backupFile, BackupMetadata $metadata, string $folder): array
    {
        $tree = $this->exploreCache->getOrBuild($backupFile, $metadata);
        if ($tree !== null) {
            return $this->getFilesFromCache($tree, $folder);
        }

        return $this->getDirectoryFilesFromIndex($backupFile, $metadata, $folder);
    }








    private function getFilesFromCache(array $tree, string $folder): array
    {
        $files = [];
        $stack = [$folder];

        while (!empty($stack)) {
            $dir = array_pop($stack);
            if (!isset($tree[$dir])) {
                continue;
            }

            foreach ($tree[$dir]['files'] as $file) {
                $files[] = [
                    'offset' => $file['offset'],
                    'path'   => $file['path'],
                    'size'   => $file['size'],
                ];
            }

            foreach ($tree[$dir]['dirs'] as $subdir) {
                $stack[] = $subdir['path'];
            }
        }

        return $files;
    }

 









    private function getDirectoryEntriesFromIndex(string $backupFile, BackupMetadata $metadata, string $folder, string $search, string $sort): array
    {
        $isSearching = $search !== '';
        $prefix = $isSearching ? '' : ($folder === '' ? '' : trailingslashit($folder));
        $directories = [];
        $directoryChildren = [];
        $directoryHasSubdirs = [];
        $files = [];

        $indexLineDto = $this->createIndexLineDto($metadata);
        $fileObject   = new FileObject($backupFile, FileObject::MODE_READ);
        $fileObject->fseek((int)$metadata->getHeaderStart());

        while ($fileObject->valid() && $fileObject->ftell() < (int)$metadata->getHeaderEnd()) {
            $indexOffset = $fileObject->ftell();
            $rawIndexFile = $fileObject->readAndMoveNext();
            if (!$indexLineDto->isIndexLine($rawIndexFile)) {
                continue;
            }

            $backupFileIndex = $indexLineDto->readIndexLine($rawIndexFile);
            $relativePath = $this->pathIdentifier->transformIdentifiableToRelativePath($backupFileIndex->getIdentifiablePath());
            $relativePath = $this->filesystem->normalizePath($relativePath);

            if ($prefix !== '' && strpos($relativePath, $prefix) !== 0) {
                continue;
            }

            $remaining = $prefix === '' ? $relativePath : substr($relativePath, strlen($prefix));
            if ($remaining === '') {
                continue;
            }

            $parts = explode('/', $remaining);
            if (!$isSearching && count($parts) > 1) {
                $dirName = $parts[0];
                $childName = $parts[1] ?? '';
                if (!empty($childName)) {
                    $directoryChildren[$dirName][$childName] = true;
                }

                if (count($parts) > 2) {
                    $directoryHasSubdirs[$dirName] = true;
                }

                if ($this->matchesSearch($dirName, $search)) {
                    $directories[$dirName] = [
                        'type'  => 'dir',
                        'name'  => $dirName,
                        'path'  => $prefix . $dirName,
                        'items' => 0,
                    ];
                }

                continue;
            }

            $fileName = basename($relativePath);
            if (!$this->matchesSearch($fileName, $search)) {
                continue;
            }

            $size = (int)$backupFileIndex->getUncompressedSize();
            $files[] = [
                'type'          => 'file',
                'name'          => $fileName,
                'path'          => $relativePath,
                'size'          => $size,
                'sizeFormatted' => size_format($size, 2),
                'offset'        => (int)$indexOffset,
            ];
        }

        $fileObject = null;

        if (!$isSearching) {
            foreach ($directories as $dirName => $dirData) {
                $directories[$dirName]['items']       = isset($directoryChildren[$dirName]) ? count($directoryChildren[$dirName]) : 0;
                $directories[$dirName]['hasChildren'] = isset($directoryHasSubdirs[$dirName]);
            }

            $directories = array_values($directories);
            $directories = $this->sortDirectories($directories, $sort);
            $files       = $this->sortFiles($files, $sort);

            return array_merge($directories, $files);
        }

        return $this->sortFiles($files, $sort);
    }







    private function getDirectoryTreeFromIndex(string $backupFile, BackupMetadata $metadata, string $folder): array
    {
        $prefix = $folder === '' ? '' : trailingslashit($folder);
        $directories = [];

        $indexLineDto = $this->createIndexLineDto($metadata);
        $fileObject   = new FileObject($backupFile, FileObject::MODE_READ);
        $fileObject->fseek((int)$metadata->getHeaderStart());

        while ($fileObject->valid() && $fileObject->ftell() < (int)$metadata->getHeaderEnd()) {
            $rawIndexFile = $fileObject->readAndMoveNext();
            if (!$indexLineDto->isIndexLine($rawIndexFile)) {
                continue;
            }

            $backupFileIndex = $indexLineDto->readIndexLine($rawIndexFile);
            $relativePath = $this->pathIdentifier->transformIdentifiableToRelativePath($backupFileIndex->getIdentifiablePath());
            $relativePath = $this->filesystem->normalizePath($relativePath);

            if ($prefix !== '' && strpos($relativePath, $prefix) !== 0) {
                continue;
            }

            $remaining = $prefix === '' ? $relativePath : substr($relativePath, strlen($prefix));
            if ($remaining === '') {
                continue;
            }

            $parts = explode('/', $remaining);
            if (count($parts) <= 1) {
                continue;
            }

            $dirName = $parts[0];
            if (!isset($directories[$dirName])) {
                $directories[$dirName] = [
                    'name'        => $dirName,
                    'path'        => $prefix . $dirName,
                    'hasChildren' => false,
                ];
            }

            if (count($parts) > 2) {
                $directories[$dirName]['hasChildren'] = true;
            }

            if (count($directories) >= self::MAX_TREE_ITEMS) {
                break;
            }
        }

        $fileObject = null;

        $directories = array_values($directories);
        usort($directories, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $directories;
    }







    private function getDirectoryFilesFromIndex(string $backupFile, BackupMetadata $metadata, string $folder): array
    {
        $prefix = $folder === '' ? '' : trailingslashit($folder);

        $files = [];
        $indexLineDto = $this->createIndexLineDto($metadata);
        $fileObject   = new FileObject($backupFile, FileObject::MODE_READ);
        $fileObject->fseek((int)$metadata->getHeaderStart());

        while ($fileObject->valid() && $fileObject->ftell() < (int)$metadata->getHeaderEnd()) {
            $indexOffset = $fileObject->ftell();
            $rawIndexFile = $fileObject->readAndMoveNext();
            if (!$indexLineDto->isIndexLine($rawIndexFile)) {
                continue;
            }

            $backupFileIndex = $indexLineDto->readIndexLine($rawIndexFile);
            $relativePath = $this->pathIdentifier->transformIdentifiableToRelativePath($backupFileIndex->getIdentifiablePath());
            $relativePath = $this->filesystem->normalizePath($relativePath);

            if ($prefix !== '' && strpos($relativePath, $prefix) !== 0) {
                continue;
            }

            if ($relativePath === $prefix) {
                continue;
            }

            $files[] = [
                'offset' => (int)$indexOffset,
                'path'   => $relativePath,
                'size'   => (int)$backupFileIndex->getUncompressedSize(),
            ];
        }

        $fileObject = null;

        return $files;
    }







    private function getDirectoryStatsFromIndex(string $backupFile, BackupMetadata $metadata, string $folder): array
    {
        $prefix = $folder === '' ? '' : trailingslashit($folder);

        $count = 0;
        $size  = 0;

        $indexLineDto = $this->createIndexLineDto($metadata);
        $fileObject   = new FileObject($backupFile, FileObject::MODE_READ);
        $fileObject->fseek((int)$metadata->getHeaderStart());

        while ($fileObject->valid() && $fileObject->ftell() < (int)$metadata->getHeaderEnd()) {
            $rawIndexFile = $fileObject->readAndMoveNext();
            if (!$indexLineDto->isIndexLine($rawIndexFile)) {
                continue;
            }

            $backupFileIndex = $indexLineDto->readIndexLine($rawIndexFile);
            $relativePath = $this->pathIdentifier->transformIdentifiableToRelativePath($backupFileIndex->getIdentifiablePath());
            $relativePath = $this->filesystem->normalizePath($relativePath);

            if ($prefix !== '' && strpos($relativePath, $prefix) !== 0) {
                continue;
            }

            if ($relativePath === $prefix) {
                continue;
            }

            $count++;
            $size += (int)$backupFileIndex->getUncompressedSize();
        }

        $fileObject = null;

        return [
            'count' => $count,
            'size'  => $size,
        ];
    }

 





    private function normalizeFolder(string $folder): string
    {
        $folder = trim($folder);
        $folder = trim($folder, '/');

        return $this->filesystem->normalizePath($folder);
    }






    private function matchesSearch(string $name, string $search): bool
    {
        if ($search === '') {
            return true;
        }

        $normalizedName = basename($name);

 
        return stripos($normalizedName, $search) === 0;
    }






    private function sortDirectories(array $directories, string $sort): array
    {
        usort($directories, function ($a, $b) use ($sort) {
            if ($sort === 'name_desc') {
                return strcasecmp($b['name'], $a['name']);
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $directories;
    }






    private function sortFiles(array $files, string $sort): array
    {
        usort($files, function ($a, $b) use ($sort) {
            switch ($sort) {
                case 'size_asc':
                    return $a['size'] <=> $b['size'];
                case 'size_desc':
                    return $b['size'] <=> $a['size'];
                case 'name_desc':
                    return strcasecmp($b['name'], $a['name']);
                case 'name_asc':
                default:
                    return strcasecmp($a['name'], $b['name']);
            }
        });

        return $files;
    }





    private function createIndexLineDto(BackupMetadata $metadata)
    {
        if ($metadata->getIsBackupFormatV1()) {
            return new BackupFileIndex();
        }

        return WPStaging::make(FileHeader::class);
    }
}
