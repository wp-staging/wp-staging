<?php

namespace WPStaging\Backend\Modules\Jobs;

use DirectoryIterator;
use Exception;
use UnexpectedValueException;
use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Staging\Sites;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Filesystem\PathIdentifier;




class Scan extends Job
{






    const WP_CORE_DIR = "wpstg-wp-core-dir";







    const WP_NON_CORE_DIR = "wpstg-wp-non-core-dir";

 
    private $directories = [];

 
    private $directoryToScanOnly;




    private $gifLoaderPath;




    private $strUtils;




    protected $dirAdapter;




    private $sanitize;




    private $infoIconPath;




    private $basePath;




    private $pathIdentifier;




    private $templateEngine;

 
    private $pathAdapter;

 
    private $pathChecker;

 
    protected $absPath = ABSPATH;

 
    protected $wpContentPath = WP_CONTENT_DIR;





    public function __construct($directoryToScanOnly = null)
    {
 
 
        $this->directoryToScanOnly = null;
        if ($directoryToScanOnly !== null) {
            $this->directoryToScanOnly = $directoryToScanOnly;
        }

 
        $this->strUtils       = new Strings();
        $this->pathAdapter    = WPStaging::make(PathIdentifier::class);
        $this->pathChecker    = WPStaging::make(PathChecker::class);
        $this->dirAdapter     = WPStaging::make(Directory::class);
        $this->sanitize       = WPStaging::make(Sanitize::class);
        $this->templateEngine = WPStaging::make(TemplateEngine::class);
        parent::__construct();
    }




    public function setGifLoaderPath(string $gifLoaderPath)
    {
        $this->gifLoaderPath = $gifLoaderPath;
    }




    public function setInfoIcon(string $infoIconPath)
    {
        $this->infoIconPath = $infoIconPath;
    }






    public function getInfoIcon(): string
    {
        return $this->infoIconPath;
    }




    public function setDirectoryToScanOnly(string $directoryToScanOnly)
    {
        $this->directoryToScanOnly = $directoryToScanOnly;
    }





    public function setBasePath($basePath)
    {
        $this->basePath = rtrim(wp_normalize_path($basePath), '/');
    }

 
    public function getBasePath(): string
    {
        return $this->basePath;
    }

 
    public function setPathIdentifier(string $pathIdentifier)
    {
        $this->pathIdentifier = $pathIdentifier;
    }

 
    public function getPathIdentifier(): string
    {
        return $this->pathIdentifier;
    }




    public function initialize()
    {
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

 
        if ($this->isWpContentOutsideAbspath()) {
            $this->setBasePath($this->wpContentPath);
            $this->setPathIdentifier(PathIdentifier::IDENTIFIER_WP_CONTENT);
            $this->getDirectories(dirname($this->wpContentPath));
        }

        $this->installOptimizer();
    }






    public function start()
    {
 
        $this->options->root         = str_replace(["\\", '/'], DIRECTORY_SEPARATOR, ABSPATH);
        $this->options->current      = null;
        $this->options->currentClone = $this->getCurrentClone();

        if ($this->options->currentClone !== null) {
 
            $this->options->currentClone['excludeSizeRules']   = $this->options->currentClone['excludeSizeRules'] ?? [];
            $this->options->currentClone['excludeGlobRules']   = $this->options->currentClone['excludeGlobRules'] ?? [];
 
            $this->options->currentClone['useNewAdminAccount'] = $this->options->currentClone['useNewAdminAccount'] ?? false;
            $this->options->currentClone['adminEmail']         = $this->options->currentClone['adminEmail'] ?? '';
            $this->options->currentClone['adminPassword']      = $this->options->currentClone['adminPassword'] ?? '';
 
            $this->options->currentClone['isEmailsAllowed']         = $this->options->currentClone['isEmailsAllowed'] ?? true;
            $this->options->currentClone['databaseSsl']             = $this->options->currentClone['databaseSsl'] ?? false;
            $this->options->currentClone['uploadsSymlinked']        = $this->options->currentClone['uploadsSymlinked'] ?? false;
            $this->options->currentClone['networkClone']            = $this->options->currentClone['networkClone'] ?? false;
            $this->options->currentClone['isWooSchedulerEnabled']   = empty($this->options->currentClone['isWooSchedulerEnabled']) ? false : true;
            $this->options->currentClone['isEmailsReminderEnabled'] = empty($this->options->currentClone['isEmailsReminderEnabled']) ? false : true;
            $this->options->currentClone['isAutoUpdatePlugins']     = empty($this->options->currentClone['isAutoUpdatePlugins']) ? false : true;
        }

 
        $this->options->clonedTables = [];

 
        $this->options->totalFiles    = 0;
        $this->options->totalFileSize = 0;
        $this->options->copiedFiles   = 0;


 
        $this->options->includedDirectories      = [];
        $this->options->includedExtraDirectories = [];
        $this->options->excludedDirectories      = [];
        $this->options->extraDirectories         = [];
        $this->options->scannedDirectories       = [];

 
        $this->options->currentJob  = "PreserveDataFirstStep";
        $this->options->currentStep = 0;
        $this->options->totalSteps  = 0;

 
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

 
        $this->cloneOptionCache->delete();
        $this->filesIndexCache->delete();

        $this->saveOptions();

        return $this;
    }




    private function installOptimizer()
    {
        $optimizer = new Optimizer();
        $optimizer->installOptimizer();
    }










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
 
                try {
                    return $this->pathAdapter->transformIdentifiableToPath($directory);
                } catch (UnexpectedValueException $ex) {
                    return $directory;
                }
            }, $currentClone->excludedDirectories) : [];
        }

        $output = '';
        foreach ($directories as $dirName => $directory) {
 
            if (!is_array($directory) || basename($dirName) === "\\") {
                continue;
            }

 
            $data = reset($directory);
            unset($directory[key($directory)]);

            $output .= $this->getDirectoryHtml($data['dirName'], $data, $excludedDirectories, $extraDirectories, $parentChecked, $forceDefault);
        }

        return $output;
    }




    protected function getTables()
    {
        $db       = WPStaging::getInstance()->get("wpdb");
        $dbPrefix = WPStaging::getTablePrefix();

        $sql = "SHOW TABLE STATUS";

        $tables = $db->get_results($sql);

        $currentTables = [];

        $currentClone = $this->getCurrentClone();
        $networkClone = is_multisite() && is_main_site() && is_array($currentClone) && (array_key_exists('networkClone', $currentClone) ? $this->sanitize->sanitizeBool($currentClone['networkClone']) : false);

 
        $this->options->excludedTables = [];
        foreach ($tables as $table) {
 
 
 
            if (
                ( ! empty($dbPrefix) && strpos($table->Name, $dbPrefix) !== 0)
                || (is_multisite() && is_main_site() && !$networkClone && preg_match('/^' . $dbPrefix . '\d+_/', $table->Name))
            ) {
                $this->options->excludedTables[] = $table->Name;
            }

            if ($table->Comment !== "VIEW") {
                $currentTables[] = [
                    "name" => $table->Name,
                    "size" => ($table->Data_length + $table->Index_length),
                ];
            }
        }

        $this->options->tables = json_decode(json_encode($currentTables));
    }








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

 
            $result[$directory->getFilename()]['metaData'] = [
                'dirName'  => $directory->getFilename(),
                "path"     => $fullPath,
                "basePath" => $this->getBasePath(),
                "prefix"   => $this->getPathIdentifier(),
                "isLink"   => is_link($directory->getPathname()),
            ];
        }

        if ($shouldReturn) {
            return $result;
        }

        $this->directories = array_merge($this->directories, $result);
    }






    protected function getPath($directory)
    {
        $basePath = $this->getBasePath();
        $realPath = WPStaging::make('WPSTG_ALLOW_VFS') === true && strpos($directory->getPathname(), 'vfs://') === 0 ? $directory->getPathname() : $directory->getRealPath();
        $realPath = wp_normalize_path($realPath);






        if (strpos($realPath, $basePath) !== 0) {
            return false;
        }

        $path = str_replace($basePath, '', $realPath);
 
        if (!$directory->isDir() || (strlen($path) < 1 && $this->pathIdentifier !== PathIdentifier::IDENTIFIER_WP_CONTENT)) {
            return false;
        }

        return $path;
    }










    protected function getDirectoryHtml($dirName, $dirInfo, $excludedDirectories, $extraDirectories, $parentChecked = false, $forceDefault = false)
    {
        $data     = $dirInfo;
        $dataPath = isset($data["path"]) ? $data["path"] : '';
        $path     = wp_normalize_path($dataPath);
        $basePath = isset($data["basePath"]) ? $data["basePath"] : wp_normalize_path($this->absPath);
        $prefix   = isset($data["prefix"]) ? $data["prefix"] : PathIdentifier::IDENTIFIER_ABSPATH;
        $relPath  = str_replace($basePath, '', $path);
        $relPath  = ltrim($relPath, '/');

 
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

 
        $isNavigatable = 'true';
        if ($this->strUtils->startsWith($path, $basePath . "/wp-admin") !== false || $this->strUtils->startsWith($path, $basePath . "/wp-includes") !== false) {
            $isNavigatable = 'false';
        }

 
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
            'isDebugMode'       => false,
            'dataPath'          => $dataPath,
            'basePath'          => $basePath,
            'forceDefault'      => $forceDefault,
            'dirPath'           => $path,
            'isLink'            => $isLink,
        ]);
    }









    protected function isPathInDirectories(string $path, array $directories, $basePath = null): bool
    {
        return $this->pathChecker->isPathInPathsList($path, $directories, true, $basePath);
    }







    protected function getCurrentClone()
    {
        $cloneID = isset($_POST["clone"]) ? $this->sanitize->sanitizeString($_POST['clone']) : '';

        if (array_key_exists($cloneID, $this->options->existingClones)) {
            $this->options->current = $cloneID;
            return $this->options->existingClones[$this->options->current];
        }

        return null;
    }




    protected function isWpContentOutsideAbspath()
    {
 
        $siteInfo = WPStaging::make(SiteInfo::class);
        return $siteInfo->isWpContentOutsideAbspath();
    }








    protected function isNonWpCoreDirectory($dirname, $path)
    {
        $coreDirectories = [
            'wp-admin',
            'wp-content',
            'wp-includes',
        ];

        if (in_array($dirname, $coreDirectories)) {
            return false;
        }

        $wpDirectories = [
            $this->dirAdapter->getWpContentDirectory(),
            $this->dirAdapter->getPluginsDirectory(),
            $this->dirAdapter->getActiveThemeParentDirectory(),
            $this->dirAdapter->getUploadsDirectory(),
            $this->dirAdapter->getMuPluginsDirectory(),
        ];

        foreach ($wpDirectories as $wpDirectory) {
            if (strpos(trailingslashit($path), $wpDirectory) !== false) {
                return false;
            }
        }

        return true;
    }






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
}
