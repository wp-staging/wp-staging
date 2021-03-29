<?php

namespace WPStaging\Backend\Modules\Jobs;

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\CloningProcess\ExcludedPlugins;
use WPStaging\Framework\Traits\FileScanToCacheTrait;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Directories
 */
class Directories extends JobExecutable
{
    use FileScanToCacheTrait;

    /**
     * @var array
     */
    private $files = [];

    /**
     * Total steps to do
     * @var int
     */
    private $total = 4;

    /**
     * path to the cache file
     * @var string
     */
    private $filename;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->filename = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        // Set total to 5 for multisite
        if ($this->isMultisiteAndPro()) {
            $this->total = 5;
        }

        $this->options->totalSteps = $this->total + count($this->options->extraDirectories);
    }

    /**
     * Start Module
     * @return object
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
     */
    private function getWpRootFiles()
    {
        // open file handle
        $files = $this->open($this->filename, 'a');

        $this->log("Scanning / for its files");

        try {
            // Iterate over wp root directory
            $this->options->totalFiles = $this->scanToCacheFile($files, ABSPATH);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

    /**
     * Step 1
     * Get WP Content Files
     */
    private function getWpContentFiles()
    {
        $directory = WP_CONTENT_DIR;
        // Skip it
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skip " . $directory);
            return true;
        }
        // open file handle
        $files = $this->open($this->filename, 'a');

        $relativeDirectory = str_replace(ABSPATH, '', $directory);
        $this->log("Scanning " . $relativeDirectory . " for its sub-directories and files");

        $paths = $this->filteredSelectedDirectories($directory, $this->options->includedDirectories);

        $excludePaths = [
            trailingslashit(WP_CONTENT_DIR) . 'uploads/sites',
            trailingslashit(WP_CONTENT_DIR) . 'cache',
            rtrim(WPStaging::getContentDir(), '/'),
            '**/node_modules',
        ];
        // add excluded plugins defined by WP Staging
        $excludePaths = array_merge((new ExcludedPlugins())->getPluginsToExcludeWithAbsolutePaths(), $excludePaths);
        $excludePaths = array_merge($this->options->excludedDirectories, $excludePaths);
        if ($this->isMultisiteAndPro()) {
            $excludePaths = apply_filters('wpstg_clone_mu_excl_folders', $excludePaths);
        } else {
            $excludePaths = apply_filters('wpstg_clone_excl_folders', $excludePaths);
        }

        try {
            foreach ($paths as $path) {
                $this->options->totalFiles += $this->scanToCacheFile($files, $path->path, $path->flag === Scan::IS_RECURSIVE, $excludePaths);
            }
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Step 2
     * @return boolean
     * @throws Exception
     */
    private function getWpIncludesFiles()
    {
        $directory = ABSPATH . 'wp-includes';
        // Skip it
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skip " . $directory);
            return true;
        }
        // open file handle
        $files = $this->open($this->filename, 'a');

        $relativeDirectory = str_replace(ABSPATH, '', $directory);
        $this->log("Scanning " . $relativeDirectory . " for its sub-directories and files");

        $paths = $this->filteredSelectedDirectories($directory, $this->options->includedDirectories);
        try {
            foreach ($paths as $path) {
                $this->options->totalFiles += $this->scanToCacheFile($files, $path->path, $path->flag === Scan::IS_RECURSIVE);
            }
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Step 3
     * @return boolean
     * @throws Exception
     */
    private function getWpAdminFiles()
    {
        $directory = ABSPATH . 'wp-admin';
        // Skip it
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skip " . $directory);
            return true;
        }
        // open file handle
        $files = $this->open($this->filename, 'a');

        $relativeDirectory = str_replace(ABSPATH, '', $directory);
        $this->log("Scanning " . $relativeDirectory . " for its sub-directories and files");

        $paths = $this->filteredSelectedDirectories($directory, $this->options->includedDirectories);
        try {
            foreach ($paths as $path) {
                $this->options->totalFiles += $this->scanToCacheFile($files, $path->path, $path->flag === Scan::IS_RECURSIVE);
            }
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Step 4 (Multisite Only)
     * Get WP Content Uploads Files multisite folder wp-content/uploads/sites or wp-content/blogs.dir/ID/files
     */
    private function getWpContentUploadsSites()
    {
        // Skip if main site is cloned
        if (is_main_site()) {
            return true;
        }

        // Skip if symlink option selected
        if ($this->options->uploadsSymlinked) {
            return true;
        }

        // Absolute path to uploads folder
        $directory = (new WpDefaultDirectories())->getUploadsPath();

        // Skip it
        if (!is_dir($directory)) {
            $this->log("Skipping: {$directory} does not exist.");
            return true;
        }

        // Skip it
        if ($this->isDirectoryExcluded($directory)) {
            $this->log("Skipping: {$directory}");
            return true;
        }


        // open file handle
        $files = $this->open($this->filename, 'a');

        $excludePaths = [
            '**/node_modules',
        ];
        $excludePaths = array_merge($this->options->excludedDirectories, $excludePaths);

        try {
            $this->options->totalFiles += $this->scanToCacheFile($files, $directory, true, $excludePaths);
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Step 4 - x (Single Site)
     * Step 5 - x (Multisite)
     * Get extra folders of the wp root level
     * Does not collect wp-includes, wp-admin and wp-content folder
     * @param string $folder
     * @return boolean
     */
    private function getExtraFiles($folder)
    {

        if (!is_dir($folder)) {
            return true;
        }

        // open file handle and attach data to end of file
        $files = $this->open($this->filename, 'a');
        $strUtil = new Strings();
        $this->log("Scanning {$strUtil->getLastElemAfterString( '/', $folder )} for its sub-directories and files");

        try {
            $this->options->totalFiles += $this->scanToCacheFile($files, $folder, true, []);
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
     * @return boolean
     */
    public function close($handle)
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
    public function open($file, $mode)
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
     * @return integer
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
     */
    protected function execute()
    {

        // No job left to execute
        if ($this->isFinished()) {
            $this->prepareResponse(true, false);
            return false;
        }


        if ($this->options->currentStep == 0) {
            $this->getWpRootFiles();
            $this->prepareResponse(false, true);
            return false;
        }

        if ($this->options->currentStep == 1) {
            $this->getWpContentFiles();
            $this->prepareResponse(false, true);
            return false;
        }

        if ($this->options->currentStep == 2) {
            $this->getWpIncludesFiles();
            $this->prepareResponse(false, true);
            return false;
        }

        if ($this->options->currentStep == 3) {
            $this->getWpAdminFiles();
            $this->prepareResponse(false, true);
            return false;
        }

        if ($this->isMultisiteAndPro() && $this->options->currentStep == 4) {
            $this->getWpContentUploadsSites();
            $this->prepareResponse(false, true);
            return false;
        }

        if (isset($this->options->extraDirectories[$this->options->currentStep - $this->total])) {
            $this->getExtraFiles($this->options->extraDirectories[$this->options->currentStep - $this->total]);
            $this->prepareResponse(false, true);
            return false;
        }


        // Prepare response
        $this->prepareResponse(false, true);
        // Not finished
        return true;
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    protected function isFinished()
    {
        if ($this->options->currentStep >= $this->options->totalSteps) {
            return true;
        }

        return false;
    }

    /**
     * Save files
     * @return bool
     */
    protected function saveProgress()
    {
        return $this->saveOptions();
    }

    /**
     * Get files
     * @return void
     */
    protected function getFiles()
    {
        $fileName = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();

        if (($this->files = @file_get_contents($fileName)) === false) {
            $this->files = [];
            return;
        }

        $this->files = explode(PHP_EOL, $this->files);
    }

    /**
     * Check if directory is excluded
     * @param string $directory
     * @return bool
     */
    protected function isDirectoryExcluded($directory)
    {
        $directory = (new Strings())->sanitizeDirectorySeparator($directory);
        // check if directory is in selected included directory
        foreach ($this->options->includedDirectories as $includedDirectory) {
            $includedDirectory = trim($includedDirectory, ' ');
            $directoryPath = explode(Scan::DIRECTORY_PATH_FLAG_SEPARATOR, $includedDirectory)[0];
            $directoryPath = trim($directoryPath, ' ');
            $directoryPath = (new Strings())->sanitizeDirectorySeparator($directoryPath);
            if (strpos(trailingslashit($directoryPath), trailingslashit($directory)) === 0) {
                return false;
            }
        }

        return true;
    }
}
