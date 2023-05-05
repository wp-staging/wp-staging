<?php

namespace WPStaging\Backend\Modules\Jobs;

use Countable;
use DirectoryIterator;
use Exception;
use RuntimeException;
use UnexpectedValueException;
use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Core\Utils\Directories as DirectoriesUtil;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Backup\Exceptions\DiskNotWritableException;

use function WPStaging\functions\debug_log;

/**
 * Class Scan
 * @package WPStaging\Backend\Modules\Jobs
 *
 * @todo replace WPStaging::getWPpath() with ABSPATH - separate PR
 */
class Scan extends Job
{

    /**
     * CSS class name to use for WordPress core directories like wp-content, wp-admin, wp-includes
     * This doesn't contains class selector prefix
     *
     * @var string
     */
    const WP_CORE_DIR = "wpstg-wp-core-dir";

    /**
     * CSS class name to use for WordPress non core directories
     * This doesn't contains class selector prefix
     *
     * @var string
     */
    const WP_NON_CORE_DIR = "wpstg-wp-non-core-dir";

    /** @var array */
    private $directories = [];

    /** @var DirectoriesUtil */
    private $objDirectories;

    /** @var string */
    private $directoryToScanOnly;

    /**
     * @var string Path to gif loader for directory loading
     */
    private $gifLoaderPath;

    /**
     * @var Strings
     */
    private $strUtils;

    /**
     * @var Directory
     */
    private $dirAdapter;

    /**
     * @var DiskWriteCheck
     */
    private $diskWriteCheck;

    /**
     * @var Sanitize
     */
    private $sanitize;

    /**
     * @var string Path to the info icon
     */
    private $infoIconPath;

    public function __construct($directoryToScanOnly = null)
    {
        // Accept both the absolute path or relative path with respect to wp root
        // Santized the path to make comparing works for windows platform too.
        $this->directoryToScanOnly = null;
        if ($directoryToScanOnly !== null) {
            $this->directoryToScanOnly = $directoryToScanOnly;
        }

        // TODO: inject using DI when available
        $this->strUtils       = new Strings();
        $this->dirAdapter     = WPStaging::make(Directory::class);
        $this->diskWriteCheck = WPStaging::make(DiskWriteCheck::class);
        $this->sanitize       = WPStaging::make(Sanitize::class);
        parent::__construct();
    }

    /**
     * @param string $gifLoaderPath
     */
    public function setGifLoaderPath($gifLoaderPath)
    {
        $this->gifLoaderPath = $gifLoaderPath;
    }

    /**
     * @param string $infoIconPath
     */
    public function setInfoIcon($infoIconPath)
    {
        $this->infoIconPath = $infoIconPath;
    }

    /**
     * Return the path of info icon
     *
     * @return string
     */
    public function getInfoIcon()
    {
        return $this->infoIconPath;
    }

    /**
     * Upon class initialization
     */
    protected function initialize()
    {
        $this->objDirectories = new DirectoriesUtil();

        $this->options->existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        $this->options->existingClones = is_array($this->options->existingClones) ? $this->options->existingClones : [];

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
        $this->options->root         = str_replace(["\\", '/'], DIRECTORY_SEPARATOR, WPStaging::getWPpath());
        $this->options->current      = null;
        $this->options->currentClone = $this->getCurrentClone();

        if ($this->options->currentClone !== null) {
            // Make sure no warning is shown when updating/resetting an old clone having no exclude rules options
            $this->options->currentClone['excludeSizeRules'] = isset($this->options->currentClone['excludeSizeRules']) ? $this->options->currentClone['excludeSizeRules'] : [];
            $this->options->currentClone['excludeGlobRules'] = isset($this->options->currentClone['excludeGlobRules']) ? $this->options->currentClone['excludeGlobRules'] : [];
            // Make sure no warning is shown when updating/resetting an old clone having emails allowed option
            $this->options->currentClone['emailsAllowed'] = isset($this->options->currentClone['emailsAllowed']) ? $this->options->currentClone['emailsAllowed'] : true;
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
        $job                    = '';
        if (isset($_POST["job"])) {
            $job = $this->sanitize->sanitizeString($_POST['job']);
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
     * @param null|bool $parentChecked  Is parent folder selected
     * @param bool $forceDefault        Default false. Set it to true,
     *                                  when default button on ui is clicked,
     *                                  to ignore previous selected option for UPDATE and RESET process.
     * @param null|array                $directories to list
     *
     * @return string
     *
     * @todo create a template for ui
     */
    public function directoryListing($parentChecked = null, $forceDefault = false, $directories = null)
    {
        if ($directories === null) {
            $directories = $this->directories;
        }

        uksort($directories, 'strcasecmp');

        $excludedDirectories = [];
        $extraDirectories    = [];

        if ($this->isUpdateOrResetJob()) {
            $currentClone        = json_decode(json_encode($this->options->currentClone));
            $excludedDirectories = isset($currentClone->excludedDirectories) ? $currentClone->excludedDirectories : [];
            $extraDirectories    = isset($currentClone->extraDirectories) ? $currentClone->extraDirectories : [];
        }

        $output = '';
        foreach ($directories as $dirName => $directory) {
            // Not a directory, possibly a symlink, therefore we will skip it
            if (!is_array($directory)) {
                continue;
            }

            // Need to preserve keys so no array_shift()
            $data = reset($directory);
            unset($directory[key($directory)]);

            $dataPath = isset($data["path"]) ? $data["path"] : '';
            $dataSize = isset($data["size"]) ? $data["size"] : '';
            $path     = $this->strUtils->sanitizeDirectorySeparator($dataPath);
            $wpRoot   = $this->strUtils->sanitizeDirectorySeparator(ABSPATH);
            $relPath  = str_replace($wpRoot, '', $path);
            $dirPath  = '/' . $relPath;

            // Check if directory name or directory path is not WP core folder
            $isNotWPCoreDir = ($dirName !== 'wp-admin' &&
                    $dirName !== 'wp-includes' &&
                    $dirName !== 'wp-content') &&
                    strpos($path, $wpRoot . "wp-admin") === false &&
                    strpos($path, $wpRoot . "wp-includes") === false &&
                    strpos($path, $wpRoot . "wp-content") === false;

            // html class to differentiate between wp core and non core folders
            $class = $isNotWPCoreDir ? self::WP_NON_CORE_DIR : self::WP_CORE_DIR;

            // Make wp-includes and wp-admin directory items not expandable
            $isNavigateable = 'true';
            if ($this->strUtils->startsWith($path, $wpRoot . "wp-admin") !== false || $this->strUtils->startsWith($path, $wpRoot . "wp-includes") !== false) {
                $isNavigateable = 'false';
            }

            $contentType = 'other';
            if ($this->strUtils->startsWith($path, $wpRoot . "wp-content/plugins/") !== false) {
                $contentType = 'plugin';
            } elseif ($this->strUtils->startsWith($path, $wpRoot . "wp-content/themes/") !== false) {
                $contentType = 'theme';
            }

            $isScanned = 'false';
            if (
                $path === $wpRoot . 'wp-content'
                || $path === $wpRoot . 'wp-content/plugins'
                || $path === $wpRoot . 'wp-content/themes'
            ) {
                $isScanned = 'true';
            }

            $output .= "<div class='wpstg-dir'>";
            $output .= "<input type='checkbox' data-content-type='" . $contentType . "' class='wpstg-checkbox wpstg-checkbox--small wpstg-check-dir " . $class . "'";

            // Decide if item checkbox is active or not
            $shouldBeChecked = $parentChecked !== null ? $parentChecked : !$isNotWPCoreDir;
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

            $output .= " name='selectedDirectories[]' value='{$relPath}' data-scanned='{$isScanned}' data-navigateable='{$isNavigateable}'>";

            $output .= "<a href='#' class='wpstg-expand-dirs ";

            $isDisabledDir = $dirName === 'wp-admin' || $dirName === 'wp-includes';

            // Set menu item to 'disable'
            if ($isNotWPCoreDir || $isDisabledDir) {
                $output .= " disabled";
            }

            $output .= "'>{$dirName}";
            $output .= "</a>";
            $output .= ($this->gifLoaderPath !== '' && $isNavigateable === 'true') ? "<img src='{$this->gifLoaderPath}' class='wpstg-is-dir-loading' alt='loading' />" : "";
            $output .= "<span class='wpstg-size-info'>{$this->utilsMath->formatSize( $dataSize )}</span>";
            $output .= isset($this->settings->debugMode) ? "<span class='wpstg-size-info'> {$dataPath}</span>" : "";

            if ($isScanned === 'true') {
                $childDirectories = $this->getDirectories($path, $return = true);
                $output .= '<div class="wpstg-dir wpstg-subdir" style="display: none;">';
                $output .= $this->directoryListing($parentChecked, $forceDefault, $childDirectories);
                $output .= '</div>';
            }

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
        $dirUtils            = new WpDefaultDirectories();
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

        $errorMessage = null;
        try {
            $this->diskWriteCheck->checkPathCanStoreEnoughBytes(ABSPATH, $size);
        } catch (RuntimeException $ex) {
            $errorMessage = $ex->getMessage();
        } catch (DiskNotWritableException $ex) {
            $errorMessage = $ex->getMessage();
        }

        $data = [
            'requiredSpace' => $this->utilsMath->formatSize($size),
            'errorMessage'  => $errorMessage
        ];

        echo json_encode($data);
        die();
    }

    /**
     * Get Database Tables
     */
    protected function getTables()
    {
        $db       = WPStaging::getInstance()->get("wpdb");
        $dbPrefix = WPStaging::getTablePrefix();

        $sql = "SHOW TABLE STATUS";

        $tables = $db->get_results($sql);

        $currentTables = [];

        $currentClone = $this->getCurrentClone();
        $networkClone = is_multisite() && is_main_site() && is_array($currentClone) && (array_key_exists('networkClone', $currentClone) ? $this->sanitize->sanitizeBool($currentClone['networkClone']) : false);

        // Reset excluded Tables than loop through all tables
        $this->options->excludedTables = [];
        foreach ($tables as $table) {
            // Create array of unchecked tables
            // On the main website of a multisite installation, do not select network site tables beginning with wp_1_, wp_2_ etc.
            // (On network sites, the correct tables are selected anyway)
            if (
                ( ! empty($dbPrefix) && strpos($table->Name, $dbPrefix) !== 0)
                || (is_multisite() && is_main_site() && !$networkClone && preg_match('/^' . $dbPrefix . '\d+_/', $table->Name))
            ) {
                $this->options->excludedTables[] = $table->Name;
            }

            if ($table->Comment !== "VIEW") {
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
     * @param string $dirPath      - Optional - Default ABSPATH
     * @param bool   $shouldReturn - Optional - Default false
     *
     * @return void|array            Depend upon value of $shouldReturn
     */
    protected function getDirectories($dirPath = ABSPATH, $shouldReturn = false)
    {
        if (!is_dir($dirPath)) {
            return;
        }

        try {
            $directories = new DirectoryIterator($dirPath);
        } catch (UnexpectedValueException $ex) {
            $errorMessage = $ex->getMessage();
            if ($ex->getCode() === 5) {
                $errorMessage = esc_html__('Access Denied: No read permission to scan the root directory for cloning. Alternatively you can try the WP STAGING backup feature!', 'wp-staging');
            }

            echo json_encode([
                'success'     => false,
                'type'        => '',
                // TODO: Create a Swal Response Class and Js library to handle that response or, Implement own Swal alternative
                'swalOptions' => [
                    'title'             => esc_html__('Error!', 'wp-staging'),
                    'html'              => $errorMessage,
                    'cancelButtonText'  => esc_html__('Ok', 'wp-staging'),
                    'showCancelButton'  => true,
                    'showConfirmButton' => false,
                ],
            ]);

            exit();
        }

        $result = [];

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

            $result[$directory->getFilename()]['metaData'] = [
                "size" => $size,
                "path" => $fullPath,
            ];
        }

        if ($shouldReturn) {
            return $result;
        }

        $this->directories = $result;
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

        $path = str_replace(WPStaging::getWPpath(), '', $directory->getRealPath());
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
            return;
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
     * @param array $directories List of directories relative to ABSPATH with leading slash
     *
     * @return boolean
     */
    protected function isPathInDirectories($path, $directories)
    {
        return $this->dirAdapter->isPathInPathsList($path, $directories, true);
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

    protected function getCurrentClone()
    {
        $cloneID = isset($_POST["clone"]) ? $this->sanitize->sanitizeString($_POST['clone']) : '';

        if (array_key_exists($cloneID, $this->options->existingClones)) {
            $this->options->current = $cloneID;
            return $this->options->existingClones[$this->options->current];
        }

        return;
    }
}
