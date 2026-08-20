<?php

namespace WPStaging\Framework\Traits;

use Exception;
use RuntimeException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilterableDirectoryIterator;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Traits\SafeFileInfoTrait;

trait FileScanToCacheTrait
{
    use EndOfLinePlaceholderTrait;
    use SafeFileInfoTrait;

 
    protected $isExcludedWpConfig = false;

 
    protected $pathIdentifier = '';




    protected $strUtils;




    protected $logger;









    abstract public function write($handle, $content);















    public function scanToCacheFile($filesHandle, $path, $isRecursive = false, $excludePaths = [], $excludeSizeRules = [], $wpRootPath = ABSPATH, bool $shouldScanEmptyDirs = true)
    {
        if (!is_readable($path)) {
            $this->log(sprintf('Skipping! The path "%s" is not readable.', $path), Logger::TYPE_WARNING);
            return 0;
        }

        $filesystem       = WPStaging::make(Filesystem::class);
        $normalizedWpRoot = $filesystem->normalizePath($wpRootPath);

        if (is_file($path)) {
            $file = str_replace($normalizedWpRoot, '', $filesystem->normalizePath($path, true));
            $file = $this->replaceEOLsWithPlaceholders($file) . PHP_EOL;
            if ($this->write($filesHandle, $file)) {
                return 1;
            }

            return 0;
        }

        $filesWrittenToCache = 0;
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
 
                $itemPath = $item->getPathname();

                $isLink = $this->isLinkSafely($item);
                if ($isLink === null) {
                    continue;
                }

                if ($isLink) {
 
                    $linkTarget = $this->getRealPathSafely($item);
                    if ($linkTarget !== false && is_dir($linkTarget) && $isRecursive) {
                        $filesWrittenToCache += $this->scanToCacheFile($filesHandle, $itemPath, $isRecursive, $excludePaths, $excludeSizeRules, $wpRootPath, $shouldScanEmptyDirs);
                    }

                    continue;
                }

                if ($isRecursive) {
                    $isDir = $this->isDirSafely($item);
                    if ($isDir === null) {
                        continue;
                    }

                    if ($isDir) {
                        $filesWrittenToCache += $this->scanToCacheFile($filesHandle, $itemPath, $isRecursive, $excludePaths, $excludeSizeRules, $wpRootPath, $shouldScanEmptyDirs);
                        continue;
                    }
                }

                $isFile = $this->isFileSafely($item);
                if ($isFile === null) {
                    continue;
                }

                if ($isFile) {
                    $file = $filesystem->maybeNormalizePath($itemPath);
                    $file = $this->strUtils->replaceStartWith($normalizedWpRoot, '', $file);
 
                    $file = $this->strUtils->replaceStartWith($wpRootPath, '', $file);

 
                    if ($file === '/wp-config.php') {
                        $this->setIsExcludedWpConfig(false);
                    }

                    $file = $this->replaceEOLsWithPlaceholders($file) . PHP_EOL;
                    if ($this->write($filesHandle, $this->pathIdentifier . ltrim($file, '/'))) {
                        $filesWrittenToCache++;
                    }
                }
            }
        } catch (Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        if ($shouldScanEmptyDirs && $filesWrittenToCache === 0 && is_dir($path)) {
            $pathPart = $this->strUtils->replaceStartWith($normalizedWpRoot, '', $path) . PHP_EOL;
            $this->write($filesHandle, $this->pathIdentifier . ltrim($pathPart, '/'));
            $filesWrittenToCache++;
        }

        return $filesWrittenToCache;
    }




    public function setIsExcludedWpConfig($skipped = true)
    {
        $this->isExcludedWpConfig = $skipped;
    }




    public function getIsExcludedWpConfig()
    {
        return $this->isExcludedWpConfig;
    }




    protected function setPathIdentifier($pathIdentifier)
    {
        $this->pathIdentifier = $pathIdentifier;
    }







    public function log($msg, $type = Logger::TYPE_INFO)
    {
        if ($this->logger === null) {
            $this->logger = WPStaging::make(Logger::class);
        }

        $this->logger->add($msg, $type);
    }
}
