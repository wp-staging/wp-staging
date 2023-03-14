<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Utils;

use DirectoryIterator;
use WPStaging\Framework\Filesystem\Scanning\ScanConst;

// TODO PHP7.1; constant visibility
class WpDefaultDirectories
{
    /**
     * @var Strings
     */
    private $strUtils;

    /**
     * Sanitized ABSPATH for comparing against windows iterator
     * @var string
     */
    private $wpRoot;

    /**
     * refresh cache of upload path
     * @var bool default false
     */
    private $refreshUploadPathCache = false;

    public function __construct()
    {
        // @todo inject using DI
        $this->strUtils = new Strings();
        $this->wpRoot = $this->strUtils->sanitizeDirectorySeparator(ABSPATH);
    }

    /**
     * @param bool $shouldRefresh
     */
    public function shouldRefreshUploadPathCache($shouldRefresh = true)
    {
        $this->refreshUploadPathCache = $shouldRefresh;
    }

    /**
     * Get path to the uploads folder, relatively to the wp root folder.
     * Allows custom uploads folders.
     * For instance, returned strings can be:
     *  `
     * `custom-upload-folder`
     * `wp-content/uploads`
     * `wp-content/uploads/sites/2`
     *
     * Directory separator will be forward slash always for Microsoft IIS compatibility
     *
     * @param int $mode Optional. Slash Mode. Default SlashMode::NO_SLASH.
     *                      Use SlashMode::NO_SLASH, if you don't want trailing and leading slash.
     *                      Use SlashMode::TRAILING_SLASH, if you want trailing forward slash.
     *                      Use SlashMode::LEADING_SLASH, if you want leading forward slash.
     *                      Use SlashMode::BOTH_SLASHES, if you want both trailing and leading forward slash.
     *
     * @return string
     */
    public function getRelativeUploadPath($mode = SlashMode::NO_SLASH)
    {
        $relPath = str_replace($this->wpRoot, '', $this->getUploadsPath());

        return $this->slashit($relPath, $mode);
    }

    /*
     * Get the absolute path of uploads directory
     * @return string
     */
    public function getUploadsPath()
    {
        // Get upload directory information. Default is ABSPATH . 'wp-content/uploads'
        // Could have been customized by populating the db option upload_path or the constant UPLOADS in wp-config
        // If both are defined WordPress will uses the value of the UPLOADS constant
        // First two parameters in wp_upload_dir are default parameter and last parameter is to refresh the cache
        // Setting the 3rd parameter to true will refresh the cache and return the latest value. Set to true for tests
        $uploads = wp_upload_dir(null, true, $this->refreshUploadPathCache);

        // Adding slashes at before and end of absolute path to WordPress uploads directory
        $uploadsAbsPath = trailingslashit($uploads['basedir']);

        return $this->strUtils->sanitizeDirectorySeparator($uploadsAbsPath);
    }

    /**
     * Get the relative path of wp content directory
     * @param int $mode Optional. Slash Mode. Default SlashMode::NO_SLASH.
     *                      Use SlashMode::NO_SLASH, if you don't want trailing and leading slash.
     *                      Use SlashMode::TRAILING_SLASH, if you want trailing forward slash.
     *                      Use SlashMode::LEADING_SLASH, if you want leading forward slash.
     *                      Use SlashMode::BOTH_SLASHES, if you want both trailing and leading forward slash.
     * @return string
     */
    public function getRelativeWpContentPath($mode = SlashMode::NO_SLASH)
    {
        $wpContentDir = $this->strUtils->sanitizeDirectorySeparator(WP_CONTENT_DIR);
        $relPath = str_replace($this->wpRoot, '', $wpContentDir);

        return $this->slashit($relPath, $mode);
    }

    /**
     * Get the relative path of plugins directory
     * @param int $mode Optional. Slash Mode. Default SlashMode::NO_SLASH.
     *                      Use SlashMode::NO_SLASH, if you don't want trailing and leading slash.
     *                      Use SlashMode::TRAILING_SLASH, if you want trailing forward slash.
     *                      Use SlashMode::LEADING_SLASH, if you want leading forward slash.
     *                      Use SlashMode::BOTH_SLASHES, if you want both trailing and leading forward slash.
     * @return string
     */
    public function getRelativePluginPath($mode = SlashMode::NO_SLASH)
    {
        $wpPluginDir = $this->strUtils->sanitizeDirectorySeparator(WP_PLUGIN_DIR);
        $relPath = str_replace($this->wpRoot, '', $wpPluginDir);

        return $this->slashit($relPath, $mode);
    }

    /**
     * Get the relative path of mu plugins directory
     * @param int $mode Optional. Slash Mode. Default SlashMode::NO_SLASH.
     *                      Use SlashMode::NO_SLASH, if you don't want trailing and leading slash.
     *                      Use SlashMode::TRAILING_SLASH, if you want trailing forward slash.
     *                      Use SlashMode::LEADING_SLASH, if you want leading forward slash.
     *                      Use SlashMode::BOTH_SLASHES, if you want both trailing and leading forward slash.
     * @return string
     */
    public function getRelativeMuPluginPath($mode = SlashMode::NO_SLASH)
    {
        $wpPluginDir = $this->strUtils->sanitizeDirectorySeparator(WPMU_PLUGIN_DIR);
        $relPath = str_replace($this->wpRoot, '', $wpPluginDir);

        return $this->slashit($relPath, $mode);
    }

    /**
     * Get the relative path of themes directory
     * @param int $mode Optional. Slash Mode. Default SlashMode::NO_SLASH.
     *                      Use SlashMode::NO_SLASH, if you don't want trailing and leading slash.
     *                      Use SlashMode::TRAILING_SLASH, if you want trailing forward slash.
     *                      Use SlashMode::LEADING_SLASH, if you want leading forward slash.
     *                      Use SlashMode::BOTH_SLASHES, if you want both trailing and leading forward slash.
     * @return string
     */
    public function getRelativeThemePath($mode = SlashMode::NO_SLASH)
    {
        $relPath = $this->getRelativeWpContentPath() . '/themes';

        return $this->slashit($relPath, $mode);
    }

    /**
     * Get array of wp core directories with flag 0|1
     * i.e. wp-content, wp-admin, wp-includes
     *
     * @return array
     */
    public function getWpCoreDirectories()
    {
        $coreDirectories = [];

        $directories = new DirectoryIterator(ABSPATH);
        foreach ($directories as $directory) {
            if ($directory->isDot() || $directory->isFile() || $directory->isLink()) {
                continue;
            }

            $name = $directory->getBasename();
            $path = $this->strUtils->sanitizeDirectorySeparator($directory->getPathname());

            // should skip if not wp core directory
            $shouldSkip = ($name !== 'wp-admin' &&
                $name !== 'wp-includes' &&
                $name !== 'wp-content' &&
                $name !== 'sites') &&
                strpos(strrev($path), strrev($this->wpRoot . "wp-admin")) === false &&
                strpos(strrev($path), strrev($this->wpRoot . "wp-includes")) === false &&
                strpos(strrev($path), strrev($this->wpRoot . "wp-content")) === false;

            if ($shouldSkip) {
                continue;
            }

            $path = untrailingslashit($this->strUtils->sanitizeDirectorySeparator($path));
            $coreDirectories[] = $path;
        }

        return $coreDirectories;
    }

    /**
     * Get excluded directories and map it to array
     *
     * @param string $directoriesRequest
     * @return array
     *
     * @todo find a better place
     */
    public function getExcludedDirectories($directoriesRequest)
    {
        if ((empty($directoriesRequest))) {
            return [];
        }

        $excludedDirectories = explode(ScanConst::DIRECTORIES_SEPARATOR, wpstg_urldecode($directoriesRequest));
        $excludedDirectories = array_map(function ($directory) {
            return $this->slashit($directory, SlashMode::LEADING_SLASH);
        }, $excludedDirectories);

        return $excludedDirectories;
    }

    /**
     * Different slash mode for path
     * @param string $path
     * @param int $mode Optional. Slash Mode. Default SlashMode::NO_SLASH.
     *                      Use SlashMode::NO_SLASH, if you don't want trailing and leading slash.
     *                      Use SlashMode::TRAILING_SLASH, if you want trailing forward slash.
     *                      Use SlashMode::LEADING_SLASH, if you want leading forward slash.
     *                      Use SlashMode::BOTH_SLASHES, if you want both trailing and leading forward slash.
     * @return string
     */
    private function slashit($path, $mode = SlashMode::NO_SLASH)
    {
        $path = trim(trim($path, '\\'), '/');
        if ($mode === SlashMode::BOTH_SLASHES) {
            return '/' . $path . '/';
        }

        if ($mode === SlashMode::TRAILING_SLASH) {
            return $path . '/';
        }

        if ($mode === SlashMode::LEADING_SLASH) {
            return '/' . $path;
        }

        return $path;
    }
}
