<?php

namespace WPStaging\Staging\Service;

use DirectoryIterator;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Core\WPStaging;
use WPStaging\Staging\Dto\DirectoryNodeDto;
use WPStaging\Staging\Sites;




class DirectoryScanner
{






    const WP_CORE_DIR = "wpstg-wp-core-dir";







    const WP_NON_CORE_DIR = "wpstg-wp-non-core-dir";




    protected $templateEngine;




    protected $directory;




    protected $strUtils;




    protected $pathChecker;




    protected $siteInfo;




    protected $stagingSetup;






    private $stagingSiteDirectories = null;




    protected $loaderIcon = '';




    protected $infoIcon = '';




    protected $isAllowVfsPath = false;




    protected $scanSubWpContentByDefault = false;




    protected $excludedDirectories = [];




    protected $extraDirectories = [];

 
    protected $absPath = ABSPATH;

 
    protected $wpContentPath = WP_CONTENT_DIR;

 
    protected $filesystem;

 
    protected $useDefaultSelection = false;




    protected $showFileDestination = true;

    public function __construct(TemplateEngine $templateEngine, Assets $assets, Directory $directory, Strings $strUtils, PathChecker $pathChecker, SiteInfo $siteInfo, Filesystem $filesystem)
    {
        $this->templateEngine = $templateEngine;
        $this->directory      = $directory;
        $this->strUtils       = $strUtils;
        $this->pathChecker    = $pathChecker;
        $this->siteInfo       = $siteInfo;
        $this->loaderIcon     = $assets->getAssetsUrl('img/spinner.gif');
        $this->filesystem     = $filesystem;

 
        $this->absPath       = $this->filesystem->normalizePath($this->absPath, true);
        $this->wpContentPath = $this->filesystem->normalizePath($this->wpContentPath);
    }





    public function setIsAllowVfsPath(bool $isAllowVfsPath)
    {
        $this->isAllowVfsPath = $isAllowVfsPath;
    }




    public function setStagingSetup(AbstractStagingSetup $stagingSetup)
    {
        $this->stagingSetup        = $stagingSetup;
        $this->excludedDirectories = $stagingSetup->getStagingSiteDto()->getExcludedDirectories();
        $this->extraDirectories    = $stagingSetup->getStagingSiteDto()->getExtraDirectories();
    }





    public function setShowFileDestination(bool $showFileDestination)
    {
        $this->showFileDestination = $showFileDestination;
    }

    public function isUpdateOrResetJob(): bool
    {
        return $this->stagingSetup->isUpdateOrResetJob();
    }

    public function renderFilesSelection()
    {
        $directories = $this->scanDirectory($this->absPath, $this->absPath, PathIdentifier::IDENTIFIER_ABSPATH);

 
        if ($this->isWpContentOutsideAbspath()) {
            $wpContentDirectories = $this->scanDirectory(dirname($this->wpContentPath), $this->wpContentPath, PathIdentifier::IDENTIFIER_WP_CONTENT);
            $directories          = array_merge($directories, $wpContentDirectories);
        }

 
        $this->useDefaultSelection = true;

        $result = $this->templateEngine->render('staging/_partials/files-selection.php', [
            'scanner'             => $this,
            'stagingSetup'        => $this->stagingSetup,
            'stagingSiteDto'      => $this->stagingSetup->getStagingSiteDto(),
            'directories'         => $directories,
            'excludeFilters'      => new ExcludeFilter(),
            'showFileDestination' => $this->showFileDestination,
        ]);

        echo $result; // phpcs:ignore
    }







    public function scanDirectory(string $dirToScan, string $basePath, string $identifier): array
    {
        if (!is_dir($dirToScan)) {
            throw new WPStagingException("The directory at path '{$dirToScan}' does not exist.");
        }

        try {
            $iterator = new DirectoryIterator($dirToScan);
        } catch (Throwable $ex) {
            $errorMessage = $ex->getMessage();
            if ($ex->getCode() === 5) {
                $errorMessage = esc_html__('Access Denied: No read permission to scan the root directory for cloning. Alternatively you can try the WP STAGING backup feature!', 'wp-staging');
            }

            throw new WPStagingException($errorMessage);
        }

        $directories = [];
        foreach ($iterator as $directory) {
            try {
                if ($directory->isDot() || $directory->isFile()) {
                    continue;
                }
            } catch (RuntimeException $openBaseDirException) {
                continue;
            }

            $directoryPath = $directory->getPathname();
            try {
                $path = $this->getPath($directory, $basePath, $identifier);
            } catch (UnexpectedValueException $e) {
                continue;
            }

            $directoryNode = new DirectoryNodeDto();
            $directoryNode->setName($directory->getFilename());

            if (strpos($directoryPath, 'wp-content') !== false && is_link($directoryPath)) {
                $directoryNode->setPath(realpath($directory->getPathname()));
            } elseif (is_link($directoryPath)) {
 
 
                $directoryNode->setPath(wp_normalize_path($directoryPath));
            } else {
                $directoryNode->setPath(trailingslashit($basePath) . ltrim($path, '/'));
            }

            $directoryNode->setIdentifier($identifier);
            $directoryNode->setBasePath($basePath);

            $directories[$directory->getFilename()] = $directoryNode;
        }

        return $directories;
    }







    public function directoryListing(array $directories, bool $parentChecked = true, bool $preserveSelection = false): string
    {
        uksort($directories, 'strcasecmp');

        $output = '';
        foreach ($directories as $dirName => $directory) {
 
            if (basename($dirName) === "\\") {
                continue;
            }

            $output .= $this->renderDirectoryNode($directory, $parentChecked, $preserveSelection);
        }

        return $output;
    }







    protected function getPath(DirectoryIterator $directory, string $basePath, string $identifier): string
    {
        try {
            $realPath = $this->isAllowVfsPath && strpos($directory->getPathname(), 'vfs://') === 0 ? $directory->getPathname() : $directory->getRealPath();
        } catch (RuntimeException $openBaseDirException) {
            throw new UnexpectedValueException($openBaseDirException->getMessage());
        }

        if ($realPath === false) {
            throw new UnexpectedValueException("The path '{$directory->getPathname()}' could not be resolved, likely a dangling symlink.");
        }

        $realPath = $this->filesystem->normalizePath($realPath);






        $path = $this->stripBasePath($realPath, $basePath);

        try {
            $isDir = $directory->isDir();
        } catch (RuntimeException $openBaseDirException) {
            throw new UnexpectedValueException($openBaseDirException->getMessage());
        }

 
        if (!$isDir || (strlen($path) < 1 && $identifier !== PathIdentifier::IDENTIFIER_WP_CONTENT)) {
            throw new UnexpectedValueException("The path '{$path}' is not a valid directory.");
        }

        return $path;
    }









    protected function stripBasePath(string $realPath, string $basePath): string
    {
        $isUncBasePath      = strpos($basePath, '//') === 0;
        $startsWithBasePath = $isUncBasePath ? stripos($realPath, $basePath) === 0 : strpos($realPath, $basePath) === 0;
        if (!$startsWithBasePath) {
            throw new UnexpectedValueException("The directory at path '{$realPath}' is not within the base path '{$basePath}'.");
        }

        return substr($realPath, strlen($basePath));
    }







    protected function renderDirectoryNode(DirectoryNodeDto $directory, bool $parentChecked = true, bool $preserveSelection = false): string
    {
        $path    = wp_normalize_path($directory->getPath());
        $relPath = str_replace($directory->getBasePath(), '', $path);
        $relPath = ltrim($relPath, '/');

 
        $isNotWPCoreDir = $this->isNonWpCoreDirectory($directory->getName(), $path);

        $class     = $isNotWPCoreDir ? self::WP_NON_CORE_DIR : self::WP_CORE_DIR;
        $dirType   = $this->getDirectoryType($path);
        $isScanned = 'false';
        $normalizedPath = trailingslashit($path);
        if (
            $normalizedPath === $this->directory->getWpContentDirectory()
            || $normalizedPath === $this->directory->getPluginsDirectory()
            || $normalizedPath === $this->directory->getActiveThemeParentDirectory()
        ) {
            $isScanned = 'true';
        }

        $showChildByDefault = false;
        if ($this->scanSubWpContentByDefault && ($normalizedPath === $this->wpContentPath . 'plugins/' || $normalizedPath === $this->wpContentPath . 'themes/' || $normalizedPath === $this->wpContentPath . 'uploads/')) {
            $isScanned          = 'true';
            $showChildByDefault = true;
        }

 
 
 
 
        $normalizedBasePath = untrailingslashit($directory->getBasePath());
        $isNavigatable      = 'true';
        if ($this->strUtils->startsWith($path, $normalizedBasePath . "/wp-admin") !== false || $this->strUtils->startsWith($path, $normalizedBasePath . "/wp-includes") !== false) {
            $isNavigatable = 'false';
        }

 
        $shouldBeChecked = $this->useDefaultSelection ? !$isNotWPCoreDir : $parentChecked;
        if (!$preserveSelection && $this->isUpdateOrResetJob() && (!$this->isPathInDirectories($path, $this->excludedDirectories, $directory->getBasePath()))) {
            $shouldBeChecked = true;
        } elseif (!$preserveSelection && $this->isUpdateOrResetJob()) {
            $shouldBeChecked = false;
        }

        if (!$preserveSelection && $this->isUpdateOrResetJob() && $class === self::WP_NON_CORE_DIR && !$this->isPathInDirectories($path, $this->extraDirectories)) {
            $shouldBeChecked = false;
        }

        $shouldBeChecked = $this->getShouldBeChecked($shouldBeChecked, $directory);
        $isDisabledDir = $directory->getName() === 'wp-admin' || $directory->getName() === 'wp-includes';

        $isDisabled = false;
        if (strpos($directory->getPath(), 'wp-content/' . Directory::STAGING_SITE_DIRECTORY) !== false) {
            $isDisabled      = true;
            $shouldBeChecked = false;
        }

        $isLink = false;
        if (strpos(trailingslashit($directory->getBasePath()) . $directory->getName(), 'wp-content') !== false && is_link(trailingslashit($directory->getBasePath()) . $directory->getName())) {
            $isDisabled      = true;
            $isNavigatable   = 'false';
            $shouldBeChecked = true;
            $isLink          = true;
            $relPath         = 'wp-content';
        }

        if ($this->isMandatoryExcludedDirectory($path)) {
            $isDisabled      = true;
            $shouldBeChecked = false;
        }

 
 
 
        $isLeaf = false;
        if ($isNavigatable === 'true' && !$this->directoryHasSubdirectories($path)) {
            $isLeaf        = true;
            $isNavigatable = 'false';
        }

 
 
        $isStagingSite = in_array(rtrim($path, '/\\'), $this->getStagingSiteDirectories(), true);

        return $this->templateEngine->render('staging/_partials/directory-navigation.php', [
            'scanner'           => $this,
            'prefix'            => $directory->getIdentifier(),
            'relPath'           => $relPath,
            'class'             => $class,
            'dirType'           => $dirType,
            'isScanned'         => $isScanned,
            'isNavigatable'     => $isNavigatable,
            'isLeaf'            => $isLeaf,
            'isStagingSite'     => $isStagingSite,
            'shouldBeChecked'   => $shouldBeChecked,
            'parentChecked'     => $parentChecked,
            'directoryDisabled' => $isNotWPCoreDir || $isDisabledDir,
            'isDisabled'        => $isDisabled,
            'dirName'           => $directory->getName(),
            'gifLoaderPath'     => $this->loaderIcon,
            'infoIconPath'      => $this->infoIcon,
            'isDebugMode'       => false,
            'dataPath'          => $directory->getPath(),
            'basePath'          => $directory->getBasePath(),
            'forceDefault'      => $preserveSelection,
            'dirPath'           => $path,
            'isLink'            => $isLink,
            'showChild'         => $showChildByDefault,
        ]);
    }








    private function directoryHasSubdirectories(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        try {
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $item) {
                if ($item->isDot()) {
                    continue;
                }

                if ($item->isDir()) {
                    return true;
                }
            }
        } catch (\Exception $e) {
 
 
            return true;
        }

        return false;
    }







    private function getStagingSiteDirectories(): array
    {
        if ($this->stagingSiteDirectories !== null) {
            return $this->stagingSiteDirectories;
        }

        $this->stagingSiteDirectories = [];
        try {
            foreach (WPStaging::make(Sites::class)->getStagingDirectories() as $directory) {
                $this->stagingSiteDirectories[] = wp_normalize_path(rtrim((string)$directory, '/\\'));
            }
        } catch (\Throwable $e) {
            $this->stagingSiteDirectories = [];
        }

        return $this->stagingSiteDirectories;
    }








    protected function isNonWpCoreDirectory(string $dirname, string $path): bool
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
            $this->directory->getWpContentDirectory(),
            $this->directory->getPluginsDirectory(),
            $this->directory->getActiveThemeParentDirectory(),
            $this->directory->getUploadsDirectory(),
            $this->directory->getMuPluginsDirectory(),
        ];

        foreach ($wpDirectories as $wpDirectory) {
            if (strpos(trailingslashit($path), $wpDirectory) !== false) {
                return false;
            }
        }

        return true;
    }









    protected function isPathInDirectories(string $path, array $directories, $basePath = null): bool
    {
        return $this->pathChecker->isPathInPathsList($path, $directories, true, $basePath);
    }





    protected function isMandatoryExcludedDirectory(string $path): bool
    {
        $path = untrailingslashit(wp_normalize_path($path));
        foreach ($this->getMandatoryExcludedDirectories() as $excludedDirectory) {
            if ($path === untrailingslashit(wp_normalize_path($excludedDirectory))) {
                return true;
            }
        }

        return false;
    }




    protected function getMandatoryExcludedDirectories(): array
    {
        return $this->directory->getWpStagingDataDirectories();
    }

    protected function isWpContentOutsideAbspath(): bool
    {
        return $this->siteInfo->isWpContentOutsideAbspath();
    }

    protected function isCheckDirectorySize(): bool
    {
        return false;
    }




    protected function getShouldBeChecked(bool $shouldBeChecked, DirectoryNodeDto $directory): bool
    {
        return $shouldBeChecked;
    }




    protected function getDirectoryType(string $path): string
    {
        $dirType = 'other';
        if ($this->strUtils->startsWith($path, $this->directory->getPluginsDirectory()) !== false) {
            $pluginPath = $this->strUtils->strReplaceFirst($this->directory->getPluginsDirectory(), '', $path);
            $dirType    = strpos($pluginPath, '/') === false ? 'plugin' : 'other';
        } elseif ($this->strUtils->startsWith($path, $this->directory->getActiveThemeParentDirectory()) !== false) {
            $themePath = $this->strUtils->strReplaceFirst($this->directory->getActiveThemeParentDirectory(), '', $path);
            $dirType   = strpos($themePath, '/') === false ? 'theme' : 'other';
        }

        return $dirType;
    }
}
