<?php

namespace WPStaging\Backend\Modules\Jobs;

use Exception;
use UnexpectedValueException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Traits\FileScanToCacheTrait;
use WPStaging\Framework\Utils\Strings;

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Directories
 * @todo DRY this class and WPStaging\Backend\Pro\Modules\Directories class
 */
class Directories extends JobExecutable
{
    use FileScanToCacheTrait;

    /** @var ExcludedPlugins */
    protected $excludedPlugins;

    /**
     * @var array
     */
    private $files = [];

    /**
     * Total steps to do
     * @var int
     */
    private $total = 8;

    /**
     * path to the cache file
     * @var string
     */
    private $filename;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var PathIdentifier
     */
    private $pathAdapter;

    /**
     * @var Directory
     */
    private $directory;

    /** @var PathChecker */
    private $pathChecker;

    /**
     * @var string
     */
    private $rootPath;

    /** @var array */
    private $excludedPaths = [];

    /**
     * @return void
     */
    public function initialize()
    {
        $this->filesystem      = WPStaging::make(Filesystem::class);
        /** @var Directory */
        $this->directory       = WPStaging::make(Directory::class);
        $this->pathAdapter     = WPStaging::make(PathIdentifier::class);
        $this->pathChecker     = WPStaging::make(PathChecker::class);
        $this->filename        = $this->getFilesIndexCacheFilePath();
        $this->strUtils        = new Strings();
        $this->rootPath        = $this->filesystem->normalizePath($this->directory->getAbsPath());
        $this->excludedPlugins = new ExcludedPlugins($this->directory);

        $this->filesIndexCache->initWithPhpHeader();
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = $this->total + count($this->options->extraDirectories);
    }

    /**
     * Start Module
     * @return object
     * @throws Exception
     */
    public function start()
    {
        // Execute steps
        $this->run();

        // Save option, progress
        $this->saveProgress();

        return (object)$this->response;
    }

    /**
     * Step 0
     * Get WP Root files
     * Does not collect any sub folders
     * @throws Exception
     */
    private function scanWpRootFolder(): bool
    {
        // Skip scanning the root directory if all other directories are unselected
        if (
            $this->isDirectoryExcluded($this->rootPath . 'wp-admin') &&
            $this->isDirectoryExcluded($this->rootPath . 'wp-includes')
        ) {
            $this->log("Skipping: /");
            return true;
        }

        // open file handle
        $files = $this->open($this->filename, 'a');

        $this->log("Scanning / and its files");

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_ABSPATH);
            $this->setIsExcludedWpConfig();
            // Iterate over wp root directory
            $this->options->totalFiles         = $this->scanToCacheFile($files, $this->rootPath, false, $this->getFilteredExcludedPaths(), $this->getFilteredExcludedFileSizes());
            $this->options->isExcludedWpConfig = $this->getIsExcludedWpConfig();
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

    /**
     * Step 1-4
     * Scan plugins, mu-plugins, themes or uploads dir depending upon the input
     *
     * @param string $directory
     * @param string $pluginDir - default const WP_PLUGIN_DIR  - testing purpose only
     * @param string $wpRoot - default const WP_CONTENT_DIR - testing purpose only
     * @return bool
     * @throws Exception
     */
    protected function scanWpContentSubDirectory(string $directory, string $pluginDir = WP_PLUGIN_DIR, string $wpRoot = WP_CONTENT_DIR): bool
    {
        // Skip if scanning uploads directory and symlink option selected
        if ($directory === $this->directory->getUploadsDirectory() && $this->options->uploadsSymlinked) {
            return true;
        }

        $directory = $this->filesystem->normalizePath($directory, true);
        $relPath   = str_replace($this->rootPath, '', $directory);

        // Skip it
        if (!is_dir($directory)) {
            $this->log("Skipping: {$relPath} does not exist.");
            return true;
        }

        // Skip it
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skipping: {$relPath}");
            return true;
        }

        $this->log("Scanning {$relPath}, its sub-directories and files");

        // open file handle
        $files = $this->open($this->filename, 'a');

        $excludePaths = [
            rtrim($this->directory->getPluginUploadsDirectory(), '/')
        ];

        // Exclude predefined plugins if given directory is plugins dir
        if ($directory === $this->filesystem->normalizePath($pluginDir, true)) {
            $excludePaths[] = '**/wp-staging*/**/node_modules'; // only exclude node modules in WP Staging's plugins
            // add excluded plugins defined by WP Staging
            $excludePaths = array_merge($this->excludedPlugins->getPluginsToExcludeFullPath(), $excludePaths);
        }

        // Exclude subsite uploads if current site is multisite and main site but clone is not a network clone
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

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Step 5
     * Scan and collect WP Content Files for other files than plugins, mu plugins, themes and uploads.
     * @throws Exception
     */
    private function scanWpContentFolder(): bool
    {
        $directory = WP_CONTENT_DIR;

        $directory = $this->filesystem->normalizePath($directory, true);
        $relPath   = str_replace($this->rootPath, '', $directory);

        // Skip it
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skipping {$relPath} for other files.");
            return true;
        }

        // open file handle
        $files = $this->open($this->filename, 'a');

        $this->log("Scanning {$relPath} for other directories and files");

        $excludePaths = [
            '**/wp-staging*/**/node_modules', // only exclude node modules in WP Staging's plugins
            $this->directory->getWpContentDirectory() . 'cache',
            rtrim($this->directory->getPluginsDirectory(), '/'),
            rtrim($this->directory->getMuPluginsDirectory(), '/'),
            rtrim($this->directory->getUploadsDirectory(), '/'),
            // In case the wp staging uploads directory is outside the uploads directory
            rtrim($this->directory->getPluginUploadsDirectory(), '/'),
            rtrim($this->directory->getActiveThemeParentDirectory(), '/'),
            // To not copy the wp-staging sites directory inside wp-content
            rtrim($this->directory->getStagingSiteDirectoryInsideWpcontent($createDir = false)),
        ];

        // Exclude main uploads directory if multisite and not main site
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

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Step 6
     * @return bool
     * @throws Exception
     */
    private function scanWpIncludesFolder(): bool
    {
        $directory = $this->rootPath . 'wp-includes';

        return $this->scanDirectoryAndWriteCacheFile($directory);
    }

    /**
     * Step 7
     * @return bool
     * @throws Exception
     */
    private function scanWpAdminFolder(): bool
    {
        $directory = $this->rootPath . 'wp-admin';

        return $this->scanDirectoryAndWriteCacheFile($directory);
    }

    /**
     * Step 8 - x
     * Scan extra folders on the wp root level
     * Does not collect wp-includes, wp-admin and wp-content folder
     * @param string $folder
     * @return bool
     * @throws Exception
     */
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

        // Skip if extra path belongs to default WordPress folders. Can be the case if extra path is the path to a custom uploads dir inside wp-content.
        if (in_array(trailingslashit($absoluteExtraPath), $this->directory->getDefaultWordPressFolders())) {
            return true;
        }

        if (!is_dir($absoluteExtraPath)) {
            return true;
        }

        $relativeExtraPath = str_replace($this->directory->getAbsPath(), '', $absoluteExtraPath);

        // open file handle and attach data to end of file
        $files = $this->open($this->filename, 'a');
        $this->log("Scanning {$relativeExtraPath}, its sub-directories and files");

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_ABSPATH);
            $this->options->totalFiles += $this->scanToCacheFile($files, $absoluteExtraPath, true, $this->getFilteredExcludedPaths(), $this->getFilteredExcludedFileSizes());
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Closes a file handle
     *
     * @param resource $handle File handle to close
     * @return bool
     */
    public function close($handle): bool
    {
        return @fclose($handle);
    }

    /**
     * Opens a file in specified mode
     *
     * @param string $file Path to the file to open
     * @param string $mode Mode in which to open the file
     * @return resource
     * @throws Exception
     */
    public function open(string $file, string $mode)
    {
        $file_handle = @fopen($file, $mode);
        if ($file_handle === false) {
            $this->returnException(sprintf(__('Unable to open %s with mode %s', 'wp-staging'), $file, $mode));
        }

        return $file_handle;
    }

    /**
     * Write contents to a file
     *
     * @param resource $handle File handle to write to
     * @param string $content Contents to write to the file
     * @return int
     * @throws Exception
     */
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

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     * @throws Exception
     */
    protected function execute(): bool
    {
        // No job left to execute
        if ($this->isFinished()) {
            $this->prepareResponse(true, false);
            return false;
        }

        if ($this->options->currentStep == 0) {
            $this->scanWpRootFolder();
            $this->prepareResponse();
            return false;
        }

        // Scan Plugins directory
        if ($this->options->currentStep == 1) {
            $this->scanWpContentSubDirectory(WP_PLUGIN_DIR);
            $this->prepareResponse();
            return false;
        }

        // Scan Mu Plugins directory
        if ($this->options->currentStep == 2) {
            $this->scanWpContentSubDirectory(WPMU_PLUGIN_DIR);
            $this->prepareResponse();
            return false;
        }

        // Scan Themes directory
        if ($this->options->currentStep == 3) {
            $this->scanWpContentSubDirectory(WP_CONTENT_DIR . '/themes');
            $this->prepareResponse();
            return false;
        }

        // Scan Uploads directory
        if ($this->options->currentStep == 4) {
            $this->scanUploadsDirectory($this->directory->getUploadsDirectory());
            $this->prepareResponse();
            return false;
        }

        // Scan WP Content directory except plugins, mu-plugins, themes and uploads directory
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

        // Prepare response
        $this->prepareResponse();
        // Not finished
        return true;
    }

    /**
     * Scan uploads directory
     * @param string $uploadsDir
     * @return bool
     * @throws Exception
     */
    protected function scanUploadsDirectory(string $uploadsDir): bool
    {
        $wpContentDir = $this->directory->getWpContentDirectory();
        // When uploads dir is inside wp-content dir
        if (strpos($uploadsDir, $wpContentDir) === 0) {
            return $this->scanWpContentSubDirectory($uploadsDir);
        }

        return $this->scanWpContentSubDirectory($uploadsDir, $this->directory->getPluginsDirectory(), $this->directory->getAbsPath());
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    protected function isFinished(): bool
    {
        if ($this->options->currentStep >= $this->options->totalSteps) {
            return true;
        }

        return false;
    }

    /**
     * Save files
     * @return bool
     * @throws Exception
     */
    protected function saveProgress(): bool
    {
        return $this->saveOptions();
    }

    /**
     * Check if directory is excluded
     * @param string $directory
     *
     * @return bool
     */
    protected function isDirectoryExcluded(string $directory): bool
    {
        if (empty($this->excludedPaths)) {
            $this->excludedPaths = $this->options->excludedDirectories;
        }

        return $this->pathChecker->isPathInPathsList($directory, $this->excludedPaths);
    }

    /**
     * Return List of all user defined file size excludes from hooks and through UI
     * @return array
     */
    protected function getFilteredExcludedFileSizes(): array
    {
        return apply_filters('wpstg_clone_file_size_exclude', $this->options->excludeSizeRules);
    }

    /**
     * Return list of all exclude rules and exclude paths,
     * Defined by user in hooks or through UI
     * Defined by WP Staging i.e. cache or some plugins.
     * @return array
     */
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
            $excludePaths = apply_filters('wpstg_clone_mu_excl_folders', $excludePaths);
        } else {
            $excludePaths = apply_filters('wpstg_clone_excl_folders', $excludePaths);
        }

        $excludeFilters   = new ExcludeFilter();
        $excludeGlobRules = array_map(function ($rule) use ($excludeFilters) {
            return $excludeFilters->mapExclude($rule);
        }, $this->options->excludeGlobRules);

        return array_merge($excludePaths, $excludeGlobRules);
    }

    /**
     * @param string $directory
     * @return true
     * @throws Exception
     */
    private function scanDirectoryAndWriteCacheFile(string $directory): bool
    {
        $directory = $this->filesystem->normalizePath($directory, true);
        $relPath   = str_replace($this->rootPath, '', $directory);

        // Skip it
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skipping " . $relPath);
            return true;
        }

        // open file handle
        $files = $this->open($this->filename, 'a');

        $this->log("Scanning " . $relPath . ", its sub-directories and files");

        try {
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_ABSPATH);
            $this->options->totalFiles += $this->scanToCacheFile($files, $directory, true);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        // close the file handler
        $this->close($files);
        return true;
    }
}
