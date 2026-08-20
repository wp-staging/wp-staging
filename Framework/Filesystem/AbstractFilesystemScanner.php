<?php

namespace WPStaging\Framework\Filesystem;

use Exception;
use SplFileInfo;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Traits\SafeFileInfoTrait;
use WPStaging\Framework\Utils\PluginInfo;

abstract class AbstractFilesystemScanner
{
    use EndOfLinePlaceholderTrait;
    use SafeFileInfoTrait;




    const PATH_SEPARATOR = '::';

 
    protected $directory;

 
    protected $filesystem;

 
    protected $pathIdentifier;

 
    protected $pluginInfo;







    protected $currentPathScanning = '';





    protected $rootPath = '';





    protected $contentPath = '';

 
    protected $skipFiles = false;

 
    protected $skipDirectories = false;

 
    protected $excludeRules = [];







    public function __construct(
        Directory $directory,
        PathIdentifier $pathIdentifier,
        Filesystem $filesystem,
        PluginInfo $pluginInfo
    ) {
        $this->directory      = $directory;
        $this->filesystem     = $filesystem;
        $this->pathIdentifier = $pathIdentifier;
        $this->pluginInfo     = $pluginInfo;
        $this->rootPath       = ABSPATH;
        $this->contentPath    = WP_CONTENT_DIR;
    }





    public function setCurrentPathScanning(string $currentPathScanning)
    {
        $this->currentPathScanning = $currentPathScanning;
    }





    public function setRootPath(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }





    public function setContentPath(string $contentPath)
    {
        $this->contentPath = $contentPath;
    }




    public function setOnlyFiles()
    {
        $this->skipFiles = false;
        $this->skipDirectories = true;
    }




    public function setOnlyDirectories()
    {
        $this->skipFiles = true;
        $this->skipDirectories = false;
    }

    public function resetFilesDirectoriesSkipping()
    {
        $this->skipFiles = false;
        $this->skipDirectories = false;
    }





    public function setExcludeRules(array $excludeRules)
    {
        $this->excludeRules = $excludeRules;
    }





    public function addExcludeRule(string $excludeRule)
    {
        $this->excludeRules[] = $excludeRule;
    }







    public function preScanPath(string $directory, bool $processLinks = false, bool $scanLinkDirectory = true)
    {
        $iterator = (new FilterableDirectoryIterator())
            ->setDirectory(trailingslashit($directory))
            ->setRecursive(false)
            ->setSkipDirectoriesWithIncludeRules()
            ->setDotSkip()
            ->setWpRootPath($this->rootPath)
            ->setExcludePaths($this->excludeRules)
            ->get();

 
        foreach ($iterator as $item) {
            $isLink = $this->isLinkSafely($item);
            if ($isLink === null) {
                continue;
            }

            if ($isLink && $processLinks) {
                $this->processLink($item, $scanLinkDirectory);
            }

            if ($isLink) {
                continue;
            }

            $isFile = $this->isFileSafely($item);
            if ($isFile === null) {
                continue;
            }

            if ($isFile && !$this->skipFiles) {
                $this->processFile($item);
            }

            if ($isFile) {
                continue;
            }

            if ($this->skipDirectories) {
                continue;
            }

            $isDir = $this->isDirSafely($item);
            if ($isDir) {
                $this->processDirectory($item);
            }
        }
    }






    protected function processPath(string $path)
    {
        $path = $this->replacePlaceholdersWithEOLs($path);
        if (empty($path)) {
            return;
        }

        list($path, $linkPath) = $this->resolvePath($path);

        $path = untrailingslashit($this->filesystem->normalizePath($path, true));

        if (!file_exists($path)) {
            throw new Exception("$path is not a directory. Skipping...");
        }

        $this->preRecursivePathScanningStep();
        $this->recursivePathScanning($path, $linkPath);
    }




    abstract protected function preRecursivePathScanningStep();






    abstract protected function processFile(SplFileInfo $fileInfo, string $linkPath = '');






    abstract protected function processDirectory(SplFileInfo $fileInfo, $linkInfo = null);






    protected function processLink(SplFileInfo $linkInfo, bool $scanDirectory = true)
    {
        $isLink = $this->isLinkSafely($linkInfo);
        if ($isLink === null || !$isLink) {
            return;
        }

        $linkTarget = $this->getRealPathSafely($linkInfo);
        if ($linkTarget === false) {
            return;
        }

        $fileInfo = new SplFileInfo($linkTarget);

        $isLink = $this->isLinkSafely($fileInfo);
        if ($isLink === null || $isLink) {
            return;
        }

        $isFile = $this->isFileSafely($fileInfo);
        if ($isFile === null) {
            return;
        }

        if ($isFile) {
            $this->processFile($fileInfo, $linkInfo->getPathname());
            return;
        }

        $isDir = $this->isDirSafely($fileInfo);
        if ($isDir && $scanDirectory) {
            $this->processDirectory($fileInfo, $linkInfo);
            return;
        }
    }










    protected function resolvePath(string $pathToResolve): array
    {
        $linkPath  = '';
        $pathInfos = explode(self::PATH_SEPARATOR, $pathToResolve);
 
 
        if (count($pathInfos) > 2) {
 
            $linkPath = $pathInfos[2];
        }

 
        $this->currentPathScanning = $pathInfos[0];

 
        $path = $pathInfos[1];

        return [$path, $linkPath];
    }









    protected function recursivePathScanning(string $path, string $link = '')
    {
        $iterator = (new FilterableDirectoryIterator())
            ->setDirectory(trailingslashit($path))
            ->setRecursive(false)
            ->setDotSkip()
            ->setWpRootPath($this->rootPath)
            ->get();

 
        foreach ($iterator as $item) {
            $isLink = $this->isLinkSafely($item);
            if ($isLink === null) {
                continue;
            }

 
            if ($isLink) {
                continue;
            }

            $linkPath = '';
            if (!empty($link)) {
                $linkPath = trailingslashit($link) . $item->getFilename();
            }

            $isDir = $this->isDirSafely($item);
            if ($isDir === null) {
                continue;
            }

            if ($isDir) {
                $this->recursivePathScanning($item->getPathname(), $linkPath);
                continue;
            }

            $isFile = $this->isFileSafely($item);
            if ($isFile) {
                $this->processFile($item, $linkPath);
            }
        }
    }
}
