<?php

namespace WPStaging\Framework\Traits;

use Exception;
use RuntimeException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilterableDirectoryIterator;

trait FileScanToCacheTrait
{
    protected $isExcludedWpConfig = false;

    /**
     * Write contents to a file
     *
     * @param resource $handle File handle to write to
     * @param string $content Content to write to the file
     * @return int
     * @throws Exception
     */
    abstract public function write($handle, $content);

    /**
     * Scan Recursively through DirectoryIterator as RecursiveDirectoryIterator is slow
     *
     * @param resource $filesHandle
     * @param string $path
     * @param bool $isRecursive
     * @param array $excludePaths absolute path of dir/files to exclude
     * @param array $excludeSizeRules exclude files by different size comparing rules
     * @param string $wpRootPath
     *
     * @return int count of files path written to cache file
     * @throws Exception
     */
    public function scanToCacheFile($filesHandle, $path, $isRecursive = false, $excludePaths = [], $excludeSizeRules = [], $wpRootPath = ABSPATH)
    {
        $filesystem = new Filesystem();
        $normalizedWpRoot = $filesystem->normalizePath($wpRootPath);
        if (is_file($path)) {
            $file = str_replace($normalizedWpRoot, '', $filesystem->normalizePath($path, true)) . PHP_EOL;
            if ($this->write($filesHandle, $file)) {
                return 1;
            }

            return 0;
        }

        $filesWrittenToCache = 0;

        if (!file_exists($path)) {
            return 0;
        }

        try {
            $iterator = (new FilterableDirectoryIterator())
                            ->setDirectory($filesystem->trailingSlashit($path))
                            ->setRecursive(false)
                            ->setDotSkip()
                            ->setExcludePaths($excludePaths)
                            ->setExcludeSizeRules($excludeSizeRules)
                            ->setWpRootPath($wpRootPath)
                            ->get();

            foreach ($iterator as $item) {
                // Always check link first otherwise it may be treated as directory
                $itemPath = $item->getPathname();
                if ($item->isLink()) {
                    // Allow copying of link if the link's source is a directory
                    if (is_dir($item->getRealPath()) && $isRecursive) {
                        $filesWrittenToCache += $this->scanToCacheFile($filesHandle, $itemPath, $isRecursive, $excludePaths, $excludeSizeRules, $wpRootPath);
                    }

                    continue;
                }

                if ($isRecursive && $item->isDir()) {
                    $filesWrittenToCache += $this->scanToCacheFile($filesHandle, $itemPath, $isRecursive, $excludePaths, $excludeSizeRules, $wpRootPath);
                    continue;
                }

                if ($item->isFile()) {
                    $file = $filesystem->maybeNormalizePath($itemPath);
                    $file = str_replace($normalizedWpRoot, '', $file);
                    // One more time with not normalized $wpRootPath in case the file path was not normalized
                    $file = str_replace($wpRootPath, '', $file) . PHP_EOL;

                    // At the moment will only handle case where wp-config.php is present at root folder of WP
                    if ($file === '/wp-config.php') {
                        $this->setIsExcludedWpConfig(false);
                    }

                    if ($this->write($filesHandle, $file)) {
                        $filesWrittenToCache++;
                    }
                }
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        return $filesWrittenToCache;
    }

    /**
     * @param bool $skipped
     */
    public function setIsExcludedWpConfig($skipped = true)
    {
        $this->isExcludedWpConfig = $skipped;
    }

    /**
     * @return bool
     */
    public function getIsExcludedWpConfig()
    {
        return $this->isExcludedWpConfig;
    }
}
