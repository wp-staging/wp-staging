<?php

namespace WPStaging\Staging\Service;

use DirectoryIterator;
use Throwable;
use UnexpectedValueException;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Framework\Filesystem\Filters\ExcludeFilter;
use WPStaging\Framework\Filesystem\PathChecker;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Core\WPStaging;
use WPStaging\Staging\Dto\DirectoryNodeDto;
use WPStaging\Staging\Sites;

/**
 * Scans filesystem roots and prepares directory data for the staging selection UI.
 */
class DirectoryScanner
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

    /**
     * @var TemplateEngine
     */
    protected $templateEngine;

    /**
     * @var Directory
     */
    protected $directory;

    /**
     * @var Strings
     */
    protected $strUtils;

    /**
     * @var PathChecker
     */
    protected $pathChecker;

    /**
     * @var SiteInfo
     */
    protected $siteInfo;

    /**
     * @var AbstractStagingSetup
     */
    protected $stagingSetup;

    /**
     * Normalized absolute paths of existing staging sites, lazily loaded.
     *
     * @var array|null
     */
    private $stagingSiteDirectories = null;

    /**
     * @var string
     */
    protected $loaderIcon = '';

    /**
     * @var string
     */
    protected $infoIcon = '';

    /**
     * @var bool
     */
    protected $isAllowVfsPath = false;

    /**
     * @var bool
     */
    protected $scanSubWpContentByDefault = false;

    /**
     * @var array
     */
    protected $excludedDirectories = [];

    /**
     * @var array
     */
    protected $extraDirectories = [];

    /** @var string */
    protected $absPath = ABSPATH;

    /** @var string */
    protected $wpContentPath = WP_CONTENT_DIR;

    /** @var bool */
    protected $useDefaultSelection = false;

    /**
     * @var bool
     */
    protected $showFileDestination = true;

    public function __construct(TemplateEngine $templateEngine, Assets $assets, Directory $directory, Strings $strUtils, PathChecker $pathChecker, SiteInfo $siteInfo)
    {
        $this->templateEngine = $templateEngine;
        $this->directory      = $directory;
        $this->strUtils       = $strUtils;
        $this->pathChecker    = $pathChecker;
        $this->siteInfo       = $siteInfo;
        $this->loaderIcon     = $assets->getAssetsUrl('img/spinner.gif');
    }

    /**
     * @param bool $isAllowVfsPath
     * @return void
     */
    public function setIsAllowVfsPath(bool $isAllowVfsPath)
    {
        $this->isAllowVfsPath = $isAllowVfsPath;
    }

    /**
     * @return void
     */
    public function setStagingSetup(AbstractStagingSetup $stagingSetup)
    {
        $this->stagingSetup        = $stagingSetup;
        $this->excludedDirectories = $stagingSetup->getStagingSiteDto()->getExcludedDirectories();
        $this->extraDirectories    = $stagingSetup->getStagingSiteDto()->getExtraDirectories();
    }

    /**
     * @param bool $showFileDestination
     * @return void
     */
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

        // If wp-content is outside ABSPATH, then scan it too
        if ($this->isWpContentOutsideAbspath()) {
            $wpContentDirectories = $this->scanDirectory(dirname($this->wpContentPath), $this->wpContentPath, PathIdentifier::IDENTIFIER_WP_CONTENT);
            $directories          = array_merge($directories, $wpContentDirectories);
        }

        /** Value of parent checked will be ignored instead the default selection will be used */
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

    /**
     * @param string $dirToScan
     * @param string $basePath
     * @param string $identifier
     * @return DirectoryNodeDto[]
     */
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
            if ($directory->isDot() || $directory->isFile()) {
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
                // Keep the symlink's own location, not its target: a folder linking into
                // wp-content would otherwise be treated as WP core and pre-selected.
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

    /**
     * @param DirectoryNodeDto[] $directories
     * @param bool $parentChecked
     * @param bool $preserveSelection
     * @return string
     */
    public function directoryListing(array $directories, bool $parentChecked = true, bool $preserveSelection = false): string
    {
        uksort($directories, 'strcasecmp');

        $output = '';
        foreach ($directories as $dirName => $directory) {
            // Not a directory, possibly a symlink, therefore we will skip it
            if (basename($dirName) === "\\") {
                continue;
            }

            $output .= $this->renderDirectoryNode($directory, $parentChecked, $preserveSelection);
        }

        return $output;
    }

    /**
     * @param DirectoryIterator $directory
     * @param string $basePath
     * @param string $identifier
     * @return string
     */
    protected function getPath(DirectoryIterator $directory, string $basePath, string $identifier): string
    {
        $realPath = $this->isAllowVfsPath && strpos($directory->getPathname(), 'vfs://') === 0 ? $directory->getPathname() : $directory->getRealPath();
        $realPath = wp_normalize_path($realPath);

        /**
         * Do not follow root path like src/web/..
         * This must be done before \SplFileInfo->isDir() is used!
         * Prevents open base dir restriction fatal errors
         */
        if (strpos($realPath, $basePath) !== 0) {
            throw new UnexpectedValueException("The directory at path '{$realPath}' is not within the base path '{$basePath}'.");
        }

        $path = str_replace($basePath, '', $realPath);
        // Using strpos() for symbolic links as they could create nasty stuff in nix stuff for directory structures
        if (!$directory->isDir() || (strlen($path) < 1 && $identifier !== PathIdentifier::IDENTIFIER_WP_CONTENT)) {
            throw new UnexpectedValueException("The path '{$path}' is not a valid directory.");
        }

        return $path;
    }

    /**
     * @param DirectoryNodeDto $directory
     * @param bool $parentChecked
     * @param bool $preserveSelection
     * @return string
     */
    protected function renderDirectoryNode(DirectoryNodeDto $directory, bool $parentChecked = true, bool $preserveSelection = false): string
    {
        $path    = wp_normalize_path($directory->getPath());
        $relPath = str_replace($directory->getBasePath(), '', $path);
        $relPath = ltrim($relPath, '/');

        // Check if directory name or directory path is not WP core folder
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

        // Make wp-includes and wp-admin directory items not expandable. The base
        // path keeps ABSPATH's trailing slash, so strip it before composing the
        // core paths — otherwise the double slash never matches and both folders
        // stay wrongly expandable.
        $normalizedBasePath = untrailingslashit($directory->getBasePath());
        $isNavigatable      = 'true';
        if ($this->strUtils->startsWith($path, $normalizedBasePath . "/wp-admin") !== false || $this->strUtils->startsWith($path, $normalizedBasePath . "/wp-includes") !== false) {
            $isNavigatable = 'false';
        }

        // Decide if item checkbox is active or not
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

        // A still-navigatable folder with no subdirectories is a leaf: there is
        // nothing to fetch, so mark it non-navigatable and let the view render
        // it as non-expandable instead of firing a request that returns nothing.
        $isLeaf = false;
        if ($isNavigatable === 'true' && !$this->directoryHasSubdirectories($path)) {
            $isLeaf        = true;
            $isNavigatable = 'false';
        }

        // Flag folders that are themselves an existing staging site so the tree
        // can explain why they are excluded by default.
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

    /**
     * Cheaply checks whether a directory contains at least one subdirectory.
     * Returns on the first one found so it stays light even on large folders.
     *
     * @param string $path
     * @return bool
     */
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
            // On any error assume it may have children so a legitimately
            // expandable folder is never hidden.
            return true;
        }

        return false;
    }

    /**
     * Returns the normalized absolute paths of existing staging sites so their
     * folders can be flagged in the tree.
     *
     * @return array
     */
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

    /**
     * Check if directory name or directory path is not WP core folder
     *
     * @param string $dirname
     * @param string $path
     * @return bool
     */
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
     * @param string $path
     * @return bool
     */
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

    /**
     * @return string[]
     */
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

    /**
     * Used during push
     */
    protected function getShouldBeChecked(bool $shouldBeChecked, DirectoryNodeDto $directory): bool
    {
        return $shouldBeChecked;
    }

    /**
     * Overriden during push
     */
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
