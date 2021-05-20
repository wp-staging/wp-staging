<?php

namespace WPStaging\Framework\Traits;

use Exception;
use stdClass;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Filesystem\FilterableDirectoryIterator;
use WPStaging\Framework\Utils\Strings;

trait FileScanToCacheTrait
{
    /**
     * Write contents to a file
     *
     * @param resource $handle File handle to write to
     * @param string $content Content to write to the file
     * @return integer
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
     */
    public function scanToCacheFile($filesHandle, $path, $isRecursive = false, $excludePaths = [], $excludeSizeRules = [], $wpRootPath = ABSPATH)
    {
        if (is_link($path)) {
            return 0;
        }

        $strUtil = new Strings();
        if (is_file($path)) {
            $file = str_replace($strUtil->sanitizeDirectorySeparator($wpRootPath), '', $strUtil->sanitizeDirectorySeparator($path)) . PHP_EOL;
            if ($this->write($filesHandle, $file)) {
                return 1;
            }

            return 0;
        }

        $filesWrittenToCache = 0;
        $iterator = (new FilterableDirectoryIterator())
                        ->setDirectory(trailingslashit($path))
                        ->setRecursive(false)
                        ->setDotSkip()
                        ->setExcludePaths($excludePaths)
                        ->setExcludeSizeRules($excludeSizeRules)
                        ->setWpRootPath($wpRootPath)
                        ->get();

        $strUtil = new Strings();
        foreach ($iterator as $item) {
            // Always check link first otherwise it may be treated as directory
            if ($item->isLink()) {
                continue;
            }

            if ($isRecursive && $item->isDir()) {
                $filesWrittenToCache += $this->scanToCacheFile($filesHandle, $item->getPathname(), $isRecursive, $excludePaths, $excludeSizeRules, $wpRootPath);
            }

            if ($item->isFile()) {
                $file = str_replace($strUtil->sanitizeDirectorySeparator($wpRootPath), '', $strUtil->sanitizeDirectorySeparator($item->getPathname())) . PHP_EOL;
                if ($this->write($filesHandle, $file)) {
                    $filesWrittenToCache++;
                }
            }
        }

        return $filesWrittenToCache;
    }
}
