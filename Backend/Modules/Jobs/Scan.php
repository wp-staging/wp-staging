<?php

namespace WPStaging\Backend\Modules\Jobs;

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
use WPStaging\Staging\Sites;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Utils\WpDefaultDirectories;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Filesystem\PathIdentifier;

/**
 * Class Scan
 * @package WPStaging\Backend\Modules\Jobs
 */
class Scan extends Job
{
    /**
     * CSS class name to use for WordPress core directories like wp-content, wp-admin, wp-includes
     * This doesn't contain class selector prefix
     *
     * @var string
     */
    const WP_CORE_DIR = "wpstg-wp-core-dir";

    /**
     * CSS class name to use for WordPress non core directories
     * This doesn't contain class selector prefix
     *
     * @var string
     */
    const WP_NON_CORE_DIR = "wpstg-wp-non-core-dir";

    /** @var array */
    private $directories = [];

    /** @var DirectoriesUtil */
    private $objDirectories;

    /** @var string|null */
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
    protected $dirAdapter;

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

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $pathIdentifier;

    /**
     * @var TemplateEngine
     */
    private $templateEngine;

    /** @var PathIdentifier */
    private $pathAdapter;

    /** @var PathChecker */
    private $pathChecker;

    /** @var string */
    protected $absPath = ABSPATH;

    /** @var string */
    protected $wpContentPath = WP_CONTENT_DIR;

    /** @var bool */
    private $isUploadsSymlinked;

    /**
     * Job constructor.
     * @throws Exception
     */
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
        $this->pathAdapter    = WPStaging::make(PathIdentifier::class);
        $this->pathChecker    = WPStaging::make(PathChecker::class);
        $this->dirAdapter     = WPStaging::make(Directory::class);
        $this->diskWriteCheck = WPStaging::make(DiskWriteCheck::class);
        $this->sanitize       = WPStaging::make(Sanitize::class);
        $this->templateEngine = WPStaging::make(TemplateEngine::class);
        parent::__construct();
    }

    /**
     * @param string $gifLoaderPath
     */
    public function setGifLoaderPath(string $gifLoaderPath)
    {
        $this->gifLoaderPath = $gifLoaderPath;
    }

    /**
     * @param string $infoIconPath
     */
    public function setInfoIcon(string $infoIconPath)
    {
        $this->infoIconPath = $infoIconPath;
    }

    /**
     * Return the path of info icon
     *
     * @return string
     */
    public function getInfoIcon(): string
    {
        return $this->infoIconPath;
    }

    /**
     * @param string $directoryToScanOnly
     */
    public function setDirectoryToScanOnly(string $directoryToScanOnly)
    {
        $this->directoryToScanOnly = $directoryToScanOnly;
    }

    /**
     * @param string $basePath
     * @todo add typed property `string` and ensure this value is never null
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim(wp_normalize_path($basePath), '/');
    }

    /** @return string */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /** @param string $pathIdentifier */
    public function setPathIdentifier(string $pathIdentifier)
    {
        $this->pathIdentifier = $pathIdentifier;
    }

    /** @return string */
    public function getPathIdentifier(): string
    {
        return $this->pathIdentifier;
    }

    /**
     * Upon class initialization
     */
    public function initialize()
    {
        $this->objDirectories = new DirectoriesUtil();

        $this->options->existingClones = get_option(Sites::STAGING_SITES_OPTION, []);
        $this->options->existingClones = is_array($this->options->existingClones) ? $this->options->existingClones : [];

        $this->directories = [];
        if (!empty($this->directoryToScanOnly)) {
            return;
        }

        $this->getTables();

        $this->setBasePath($this->absPath);
        $this->setPathIdentifier(PathIdentifier::IDENTIFIER_ABSPATH);
        $this->getDirectories($this->absPath);

        // If wp-content is outside ABSPATH, then scan it too
        if ($this->isWpContentOutsideAbspath()) {
            $this->setBasePath($this->wpContentPath);
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_WP_CONTENT);
            $this->getDirectories(dirname($this->wpContentPath));
        }

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
        $this->options->root         = str_replace(["\\", '/'], DIRECTORY_SEPARATOR, ABSPATH);
        $this->options->current      = null;
        $this->options->currentClone = $this->getCurrentClone();

        if ($this->options->currentClone !== null) {
            // Make sure no warning is shown when updating/resetting an old clone having no exclude rules options
            $this->options->currentClone['excludeSizeRules']   = $this->options->currentClone['excludeSizeRules'] ?? [];
            $this->options->currentClone['excludeGlobRules']   = $this->options->currentClone['excludeGlobRules'] ?? [];
            // Make sure no warning is shown when updating/resetting an old clone having no admin account data
            $this->options->currentClone['useNewAdminAccount'] = $this->options->currentClone['useNewAdminAccount'] ?? false;
            $this->options->currentClone['adminEmail']         = $this->options->currentClone['adminEmail'] ?? '';
            $this->options->currentClone['adminPassword']      = $this->options->currentClone['adminPassword'] ?? '';
            // Make sure no warning is shown when updating/resetting an old clone without databaseSsl, uploadsSymlinked, emailsAllowed and networkClone options
            $this->options->currentClone['emailsAllowed']         = $this->options->currentClone['emailsAllowed'] ?? true;
            $this->options->currentClone['databaseSsl']           = $this->options->currentClone['databaseSsl'] ?? false;
            $this->options->currentClone['uploadsSymlinked']      = $this->options->currentClone['uploadsSymlinked'] ?? false;
            $this->options->currentClone['networkClone']          = $this->options->currentClone['networkClone'] ?? false;
            $this->options->currentClone['wooSchedulerDisabled']  = empty($this->options->currentClone['wooSchedulerDisabled']) ? false : true;
            $this->options->currentClone['emailsReminderAllowed'] = empty($this->options->currentClone['emailsReminderAllowed']) ? false : true;
            $this->options->currentClone['isAutoUpdatePlugins']   = empty($this->options->currentClone['isAutoUpdatePlugins']) ? false : true;
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
        $this->options->mainJob = Job::STAGING;
        $job                    = '';
        if (isset($_POST["job"])) {
            $job = $this->sanitize->sanitizeString($_POST['job']);
        }

        if ($this->options->current !== null && $job === 'resetting') {
            $this->options->mainJob = Job::RESET;
        } elseif ($this->options->current !== null) {
            $this->options->mainJob = Job::UPDATE;
        }

        // Delete previous cached files
        $this->cloneOptionCache->delete();
        $this->filesIndexCache->delete();

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
     * @param bool|null $parentChecked  Is parent folder selected
     * @param bool $forceDefault        Default false. Set it to true,
     *                                  when default button on ui is clicked,
     *                                  to ignore previous selected option for UPDATE and RESET process.
     * @param null|array                $directories to list
     *
     * @return string
     */
    public function directoryListing($parentChecked = null, $forceDefault = false, $directories = null): string
    {
        if ($directories === null) {
            $directories = $this->directories;
        }

        uksort($directories, 'strcasecmp');

        $excludedDirectories = [];
        $extraDirectories    = [];

        if ($this->isUpdateOrResetJob()) {
            $currentClone        = json_decode(json_encode($this->options->currentClone));
            $extraDirectories    = isset($currentClone->extraDirectories) ? $currentClone->extraDirectories : [];
            $excludedDirectories = isset($currentClone->excludedDirectories) ? array_map(function ($directory) {
                // Exception is thrown when directory doesn't have identifier, so we will return directory as it is
                try {
                    return $this->pathAdapter->transformIdentifiableToPath($directory);
                } catch (UnexpectedValueException $ex) {
                    return $directory;
                }
            }, $currentClone->excludedDirectories) : [];
        }

        $output = '';
        foreach ($directories as $dirName => $directory) {
            // Not a directory, possibly a symlink, therefore we will skip it
            if (!is_array($directory) || basename($dirName) === "\\") {
                continue;
            }

            // Need to preserve keys so no array_shift()
            $data = reset($directory);
            unset($directory[key($directory)]);

            $output .= $this->getDirectoryHtml($data['dirName'], $data, $excludedDirectories, $extraDirectories, $parentChecked, $forceDefault);
        }

        return $output;
    }

    /**
     * Checks if there is enough free disk space to create staging site according to selected directories
     * Returns null when can't run disk_free_space function one way or another
     * @param string $excludedDirectories
     * @param string $extraDirectories
     *
     * @return bool|null
     */
    public function hasFreeDiskSpace(string $excludedDirectories, string $extraDirectories)
    {
        $dirUtils            = new WpDefaultDirectories();
        $selectedDirectories = $dirUtils->getWpCoreDirectories();
        $excludedDirectories = $dirUtils->getExcludedDirectories($excludedDirectories);

        if ($this->isUploadsSymlinked) {
            $uploadDirectory = rtrim(str_replace($this->absPath, PathIdentifier::IDENTIFIER_ABSPATH, $this->dirAdapter->getMainSiteUploadsDirectory()), '/');
            $excludedDirectories[] = $uploadDirectory;
        }

        $size = 0;
        // Scan WP Root path for size (only files)
        $size += $this->getDirectorySizeExcludingSubdirs($this->absPath);
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
                $size += $this->getDirectorySizeInclSubdirs($this->absPath . $directory, $excludedDirectories);
            }
        }

        $errorMessage = null;
        try {
            $this->diskWriteCheck->checkPathCanStoreEnoughBytes($this->absPath, $size);
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
     * @param bool $shouldReturn - Optional - Default false
     *
     * @return void|array            Depend upon value of $shouldReturn
     */
    public function getDirectories(string $dirPath = ABSPATH, bool $shouldReturn = false)
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
            if ($directory->isDot() || $directory->isFile()) {
                continue;
            }

            $fullPath = $this->resolveDirectoryPath($directory);
            if (empty($fullPath) || !is_dir($fullPath)) {
                continue;
            }

            $size = $this->getDirectorySize($fullPath);
            // If filename is int, then it is treated as a numeric index in key and start with 0
            $result[$directory->getFilename()]['metaData'] = [
                'dirName'  => $directory->getFilename(),
                "size"     => $size,
                "path"     => $fullPath,
                "basePath" => $this->getBasePath(),
                "prefix"   => $this->getPathIdentifier(),
                "isLink"   => is_link($directory->getPathname())
            ];
        }

        if ($shouldReturn) {
            return $result;
        }

        $this->directories = array_merge($this->directories, $result);
    }

    /**
     * Get Path from $directory
     * @param DirectoryIterator $directory
     * @return bool|string
     */
    protected function getPath($directory)
    {
        $basePath = $this->getBasePath();
        $realPath = WPStaging::make('WPSTG_ALLOW_VFS') === true && strpos($directory->getPathname(), 'vfs://') === 0 ? $directory->getPathname() : $directory->getRealPath();
        $realPath = wp_normalize_path($realPath);

        /*
         * Do not follow root path like src/web/..
         * This must be done before \SplFileInfo->isDir() is used!
         * Prevents open base dir restriction fatal errors
         */
        if (strpos($realPath, $basePath) !== 0) {
            return false;
        }

        $path = str_replace($basePath, '', $realPath);
        // Using strpos() for symbolic links as they could create nasty stuff in nix stuff for directory structures
        if (!$directory->isDir() || (strlen($path) < 1 && $this->pathIdentifier !== PathIdentifier::IDENTIFIER_WP_CONTENT)) {
            return false;
        }

        return $path;
    }

    /**
     * @param string $dirName
     * @param array  $dirInfo contains information about the directory
     * @param array  $excludedDirectories
     * @param array  $extraDirectories
     * @param bool   $parentChecked
     * @param bool   $forceDefault
     * @return string
     */
    protected function getDirectoryHtml($dirName, $dirInfo, $excludedDirectories, $extraDirectories, $parentChecked = false, $forceDefault = false)
    {
        $data     = $dirInfo;
        $dataPath = isset($data["path"]) ? $data["path"] : '';
        $dataSize = isset($data["size"]) ? $data["size"] : '';
        $path     = wp_normalize_path($dataPath);
        $basePath = isset($data["basePath"]) ? $data["basePath"] : wp_normalize_path($this->absPath);
        $prefix   = isset($data["prefix"]) ? $data["prefix"] : PathIdentifier::IDENTIFIER_ABSPATH;
        $relPath  = str_replace($basePath, '', $path);
        $relPath  = ltrim($relPath, '/');

        // Check if directory name or directory path is not WP core folder
        $isNotWPCoreDir = $this->isNonWpCoreDirectory($dirName, $path);

        $class   = $isNotWPCoreDir ? self::WP_NON_CORE_DIR : self::WP_CORE_DIR;
        $dirType = 'other';

        if ($this->strUtils->startsWith($path, $this->dirAdapter->getPluginsDirectory()) !== false) {
            $pluginPath = $this->strUtils->strReplaceFirst($this->dirAdapter->getPluginsDirectory(), '', $path);
            $dirType    = strpos($pluginPath, '/') === false ? 'plugin' : 'other';
        } elseif ($this->strUtils->startsWith($path, $this->dirAdapter->getActiveThemeParentDirectory()) !== false) {
            $themePath = $this->strUtils->strReplaceFirst($this->dirAdapter->getActiveThemeParentDirectory(), '', $path);
            $dirType   = strpos($themePath, '/') === false ? 'theme' : 'other';
        }

        $isScanned = 'false';
        if (
            trailingslashit($path) === $this->dirAdapter->getWpContentDirectory()
            || trailingslashit($path) === $this->dirAdapter->getPluginsDirectory()
            || trailingslashit($path) === $this->dirAdapter->getActiveThemeParentDirectory()
        ) {
            $isScanned = 'true';
        }

        // Make wp-includes and wp-admin directory items not expandable
        $isNavigatable = 'true';
        if ($this->strUtils->startsWith($path, $basePath . "/wp-admin") !== false || $this->strUtils->startsWith($path, $basePath . "/wp-includes") !== false) {
            $isNavigatable = 'false';
        }

        // Decide if item checkbox is active or not
        $shouldBeChecked = $parentChecked !== null ? $parentChecked : !$isNotWPCoreDir;
        if (!$forceDefault && $this->isUpdateOrResetJob() && (!$this->isPathInDirectories($path, $excludedDirectories, $basePath))) {
            $shouldBeChecked = true;
        } elseif (!$forceDefault && $this->isUpdateOrResetJob()) {
            $shouldBeChecked = false;
        }

        if (!$forceDefault && $this->isUpdateOrResetJob() && $class === self::WP_NON_CORE_DIR && !$this->isPathInDirectories($path, $extraDirectories)) {
            $shouldBeChecked = false;
        }

        $isDisabledDir = $dirName === 'wp-admin' || $dirName === 'wp-includes';

        $isDisabled = false;
        if (strpos($dataPath, 'wp-content/' . Directory::STAGING_SITE_DIRECTORY) !== false) {
            $isDisabled      = true;
            $shouldBeChecked = false;
        }

        $isLink = false;
        if (strpos(trailingslashit($basePath) . $dirName, 'wp-content') !== false && is_link(trailingslashit($basePath) . $dirName)) {
            $isDisabled      = true;
            $isNavigatable   = 'false';
            $shouldBeChecked = true;
            $isLink          = true;
            $relPath         = 'wp-content';
        }

        return $this->templateEngine->render('clone/ajax/directory-navigation.php', [
            'scan'              => $this,
            'prefix'            => $prefix,
            'relPath'           => $relPath,
            'class'             => $class,
            'dirType'           => $dirType,
            'isScanned'         => $isScanned,
            'isNavigatable'     => $isNavigatable,
            'shouldBeChecked'   => $shouldBeChecked,
            'parentChecked'     => $parentChecked,
            'directoryDisabled' => $isNotWPCoreDir || $isDisabledDir,
            'isDisabled'        => $isDisabled,
            'dirName'           => $dirName,
            'gifLoaderPath'     => $this->gifLoaderPath,
            'formattedSize'     => $this->utilsMath->formatSize($dataSize),
            'isDebugMode'       => $this->utilsMath->formatSize($dataSize),
            'dataPath'          => $dataPath,
            'basePath'          => $basePath,
            'forceDefault'      => $forceDefault,
            'dirPath'           => $path,
            'isLink'            => $isLink
        ]);
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
     * @param string  $path
     * @param array   $directories List of directories relative to ABSPATH with leading slash
     * @param ?string $basePath
     *
     * @return bool
     */
    protected function isPathInDirectories(string $path, array $directories, $basePath = null): bool
    {
        return $this->pathChecker->isPathInPathsList($path, $directories, true, $basePath);
    }

    /**
     * Get clone from $_POST['clone'] and set it as current clone
     * If clone is not found, then set current clone to null
     *
     * @return array|null
     */
    protected function getCurrentClone()
    {
        $cloneID = isset($_POST["clone"]) ? $this->sanitize->sanitizeString($_POST['clone']) : '';

        if (array_key_exists($cloneID, $this->options->existingClones)) {
            $this->options->current = $cloneID;
            return $this->options->existingClones[$this->options->current];
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function isWpContentOutsideAbspath()
    {
        /** @var SiteInfo $siteInfo */
        $siteInfo = WPStaging::make(SiteInfo::class);
        return $siteInfo->isWpContentOutsideAbspath();
    }

    /**
     * Check if directory name or directory path is not WP core folder
     *
     * @param string $dirname
     * @param string $path
     * @return bool
     */
    protected function isNonWpCoreDirectory($dirname, $path)
    {
        $coreDirectories = [
            'wp-admin',
            'wp-content',
            'wp-includes'
        ];

        if (in_array($dirname, $coreDirectories)) {
            return false;
        }

        $wpDirectories = [
            $this->dirAdapter->getWpContentDirectory(),
            $this->dirAdapter->getPluginsDirectory(),
            $this->dirAdapter->getActiveThemeParentDirectory(),
            $this->dirAdapter->getUploadsDirectory(),
            $this->dirAdapter->getMuPluginsDirectory()
        ];

        foreach ($wpDirectories as $wpDirectory) {
            if (strpos(trailingslashit($path), $wpDirectory) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve the full path for a directory, handling symlinks if present
     * @param DirectoryIterator $directory
     * @return string
     */
    private function resolveDirectoryPath(DirectoryIterator $directory): string
    {
        if (!is_link($directory->getPathname())) {
            $path = $this->getPath($directory);
            if ($path === false) {
                return '';
            }

            return trailingslashit($this->getBasePath()) . ltrim($path, '/');
        }

        $targetPath = readlink($directory->getPathname());
        if ($targetPath === false) {
            return '';
        }

        if (!path_is_absolute($targetPath)) {
            $targetPath = dirname($directory->getPathname()) . '/' . $targetPath;
        }

        return wp_normalize_path(realpath($targetPath));
    }

    /**
     * @param bool $isUploadsSymlinked
     * @return void
     */
    public function setIsUploadsSymlinked(bool $isUploadsSymlinked)
    {
        $this->isUploadsSymlinked = $isUploadsSymlinked;
    }
}
