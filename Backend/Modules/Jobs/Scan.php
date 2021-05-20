<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use Countable;
use DirectoryIterator;
use Exception;
use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Core\Utils\Directories as DirectoriesUtil;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Database\LegacyDatabaseInfo;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Class Scan
 * @package WPStaging\Backend\Modules\Jobs
 *
 * @todo replace WPStaging::getWPpath() with ABSPATH - separate PR
 */
class Scan extends Job
{

    /**
     * Class to use for WordPress core directories like wp-content, wp-admin, wp-includes
     * This doesn't contains class selector prefix
     *
     * @var string
     */
    const WP_CORE_DIR = "wpstg-wp-core-dir";

    /**
     * Class to use for WordPress non core directories
     * This doesn't contains class selector prefix
     *
     * @var string
     */
    const WP_NON_CORE_DIR = "wpstg-wp-non-core-dir";

    /** @var array */
    private $directories = [];

    /** @var DirectoriesUtil */
    private $objDirectories;

    /**
     * @var string
     */
    private $directoryToScanOnly;

    /**
     * @var string Path to gif loader for directory loading
     */
    private $gifLoaderPath;

    public function __construct($directoryToScanOnly = null)
    {
        // Accept both the absolute path or relative path with respect to wp root
        // Santized the path to make comparing works for windows platform too.
        $this->directoryToScanOnly = null;
        if ($directoryToScanOnly !== null) {
            $this->directoryToScanOnly = $directoryToScanOnly;
        }

        parent::__construct();
    }

    /**
     * @param $string $gifLoaderPath
     */
    public function setGifLoaderPath($gifLoaderPath)
    {
        $this->gifLoaderPath = $gifLoaderPath;
    }

    /**
     * Upon class initialization
     */
    protected function initialize()
    {
        $this->objDirectories = new DirectoriesUtil();

        if ($this->directoryToScanOnly !== null) {
            $this->getDirectories($this->directoryToScanOnly);
            return;
        }

        $this->getTables();

        $this->getDirectories();

        $this->installOptimizer();
    }

    /**
     * Start Module
     * @return $this|object
     * @throws Exception
     */
    public function start()
    {
        // Basic Options
        $this->options->root           = str_replace(["\\", '/'], DIRECTORY_SEPARATOR, WPStaging::getWPpath());
        $this->options->existingClones = get_option("wpstg_existing_clones_beta", []);
        $this->options->current        = null;

        if (isset($_POST["clone"]) && array_key_exists($_POST["clone"], $this->options->existingClones)) {
            $this->options->current = $_POST["clone"];
            $this->options->currentClone = $this->options->existingClones[$this->options->current];
        }

        // Tables
        $this->options->clonedTables = [];

        // Files
        $this->options->totalFiles    = 0;
        $this->options->totalFileSize = 0;
        $this->options->copiedFiles   = 0;


        // Directories
        $this->options->includedDirectories      = [];
        $this->options->includedExtraDirectories = [];
        $this->options->excludedDirectories      = [];
        $this->options->extraDirectories         = [];
        $this->options->scannedDirectories       = [];

        // Job
        $this->options->currentJob  = "PreserveDataFirstStep";
        $this->options->currentStep = 0;
        $this->options->totalSteps  = 0;

        // Define mainJob to differentiate between cloning, updating and pushing
        $this->options->mainJob = 'cloning';
        $job = '';
        if (isset($_POST["job"])) {
            $job = $_POST['job'];
        }

        if ($this->options->current !== null && $job === 'resetting') {
            $this->options->mainJob = 'resetting';
        } elseif ($this->options->current !== null) {
            $this->options->mainJob = 'updating';
        }

        // Delete previous cached files
        $this->cache->delete("files_to_copy");
        $this->cache->delete("clone_options");

        $this->saveOptions();

        return $this;
    }

    /**
     * Make sure the Optimizer mu plugin is installed before cloning or pushing
     */
    private function installOptimizer()
    {
        $optimizer = new Optimizer();
        $optimizer->installOptimizer();
    }

    /**
     * Format bytes into human readable form
     * @param float $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize($bytes, $precision = 2)
    {
        if ((double) $bytes < 1) {
            return '';
        }

        $units = ['B', "KB", "MB", "GB", "TB"];

        $bytes = (double) $bytes;
        $base  = log($bytes) / log(1000); // 1024 would be for MiB KiB etc
        $pow   = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $precision) . ' ' . $units[(int) floor($base)];
    }

    /**
     * @param null|bool $parentChecked  Is parent folder selected
     * @param bool $forceDefault        Default false. Set it to true,
     *                                  when default button on ui is clicked,
     *                                  to ignore previous selected option for UPDATE and RESET process.
     *
     * @return string
     *
     * @todo create a template for ui
     */
    public function directoryListing($parentChecked = null, $forceDefault = false)
    {
        $directories = $this->directories;
        // Sort results
        uksort($directories, 'strcasecmp');

        $excludedDirectories = [];
        $extraDirectories    = [];

        if ($this->isUpdateOrResetJob()) {
            $currentClone        = json_decode(json_encode($this->options->currentClone));
            $excludedDirectories = isset($currentClone->excludedDirectories) ? $currentClone->excludedDirectories : [];
            $extraDirectories    = isset($currentClone->extraDirectories) ? $currentClone->extraDirectories : [];
        }

        $output = '';
        foreach ($directories as $name => $directory) {
            // Not a directory, possibly a symlink, therefore we will skip it
            if (!is_array($directory)) {
                continue;
            }

            // Need to preserve keys so no array_shift()
            $data = reset($directory);
            unset($directory[key($directory)]);

            $dataPath = isset($data["path"]) ? $data["path"] : '';
            $dataSize = isset($data["size"]) ? $data["size"] : '';
            $strUtils = new Strings();
            $path     = $strUtils->sanitizeDirectorySeparator($dataPath);
            $wpRoot   = $strUtils->sanitizeDirectorySeparator(ABSPATH);
            $relPath  = str_replace($wpRoot, '', $path);
            $dirPath  = '/' . $relPath;

            // Select all wp core folders and their sub dirs.
            // Unselect all other folders (default setting)
            $isDisabled = ($name !== 'wp-admin' &&
                    $name !== 'wp-includes' &&
                    $name !== 'wp-content' &&
                    $name !== 'sites') &&
                    strpos(strrev($path), strrev($wpRoot . "wp-admin")) === false &&
                    strpos(strrev($path), strrev($wpRoot . "wp-includes")) === false &&
                    strpos(strrev($path), strrev($wpRoot . "wp-content")) === false;

            // make only wp-includes and wp-admin dirs not navigateable
            $isNavigateable = true;
            if ($strUtils->startsWith($path, $wpRoot . "wp-admin") !== false || $strUtils->startsWith($path, $wpRoot . "wp-includes") !== false) {
                $isNavigateable = false;
            }

            $isNavigateable = $isNavigateable ? 'true' : 'false';

            // class to differentiate between wp core and non core folders
            $class = !$isDisabled ? self::WP_CORE_DIR : self::WP_NON_CORE_DIR;

            $output .= "<div class='wpstg-dir'>";
            $output .= "<input type='checkbox' class='wpstg-check-dir " . $class . "'";

            $shouldBeChecked = $parentChecked !== null ? $parentChecked : !$isDisabled;
            if (!$forceDefault && $this->isUpdateOrResetJob() && (!$this->isPathInDirectories($dirPath, $excludedDirectories))) {
                $shouldBeChecked = true;
            } elseif (!$forceDefault && $this->isUpdateOrResetJob()) {
                $shouldBeChecked = false;
            }

            if (!$forceDefault && $this->isUpdateOrResetJob() && $class === self::WP_NON_CORE_DIR && !$this->isPathInDirectories($relPath, $extraDirectories)) {
                $shouldBeChecked = false;
            }

            if ($shouldBeChecked && ($parentChecked !== false)) {
                $output .= " checked";
            }

            $output .= " name='selectedDirectories[]' value='{$relPath}' data-scanned='false' data-navigateable='{$isNavigateable}'>";

            $output .= "<a href='#' class='wpstg-expand-dirs ";
            if ($isDisabled) {
                $output .= " disabled";
            }

            $output .= "'>{$name}";
            $output .= "</a>";
            $output .= ($this->gifLoaderPath !== '' && $isNavigateable === 'true') ? "<img src='{$this->gifLoaderPath}' class='wpstg-is-dir-loading' alt='loading' />" : "";
            $output .= "<span class='wpstg-size-info'>{$this->formatSize( $dataSize )}</span>";
            $output .= isset($this->settings->debugMode) ? "<span class='wpstg-size-info'> {$dataPath}</span>" : "";
            $output .= "</div>";
        }

        return $output;
    }

    /**
     * Checks if there is enough free disk space to create staging site according to selected directories
     * Returns null when can't run disk_free_space function one way or another
     * @param string    $excludedDirectories
     * @param string    $extraDirectories
     *
     * @return bool|null
     */
    public function hasFreeDiskSpace($excludedDirectories, $extraDirectories)
    {
        if (!function_exists("disk_free_space")) {
            return null;
        }

        $dirUtils = new WpDefaultDirectories();
        $selectedDirectories = $dirUtils->getWpCoreDirectories();
        $excludedDirectories = $dirUtils->getExcludedDirectories($excludedDirectories);

        $size = 0;
        // Scan WP Root path for size (only files)
        $size += $this->getDirectorySizeExcludingSubdirs(ABSPATH);
        // Scan selected directories for size (wp-core)
        foreach ($selectedDirectories as $directory) {
            if ($this->isPathInDirectories($directory, $excludedDirectories)) {
                continue;
            }

            $size += $this->getDirectorySizeInclSubdirs($directory, $excludedDirectories);
        }

        if (!empty($extraDirectories) && $extraDirectories !== '') {
            $extraDirectories = wpstg_urldecode(explode(ScanConst::DIRECTORIES_SEPARATOR, $extraDirectories));
            foreach ($extraDirectories as $directory) {
                $size += $this->getDirectorySizeInclSubdirs(ABSPATH . $directory, $excludedDirectories);
            }
        }

        $data = [
            'usedspace' => $this->formatSize($size)
        ];

        echo json_encode($data);
        die();
    }

    /**
     * Get Database Tables
     */
    protected function getTables()
    {
        $db = WPStaging::getInstance()->get("wpdb");

        $sql = "SHOW TABLE STATUS";

        $tables = $db->get_results($sql);

        $currentTables = [];

        // Reset excluded Tables than loop through all tables
        $this->options->excludedTables = [];
        foreach ($tables as $table) {
            // Create array of unchecked tables
            // On the main website of a multisite installation, do not select network site tables beginning with wp_1_, wp_2_ etc.
            // (On network sites, the correct tables are selected anyway)
            if (
                ( ! empty($db->prefix) && strpos($table->Name, $db->prefix) !== 0)
                || (is_multisite() && is_main_site() && preg_match('/^' . $db->prefix . '\d+_/', $table->Name))
            ) {
                $this->options->excludedTables[] = $table->Name;
            }

            if ($table->Comment !== "VIEW" && !LegacyDatabaseInfo::isBackupTable($table->Name)) {
                $currentTables[] = [
                    "name" => $table->Name,
                    "size" => ($table->Data_length + $table->Index_length)
                ];
            }
        }

        $this->options->tables = json_decode(json_encode($currentTables));
    }

    /**
     * Get directories and main meta data about given directory path
     * @param string $dirPath - Optional - Default ABSPATH
     */
    protected function getDirectories($dirPath = ABSPATH)
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $directories = new DirectoryIterator($dirPath);

        foreach ($directories as $directory) {
            // Not a valid directory
            if (($path = $this->getPath($directory)) === false) {
                continue;
            }

            if ($directory->isDot()) {
                continue;
            }

            $fullPath = WPStaging::getWPpath() . $path;
            $size     = $this->getDirectorySize($fullPath);

            $this->directories[$directory->getFilename()]['metaData'] = [
                "size" => $size,
                "path" => $fullPath,
            ];
        }
    }

    /**
     * Get Path from $directory
     * @param string
     * @return bool|string
     */
    protected function getPath($directory)
    {
        /*
         * Do not follow root path like src/web/..
         * This must be done before \SplFileInfo->isDir() is used!
         * Prevents open base dir restriction fatal errors
         */
        if (strpos($directory->getRealPath(), WPStaging::getWPpath()) !== 0) {
            return false;
        }

        $path = str_replace(WPStaging::getWPpath(), null, $directory->getRealPath());
        // Using strpos() for symbolic links as they could create nasty stuff in nix stuff for directory structures
        if (!$directory->isDir() || strlen($path) < 1) {
            return false;
        }

        return $path;
    }

    /**
     * Organizes $this->directories
     * @param string $path
     */
    protected function handleDirectory($path)
    {
        $directoryArray = explode(DIRECTORY_SEPARATOR, $path);
        $total          = is_array($directoryArray) || $directoryArray instanceof Countable ? count($directoryArray) : 0;

        if ($total < 1) {
            return;
        }

        $total        = $total - 1;
        $currentArray = &$this->directories;

        for ($i = 0; $i <= $total; $i++) {
            if (!isset($currentArray[$directoryArray[$i]])) {
                $currentArray[$directoryArray[$i]] = [];
            }

            $currentArray = &$currentArray[$directoryArray[$i]];

            // Attach meta data to the end
            if ($i < $total) {
                continue;
            }

            $fullPath = WPStaging::getWPpath() . $path;
            $size     = $this->getDirectorySize($fullPath);

            $currentArray["metaData"] = [
                "size" => $size,
                "path" => WPStaging::getWPpath() . $path,
            ];
        }
    }

    /**
     * Gets size of given directory
     * @param string $path
     * @return int|null
     */
    protected function getDirectorySize($path)
    {
        if (!isset($this->settings->checkDirectorySize) || $this->settings->checkDirectorySize !== '1') {
            return null;
        }

        return $this->objDirectories->size($path);
    }

    /**
     * Get total size of a directory including all its subdirectories
     * @param string $dir
     * @param array $excludedDirectories
     * @return int
     */
    protected function getDirectorySizeInclSubdirs($dir, $excludedDirectories)
    {
        $size = 0;
        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            if (is_file($each)) {
                $size += filesize($each);
                continue;
            }

            if ($this->isPathInDirectories($each, $excludedDirectories)) {
                continue;
            }

            $size += $this->getDirectorySizeInclSubdirs($each, $excludedDirectories);
        }

        return $size;
    }

    /**
     * Get total size of a directory excluding all its subdirectories
     * @param string $dir
     * @return int
     */
    protected function getDirectorySizeExcludingSubdirs($dir)
    {
        $size = 0;
        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : 0;
        }

        return $size;
    }

    /**
     * Is the path present is given list of directories
     * @param string $path
     * @param array $directories
     *
     * @return boolean
     */
    protected function isPathInDirectories($path, $directories)
    {
        // Check whether directory is itself is a part of excluded directories
        if (in_array($path, $directories)) {
            return true;
        }

        // Check whether directory a child of any excluded directories
        $strUtils = new Strings();
        foreach ($directories as $directory) {
            if ($strUtils->startsWith($path, $directory . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the current main job UPDATE or RESET
     *
     * @return boolean
     */
    protected function isUpdateOrResetJob()
    {
        return isset($this->options->mainJob) && ($this->options->mainJob === 'updating' || $this->options->mainJob === 'resetting');
    }
}
