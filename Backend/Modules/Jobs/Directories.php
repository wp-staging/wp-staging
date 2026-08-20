<?php

namespace WPStaging\Backend\Modules\Jobs;

use Exception;
use UnexpectedValueException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Traits\FileScanToCacheTrait;
use WPStaging\Framework\Utils\Strings;






class Directories extends JobExecutable
{
    use FileScanToCacheTrait;

 
    const FILTER_CLONE_EXCLUDED_FILE_SIZE = 'wpstg_clone_file_size_exclude';

 
    protected $excludedPlugins;





    private $total = 8;





    private $filename;




    private $filesystem;




    private $pathAdapter;




    private $directory;

 
    private $pathChecker;




    private $rootPath;

 
    private $excludedPaths = [];




    public function initialize()
    {
        $this->filesystem      = WPStaging::make(Filesystem::class);
        $this->directory       = WPStaging::make(Directory::class);
        $this->pathAdapter     = WPStaging::make(PathIdentifier::class);
        $this->pathChecker     = WPStaging::make(PathChecker::class);
        $this->filename        = $this->getFilesIndexCacheFilePath();
        $this->strUtils        = new Strings();
        $this->rootPath        = $this->filesystem->normalizePath($this->directory->getAbsPath());
        $this->excludedPlugins = new ExcludedPlugins($this->directory);

        $this->filesIndexCache->initWithPhpHeader();
    }





    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = $this->total + count($this->options->extraDirectories);
    }






    public function start()
    {
 
        $this->run();

 
        $this->saveProgress();

        return (object)$this->response;
    }







    private function scanWpRootFolder(): bool
    {
 
        if (
            $this->isDirectoryExcluded($this->rootPath . 'wp-admin') &&
            $this->isDirectoryExcluded($this->rootPath . 'wp-includes')
        ) {
            $this->log("Skipping: /");
            return true;
        }

 
        $files = $this->open($this->filename, 'a');

        $this->log("Scanning / and its files");

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_ABSPATH);
            $this->setIsExcludedWpConfig();
 
            $this->options->totalFiles         = $this->scanToCacheFile($files, $this->rootPath, false, $this->getFilteredExcludedPaths(), $this->getFilteredExcludedFileSizes());
            $this->options->isExcludedWpConfig = $this->getIsExcludedWpConfig();
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }











    protected function scanWpContentSubDirectory(string $directory, string $pluginDir = WP_PLUGIN_DIR, string $wpRoot = WP_CONTENT_DIR): bool
    {
 
        if ($directory === $this->directory->getUploadsDirectory() && $this->options->uploadsSymlinked) {
            return true;
        }

        $directory = $this->filesystem->normalizePath($directory, true);
        $relPath   = str_replace($this->rootPath, '', $directory);

 
        if (!is_dir($directory)) {
            $this->log("Skipping: {$relPath} does not exist.");
            return true;
        }

 
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skipping: {$relPath}");
            return true;
        }

        $this->log("Scanning {$relPath}, its sub-directories and files");

 
        $files = $this->open($this->filename, 'a');

        $excludePaths = [
            rtrim($this->directory->getPluginUploadsDirectory(), '/')
        ];

 
        if ($directory === $this->filesystem->normalizePath($pluginDir, true)) {
            $excludePaths[] = '**/wp-staging*/**/node_modules'; 
 
            $excludePaths = array_merge($this->excludedPlugins->getPluginsToExcludeFullPath(), $excludePaths);
        }

 
        if (is_multisite() && is_main_site() && !$this->isNetworkClone()) {
            $excludePaths[] = $this->directory->getUploadsDirectory() . 'sites';
        }

        $excludePaths = array_merge($this->getFilteredExcludedPaths(), $excludePaths);

        $identifier = PathIdentifier::IDENTIFIER_WP_CONTENT;
        if ($wpRoot === ABSPATH) {
            $identifier = PathIdentifier::IDENTIFIER_ABSPATH;
        }

        try {
            $this->setPathIdentifier($identifier);
            $this->options->totalFiles += $this->scanToCacheFile($files, $directory, true, $excludePaths, $this->getFilteredExcludedFileSizes(), $wpRoot);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

 
        $this->close($files);
        return true;
    }






    private function scanWpContentFolder(): bool
    {
        $directory = WP_CONTENT_DIR;

        $directory = $this->filesystem->normalizePath($directory, true);
        $relPath   = str_replace($this->rootPath, '', $directory);

 
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skipping {$relPath} for other files.");
            return true;
        }

 
        $files = $this->open($this->filename, 'a');

        $this->log("Scanning {$relPath} for other directories and files");

        $excludePaths = [
            '**/wp-staging*/**/node_modules', 
            $this->directory->getWpContentDirectory() . 'cache',
            rtrim($this->directory->getPluginsDirectory(), '/'),
            rtrim($this->directory->getMuPluginsDirectory(), '/'),
            rtrim($this->directory->getUploadsDirectory(), '/'),
 
            rtrim($this->directory->getPluginUploadsDirectory(), '/'),
            rtrim($this->directory->getActiveThemeParentDirectory(), '/'),
 
            rtrim($this->directory->getStagingSiteDirectoryInsideWpcontent($createDir = false)),
        ];

 
        if (is_multisite() && !is_main_site()) {
            $excludePaths[] = $this->directory->getMainSiteUploadsDirectory();
        }

        $excludePaths = array_merge($this->getFilteredExcludedPaths(), $excludePaths);

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_WP_CONTENT);
            $this->options->totalFiles += $this->scanToCacheFile($files, $directory, true, $excludePaths, $this->getFilteredExcludedFileSizes(), WP_CONTENT_DIR);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

 
        $this->close($files);
        return true;
    }






    private function scanWpIncludesFolder(): bool
    {
        $directory = $this->rootPath . 'wp-includes';

        return $this->scanDirectoryAndWriteCacheFile($directory);
    }






    private function scanWpAdminFolder(): bool
    {
        $directory = $this->rootPath . 'wp-admin';

        return $this->scanDirectoryAndWriteCacheFile($directory);
    }









    private function scanExtraFolders(string $folder): bool
    {
        if (empty($folder)) {
            return true;
        }

        $absoluteExtraPath = '';
        try {
            $absoluteExtraPath = $this->pathAdapter->transformIdentifiableToPath($folder);
        } catch (Exception $ex) {
            $absoluteExtraPath = $this->rootPath . $folder;
        }

 
        if (in_array(trailingslashit($absoluteExtraPath), $this->directory->getDefaultWordPressFolders())) {
            return true;
        }

        if (!is_dir($absoluteExtraPath)) {
            return true;
        }

        $relativeExtraPath = str_replace($this->directory->getAbsPath(), '', $absoluteExtraPath);

 
        $files = $this->open($this->filename, 'a');
        $this->log("Scanning {$relativeExtraPath}, its sub-directories and files");

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_ABSPATH);
            $this->options->totalFiles += $this->scanToCacheFile($files, $absoluteExtraPath, true, $this->getFilteredExcludedPaths(), $this->getFilteredExcludedFileSizes());
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

 
        $this->close($files);
        return true;
    }







    public function close($handle): bool
    {
        return @fclose($handle);
    }









    public function open(string $file, string $mode)
    {
        $file_handle = @fopen($file, $mode);
        if ($file_handle === false) {
            $this->returnException(sprintf(__('Unable to open %s with mode %s', 'wp-staging'), $file, $mode));
        }

        return $file_handle;
    }









    public function write($handle, $content)
    {
        $write_result = @fwrite($handle, $content);
        if ($write_result === false) {
            if (($meta = \stream_get_meta_data($handle))) {
                throw new Exception(sprintf(__('Unable to write to: %s', 'wp-staging'), $meta['uri']));
            }
        } elseif ($write_result !== strlen($content)) {
            throw new Exception(__('Out of disk space.', 'wp-staging'));
        }

        return $write_result;
    }







    protected function execute(): bool
    {
 
        if ($this->isFinished()) {
            $this->prepareResponse(true, false);
            return false;
        }

        if ($this->options->currentStep == 0) {
            $this->scanWpRootFolder();
            $this->prepareResponse();
            return false;
        }

 
        if ($this->options->currentStep == 1) {
            $this->scanWpContentSubDirectory(WP_PLUGIN_DIR);
            $this->prepareResponse();
            return false;
        }

 
        if ($this->options->currentStep == 2) {
            $this->scanWpContentSubDirectory(WPMU_PLUGIN_DIR);
            $this->prepareResponse();
            return false;
        }

 
        if ($this->options->currentStep == 3) {
            $this->scanWpContentSubDirectory(WP_CONTENT_DIR . '/themes');
            $this->prepareResponse();
            return false;
        }

 
        if ($this->options->currentStep == 4) {
            $this->scanUploadsDirectory($this->directory->getUploadsDirectory());
            $this->prepareResponse();
            return false;
        }

 
        if ($this->options->currentStep == 5) {
            $this->scanWpContentFolder();
            $this->prepareResponse();
            return false;
        }

        if ($this->options->currentStep == 6) {
            $this->scanWpIncludesFolder();
            $this->prepareResponse();
            return false;
        }

        if ($this->options->currentStep == 7) {
            $this->scanWpAdminFolder();
            $this->prepareResponse();
            return false;
        }

        if (isset($this->options->extraDirectories[$this->options->currentStep - $this->total])) {
            $this->scanExtraFolders($this->options->extraDirectories[$this->options->currentStep - $this->total]);
            $this->prepareResponse();
            return false;
        }

 
        $this->prepareResponse();
 
        return true;
    }







    protected function scanUploadsDirectory(string $uploadsDir): bool
    {
        $wpContentDir = $this->directory->getWpContentDirectory();
 
        if (strpos($uploadsDir, $wpContentDir) === 0) {
            return $this->scanWpContentSubDirectory($uploadsDir);
        }

        return $this->scanWpContentSubDirectory($uploadsDir, $this->directory->getPluginsDirectory(), $this->directory->getAbsPath());
    }





    protected function isFinished(): bool
    {
        if ($this->options->currentStep >= $this->options->totalSteps) {
            return true;
        }

        return false;
    }






    protected function saveProgress(): bool
    {
        return $this->saveOptions();
    }







    protected function isDirectoryExcluded(string $directory): bool
    {
        if (empty($this->excludedPaths)) {
            $this->excludedPaths = $this->options->excludedDirectories;
        }

        return $this->pathChecker->isPathInPathsList($directory, $this->excludedPaths);
    }





    protected function getFilteredExcludedFileSizes(): array
    {
        return Hooks::applyFilters(self::FILTER_CLONE_EXCLUDED_FILE_SIZE, $this->options->excludeSizeRules);
    }







    protected function getFilteredExcludedPaths(): array
    {
        $excludePaths = [];
        $abspath      = $this->rootPath;
        foreach ($this->options->excludedDirectories as $excludedDirectory) {
            $directory = $this->filesystem->normalizePath($excludedDirectory);
            if ($this->strUtils->startsWith($directory, $abspath)) {
                $excludePaths[] = $directory;
                continue;
            }

            if ($this->strUtils->startsWith($directory, '/')) {
                $directory = ltrim($directory, '/');
            }

            try {
                $excludePaths[] = $this->pathAdapter->transformIdentifiableToPath($directory);
            } catch (UnexpectedValueException $ex) {
                $excludePaths[] = '/' . $directory;
            }
        }

        if ($this->isMultisiteAndPro()) {
            $excludePaths = apply_filters(Directory::FILTER_CLONE_MU_EXCLUDED_FOLDERS, $excludePaths);
        } else {
            $excludePaths = apply_filters(Directory::FILTER_CLONE_EXCLUDED_FOLDERS, $excludePaths);
        }

        $excludeFilters   = new ExcludeFilter();
        $excludeGlobRules = array_map(function ($rule) use ($excludeFilters) {
            return $excludeFilters->mapExclude($rule);
        }, $this->options->excludeGlobRules);

        return array_merge($excludePaths, $excludeGlobRules);
    }






    private function scanDirectoryAndWriteCacheFile(string $directory): bool
    {
        $directory = $this->filesystem->normalizePath($directory, true);
        $relPath   = str_replace($this->rootPath, '', $directory);

 
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skipping " . $relPath);
            return true;
        }

 
        $files = $this->open($this->filename, 'a');

        $this->log("Scanning " . $relPath . ", its sub-directories and files");

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_ABSPATH);
            $this->options->totalFiles += $this->scanToCacheFile($files, $directory, true);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

 
        $this->close($files);
        return true;
    }
}
