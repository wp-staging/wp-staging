<?php

namespace WPStaging\Framework\Traits;

use Exception;
use stdClass;
use WPStaging\Backend\Modules\Jobs\Scan;
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
     * @param string $wpRootPath
     *
     * @return int count of files path written to cache file
     */
    public function scanToCacheFile($filesHandle, $path, $isRecursive = false, $excludePaths = [], $wpRootPath = ABSPATH)
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
                        ->get();
        foreach ($iterator as $item) {
            // Always check link first otherwise it may be treated as directory
            if ($item->isLink()) {
                continue;
            }

            if ($isRecursive && $item->isDir()) {
                $filesWrittenToCache += $this->scanToCacheFile($filesHandle, $item->getPathname(), $isRecursive, $excludePaths, $wpRootPath);
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

    /**
     * Filtered directories according to the directory given
     * @param string $directoryPath
     * @param array $directories list of selected directories in the form of directoryPath . separatorConst . scanFlag e.g. /var/www/wp-content/plugins/::1
     *
     * @return array
     */
    public function filteredSelectedDirectories($directoryPath, $directories)
    {
        $strUtil = new Strings();
        $directoryPath = $strUtil->sanitizeDirectorySeparator($directoryPath);
        return array_filter($this->mapSelectedDirectories($directories), function ($directory) use ($directoryPath, $strUtil) {
            if ($strUtil->startsWith($strUtil->sanitizeDirectorySeparator($directory->path), $directoryPath)) {
                return true;
            }
        });
    }

    /**
     * Map included directories to object
     * @param array $directories list of selected directories in the form of directoryPath . separatorConst . scanFlag
     *
     * @return array array of objects which contains information about directory path and whether it is scanned or not
     */
    protected function mapSelectedDirectories($directories)
    {
        return array_map(function ($directory) {
            $directory = trim($directory, ' ');
            list($directoryPath, $flag) = explode(Scan::DIRECTORY_PATH_FLAG_SEPARATOR, $directory);
            $directoryInfo = new stdClass();
            $directoryInfo->path = trim($directoryPath, ' ');
            $directoryInfo->flag = trim($flag, ' ');
            return $directoryInfo;
        }, $directories);
    }
}
