<?php

namespace WPStaging\Framework\Filesystem;

use Exception;
use SplFileInfo;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Utils\PluginInfo;

abstract class AbstractFilesystemScanner
{
    use EndOfLinePlaceholderTrait;

    /**
     * @var string
     */
    const PATH_SEPARATOR = '::';

    /** @var Directory */
    protected $directory;

    /** @var Filesystem */
    protected $filesystem;

    /** @var PathIdentifier */
    protected $pathIdentifier;

    /** @var PluginInfo */
    protected $pluginInfo;

    /**
     * The parent path which is currently being scanned
     * Can be either plugins, mu_plugins, themes, uploads or other
     * Where other means base wp-content directory but skipping plugins, mu_plugins, themes and uploads as they are handle separately
     * @var string
     */
    protected $currentPathScanning = '';

    /**
     * The root path of the site
     * @var string
     */
    protected $rootPath = '';

    /** @var bool */
    protected $skipFiles = false;

    /** @var bool */
    protected $skipDirectories = false;

    /** @var array */
    protected $excludeRules = [];

    /**
     * @param Directory $directory
     * @param PathIdentifier $pathIdentifier
     * @param Filesystem $filesystem
     * @param PluginInfo $pluginInfo
     */
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
    }

    /**
     * @param string $currentPathScanning
     * @return void
     */
    public function setCurrentPathScanning(string $currentPathScanning)
    {
        $this->currentPathScanning = $currentPathScanning;
    }

    /**
     * @param string $rootPath
     * @return void
     */
    public function setRootPath(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @return void
     */
    public function setOnlyFiles()
    {
        $this->skipFiles = false;
        $this->skipDirectories = true;
    }

    /**
     * @return void
     */
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

    /**
     * @param array $excludeRules
     * @return void
     */
    public function setExcludeRules(array $excludeRules)
    {
        $this->excludeRules = $excludeRules;
    }

    /**
     * @param string $excludeRule
     * @return void
     */
    public function addExcludeRule(string $excludeRule)
    {
        $this->excludeRules[] = $excludeRule;
    }

    /**
     * @param string $directory
     * @param bool $processLinks
     * @param bool $scanLinkDirectory
     * @return void
     */
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

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isLink() && $processLinks) {
                $this->processLink($item, $scanLinkDirectory);
            }

            if ($item->isLink()) {
                continue;
            }

            if ($item->isFile() && !$this->skipFiles) {
                $this->processFile($item);
            }

            if ($item->isFile()) {
                continue;
            }

            if ($this->skipDirectories) {
                continue;
            }

            if ($item->isDir()) {
                $this->processDirectory($item);
            }
        }
    }

    /**
     * @param string $path
     * @return void
     * @throws Exception
     */
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

    /**
     * @return void
     */
    abstract protected function preRecursivePathScanningStep();

    /**
     * @param SplFileInfo $fileInfo
     * @param string $linkPath
     * @return void
     */
    abstract protected function processFile(SplFileInfo $fileInfo, string $linkPath = '');

    /**
     * @param SplFileInfo $fileInfo
     * @param ?SplFileInfo $linkInfo
     * @return void
     */
    abstract protected function processDirectory(SplFileInfo $fileInfo, $linkInfo = null);

    /**
     * @param SplFileInfo $linkInfo
     * @param bool $scanDirectory
     * @return void
     */
    protected function processLink(SplFileInfo $linkInfo, bool $scanDirectory = true)
    {
        // Bail if no link
        if (!$linkInfo->isLink()) {
            return;
        }

        $linkTarget = $linkInfo->getRealPath();
        $fileInfo   = new SplFileInfo($linkTarget);
        if ($fileInfo->isLink()) {
            return;
        }

        if ($fileInfo->isFile()) {
            $this->processFile($fileInfo, $linkInfo->getPathname());
            return;
        }

        if ($fileInfo->isDir() && $scanDirectory) {
            $this->processDirectory($fileInfo, $linkInfo);
            return;
        }
    }

    /**
     * Resolve path on non-wp.com sites (sites with no symlinks structure) to [base_directory, path, '']
     * Resolve path on wp.com sites (sites with symlinks structure) to [base_directory, path, link]
     * Where base_directory can be either plugins, mu_plugins, themes, uploads or other etc
     * Where path is the path to scan
     * Where link is the link to path (empty in case of non-wp.com sites)
     * @param string $pathToResolve - Path to resolve in format base_directory::path::link or base_directory::path
     * @return array [string pathToScan, string linkToPath]
     */
    protected function resolvePath(string $pathToResolve): array
    {
        $linkPath  = '';
        $pathInfos = explode(self::PATH_SEPARATOR, $pathToResolve);
        // On non-wp.com sites, we don't have link, we only have base directory and path to scan
        // On wp.com sites, we have base directory, path to scan and link to path, so path info contains 3 elements
        if (count($pathInfos) > 2) {
            // link to path
            $linkPath = $pathInfos[2];
        }

        // base directory
        $this->currentPathScanning = $pathInfos[0];

        // path to scan
        $path = $pathInfos[1];

        return [$path, $linkPath];
    }

    /**
     * @param string $path - Path to scan
     * @param string $link - If original $path is resolved from link, then this is the link
     *                       We need it to keep original path after restore
     *                       e.g. $link = /var/www/html/wp-content/themes/twentytwenty is a link to /var/www/libs/themes/twentytwenty (a $path)
     * @return void
     * @throws FilesystemExceptions
     */
    protected function recursivePathScanning(string $path, string $link = '')
    {
        $iterator = (new FilterableDirectoryIterator())
            ->setDirectory(trailingslashit($path))
            ->setRecursive(false)
            ->setDotSkip()
            ->setWpRootPath($this->rootPath)
            ->get();

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            // Always check link first otherwise it may be treated as directory
            if ($item->isLink()) {
                continue;
            }

            $linkPath = '';
            if (!empty($link)) {
                $linkPath = trailingslashit($link) . $item->getFilename();
            }

            if ($item->isDir()) {
                $this->recursivePathScanning($item->getPathname(), $linkPath);
                continue;
            }

            if ($item->isFile()) {
                $this->processFile($item, $linkPath);
            }
        }
    }
}
