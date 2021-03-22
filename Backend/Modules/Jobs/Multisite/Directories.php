<?php

namespace WPStaging\Backend\Modules\Jobs\Multisite;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use Exception;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Core\Iterators\RecursiveDirectoryIterator;
use WPStaging\Core\Iterators\RecursiveFilterExclude;
use WPStaging\Backend\Modules\Jobs\JobExecutable;

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Directories
 */
class Directories extends JobExecutable
{

    /**
     * @var array
     */
    private $files = [];

    /**
     * Total steps to do
     * @var int
     */
    private $total = 5;

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

        return ( object )$this->response;
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

        try {

            // Iterate over wp root directory
            $iterator = new \DirectoryIterator(WPStaging::getWPpath());

            $this->log("Scanning / for files");

            // Write path line
            foreach ($iterator as $item) {
                if (!$item->isDot() && $item->isFile()) {
                    if ($this->write($files, $iterator->getFilename() . PHP_EOL)) {
                        $this->options->totalFiles++;
                    }
                }
            }
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        $this->close($files);
        return true;
    }

    /**
     * Step 2
     * Get WP Content Files without multisite folder wp-content/uploads/sites or wp-content/blogs.dir/
     */
    private function getWpContentFiles()
    {

        // Skip it
        if ($this->isDirectoryExcluded(WP_CONTENT_DIR)) {
            $this->log("Skip " . WPStaging::getWPpath() . WP_CONTENT_DIR);
            return true;
        }
        // open file handle
        $files = $this->open($this->filename, 'a');

        /**
         * Excluded folders relative to the folder to iterate
         */
        $excludePaths = [
            'cache',
            'plugins/wps-hide-login',
            'uploads/sites',
            'blogs.dir'
        ];

        /**
         * Get user excluded folders
         */
        $directory = [];
        foreach ($this->options->excludedDirectories as $dir) {
            $dir = wpstg_replace_windows_directory_separator($dir);
            $wpContentDir = wpstg_replace_windows_directory_separator(WP_CONTENT_DIR);

            if (strpos($dir, $wpContentDir) !== false) {
                $directory[] = ltrim(str_replace($wpContentDir, '', $dir), '/\\');
            }
        }

        $excludePaths = array_merge($excludePaths, $directory);

        try {

            // Iterate over content directory
            $iterator = new RecursiveDirectoryIterator(WP_CONTENT_DIR);

            // Exclude sites, uploads, plugins or themes
            $iterator = new RecursiveFilterExclude($iterator, apply_filters('wpstg_clone_mu_excl_folders', $excludePaths));

            // Recursively iterate over content directory
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD);

            $this->log("Scanning /wp-content folder " . WP_CONTENT_DIR);

            // Write path line
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $file = 'wp-content/' . $iterator->getSubPathName() . PHP_EOL;
                    if ($this->write($files, $file)) {
                        $this->options->totalFiles++;
                    }
                }
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

        // Skip it
        if ($this->isDirectoryExcluded(WPStaging::getWPpath() . 'wp-includes/')) {
            $this->log("Skip " . WPStaging::getWPpath() . 'wp-includes/');
            return true;
        }

        // open file handle and attach data to end of file
        $files = $this->open($this->filename, 'a');

        try {

            // Iterate over wp-admin directory
            $iterator = new RecursiveDirectoryIterator(WPStaging::getWPpath() . 'wp-includes/');

            // Recursively iterate over wp-includes directory
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD);

            $this->log("Scanning /wp-includes for its sub-directories and files");

            // Write files
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    if ($this->write($files, 'wp-includes/' . $iterator->getSubPathName() . PHP_EOL)) {
                        $this->options->totalFiles++;
                    }
                }
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

        // Skip it
        if ($this->isDirectoryExcluded(WPStaging::getWPpath() . 'wp-admin/')) {
            $this->log("Skip " . WPStaging::getWPpath() . 'wp-admin/');
            return true;
        }

        // open file handle and attach data to end of file
        $files = $this->open($this->filename, 'a');

        try {

            // Iterate over wp-admin directory
            $iterator = new RecursiveDirectoryIterator(WPStaging::getWPpath() . 'wp-admin/');

            // Recursively iterate over content directory
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD);

            $this->log("Scanning /wp-admin for its sub-directories and files");

            // Write path line
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    if ($this->write($files, 'wp-admin/' . $iterator->getSubPathName() . PHP_EOL)) {
                        $this->options->totalFiles++;
                        // Too much cpu time
                        //$this->options->totalFileSize += $iterator->getSize();
                    }
                }
            }
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Step 4
     * Get WP Content Uploads Files multisite folder wp-content/uploads/sites or wp-content/blogs.dir/ID/files
     */
    private function getWpContentUploadsSites()
    {

        // Skip if main site is cloned
        if (is_main_site()) {
            return true;
        }

        // Absolute path to uploads folder
        $path = $this->getAbsUploadPath();

        // Skip it
        if (!is_dir($path)) {
            $this->log("Skipping: {$path} does not exist.");
            return true;
        }

        // Skip it
        if ($this->isDirectoryExcluded($path)) {
            $this->log("Skipping: {$path}");
            return true;
        }


        // open file handle
        $files = $this->open($this->filename, 'a');

        /**
         * Excluded folders relative to the folder to iterate
         */
        $excludePaths = [
            'cache',
            'plugins/wps-hide-login',
            'uploads/sites'
        ];

        /**
         * Get user excluded folders
         */
        $directory = [];
        foreach ($this->options->excludedDirectories as $dir) {
            $path = wpstg_replace_windows_directory_separator($path);
            $dir = wpstg_replace_windows_directory_separator($dir);
            if (strpos($dir, $path) !== false) {
                $directory[] = ltrim(str_replace($path, '', $dir), '/');
            }
        }

        $excludePaths = array_merge($excludePaths, $directory);

        try {

            // Iterate over content directory
            $iterator = new RecursiveDirectoryIterator($path);

            // Exclude sites, uploads, plugins or themes
            $iterator = new RecursiveFilterExclude($iterator, $excludePaths);

            // Recursively iterate over content directory
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD);
            $uploadDir = wpstg_get_upload_dir();
            $this->log("Scanning {$uploadDir} for its sub-directories and files...");

            // Write path line
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $file = $this->getRelUploadPath() . $iterator->getSubPathName() . PHP_EOL;
                    if ($this->write($files, $file)) {
                        $this->options->totalFiles++;
                    }
                }
            }
        } catch (Exception $e) {
            $this->returnException('Error: ' . $e->getMessage());
        }

        // close the file handler
        $this->close($files);
        return true;
    }

    /**
     * Get absolute path to the upload folder e.g. /srv/www/wp-content/blogs.dir/ID/files or /srv/www/wp-content/uploads/sites/ID/
     * @return string
     */
    private function getAbsUploadPath()
    {
        // Check first which method is used 
        $uploads = wp_upload_dir();
        $basedir = $uploads['basedir'];

        return trailingslashit($basedir);
    }

    /**
     * Get relative path to the upload folder like wp-content/uploads or wp-content/blogs.dir/2/files
     * @return string
     */
    private function getRelUploadPath()
    {
        $uploads = wp_upload_dir();
        $basedir = $uploads['basedir'];

        return trailingslashit(str_replace(wpstg_replace_windows_directory_separator(WPStaging::getWPpath()), null, wpstg_replace_windows_directory_separator($basedir)));
    }

    /**
     * Step 5 - x
     * Get extra folders of the wp root level
     * Does not collect wp-includes, wp-admin and wp-content folder
     */
    private function getExtraFiles($folder)
    {

        if (!is_dir($folder)) {
            return true;
        }

        // open file handle and attach data to end of file
        $files = $this->open($this->filename, 'a');

        try {

            // Iterate over extra directory
            $iterator = new RecursiveDirectoryIterator($folder);

            $exclude = [];

            $iterator = new RecursiveFilterExclude($iterator, $exclude);
            // Recursively iterate over content directory
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::LEAVES_ONLY, \RecursiveIteratorIterator::CATCH_GET_CHILD);

            $strings = new Strings();
            $this->log("Scanning {$strings->getLastElemAfterString( '/', $folder )} for its sub-directories and files");

            // Write path line
            foreach ($iterator as $item) {
                if ($item->isFile()) {
                    $path = str_replace(wpstg_replace_windows_directory_separator(WPStaging::getWPpath()), '', wpstg_replace_windows_directory_separator($folder)) . DIRECTORY_SEPARATOR . $iterator->getSubPathName() . PHP_EOL;
                    if ($this->write($files, $path)) {
                        $this->options->totalFiles++;
                    }
                }
            }
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

        if ($this->options->currentStep == 4) {
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
     * Replace forward slash with current directory separator
     *
     * @param string $path Path
     *
     * @return string
     */
    private function sanitizeDirectorySeparator($path)
    {
        $string = str_replace("/", "\\", $path);
        return str_replace('\\\\', '\\', $string);
    }

    /**
     * Check if directory is excluded
     * @param string $directory
     * @return bool
     */
    protected function isDirectoryExcluded($directory)
    {
        $directory = $this->sanitizeDirectorySeparator($directory);
        foreach ($this->options->excludedDirectories as $excludedDirectory) {
            $excludedDirectory = $this->sanitizeDirectorySeparator($excludedDirectory);
            if (strpos(trailingslashit($directory), trailingslashit($excludedDirectory)) === 0) {
                return true;
            }
        }

        return false;
    }

}
