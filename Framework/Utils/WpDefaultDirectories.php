<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Utils;

use DirectoryIterator;
use WPStaging\Backend\Modules\Jobs\Scan;

// TODO PHP7.1; constant visibility
class WpDefaultDirectories
{
    const WP_ADMIN = 'wp-admin';
    const WP_INCLUDES = 'wp-includes';
    const WP_CONTENT = 'wp-content';
    const SITES = 'sites';
    const MULTI_OLD_UPLOADS_DIR = 'blogs.dir';
    const MULTI_UPLOADS_DIR = 'sites';

    /**
     * @var Strings
     */
    private $strUtils;

    /**
     * WordPress core directories: wp-admin, wp-includes, wp-content
     * @var array
     */
    private $coreDirectories;

    /**
     * Sanitized ABSPATH for comparing against windows iterator
     * @var string
     */
    private $wpRoot;

    /**
     * refresh cache of upload path
     * @var boolean default false
     */
    private $refreshUploadPathCache = false;

    public function __construct()
    {
        // @todo inject using DI
        $this->strUtils = new Strings();
        $this->wpRoot = $this->strUtils->sanitizeDirectorySeparator(ABSPATH);
    }

    /**
     * @param boolean $shouldRefresh
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
     * Result will not have any appending or prepending slashes! Directory separator will be forward slash always for Microsoft IIS compatibility
     *
     * @return string
     */
    public function getRelativeUploadPath()
    {
        $relPath = str_replace($this->wpRoot, null, $this->getUploadsPath());

        return trim($relPath, '/');
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
        // Setting the 3rd and last parameter to true will refresh the cache return the latest value. Set to true for tests
        $uploads = wp_upload_dir(null, true, $this->refreshUploadPathCache);

        // Adding slashes at before and end of absolute path to WordPress uploads directory
        $uploadsAbsPath = trailingslashit($uploads['basedir']);

        return $this->strUtils->sanitizeDirectorySeparator($uploadsAbsPath);
    }

    /**
     * Get site specific absolute WP uploads path e.g.
     * Multisites: /var/www/htdocs/example.com/wp-content/uploads/sites/1 or /var/www/htdocs/example.com/wp-content/blogs.dir/1/files
     * Single sites: /var/www/htdocs/example.com/wp-content/uploads
     * This is compatible to old WordPress multisite version which contained blogs.dir
     * @return string
     */
    public function getSiteUploadsPath()
    {
        $uploads = wp_upload_dir(null, false);
        $baseDir = $this->strUtils->sanitizeDirectorySeparator($uploads['basedir']);

        // If multisite (and if not the main site in a post-MU network)
        if (is_multisite() && !( is_main_network() && is_main_site() && defined('MULTISITE') )) {
            // blogs.dir is used on WP 3.5 and lower
            if (strpos($baseDir, 'blogs.dir') !== false) {
                // remove this piece from the basedir: /blogs.dir/2/files
                $uploadDir = wpstg_replace_first_match('/blogs.dir/' . get_current_blog_id() . '/files', null, $baseDir);
                $dir       = $this->strUtils->sanitizeDirectorySeparator($uploadDir . '/blogs.dir');
            } else {
                // remove this piece from the basedir: /sites/2
                $uploadDir = wpstg_replace_first_match('/sites/' . get_current_blog_id(), null, $baseDir);
                $dir       = $this->strUtils->sanitizeDirectorySeparator($uploadDir . '/sites');
            }

            return $dir;
        }
        return $baseDir;
    }

    /**
     * Get the relative path of wp content directory
     * @return string
     */
    public function getRelativeWpContentPath()
    {
        $wpContentDir = $this->strUtils->sanitizeDirectorySeparator(WP_CONTENT_DIR);
        $relPath = str_replace($this->wpRoot, null, $wpContentDir);

        return trim($relPath, '/');
    }

    /**
     * Get the relative path of plugins directory
     * @return string
     */
    public function getRelativePluginPath()
    {
        $wpPluginDir = $this->strUtils->sanitizeDirectorySeparator(WP_PLUGIN_DIR);
        $relPath = str_replace($this->wpRoot, null, $wpPluginDir);

        return trim($relPath, '/');
    }

    /**
     * Get the relative path of themes directory
     * @return string
     */
    public function getRelativeThemePath()
    {
        $relWpContentPath = $this->getRelativeWpContentPath();
        return trailingslashit($relWpContentPath) . "themes";
    }

    /**
     * Get array of wp core directories and their one level sub dir with flag 0|1
     * i.e. wp-content, wp-admin, wp-includes, plugins etc
     *
     * @return array
     */
    public function getWpCoreDirectories()
    {
        $this->coreDirectories = [];

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

            $this->handleSelfAndSubDirs($path);
        }

        // Gather Plugins
        $pluginsDir = $this->wpRoot . $this->getRelativePluginPath();
        $this->handleSelfAndSubDirs($pluginsDir);

        // Gather Themes
        $themesDir = $this->wpRoot . $this->getRelativeThemePath();
        $this->handleSelfAndSubDirs($themesDir);

        // Gather Uploads
        $this->handleSelfAndSubDirs($this->getUploadsPath());

        return $this->coreDirectories;
    }

    /**
     * Add directory and it subdirectories to included directory list if not already added
     *
     * @param string $path
     */
    protected function handleSelfAndSubDirs($path)
    {
        $this->addDirectoryToList($path, Scan::IS_NON_RECURSIVE);
        $directories = new DirectoryIterator($path);
        foreach ($directories as $directory) {
            if ($directory->isDot() || $directory->isFile() || $directory->isLink()) {
                continue;
            }

            $this->addDirectoryToList($directory->getPathname(), Scan::IS_RECURSIVE);
        }
    }

    /**
     * Add directory if not already exist
     * Override the flag value with scanned only if directory already present and was scanned
     *
     * @param string $path
     * @param int $flag
     */
    protected function addDirectoryToList($path, $flag)
    {
        $path = untrailingslashit($this->strUtils->sanitizeDirectorySeparator($path));
        $dirInfo = $path . Scan::DIRECTORY_PATH_FLAG_SEPARATOR . $flag;

        if (in_array($dirInfo, $this->coreDirectories)) {
            return;
        }

        if (in_array($path . Scan::DIRECTORY_PATH_FLAG_SEPARATOR . Scan::IS_NON_RECURSIVE, $this->coreDirectories)) {
            return;
        }

        if ($flag === Scan::IS_NON_RECURSIVE) {
            for ($i = 0, $iMax = count($this->coreDirectories); $i < $iMax; $i++) {
                if ($this->coreDirectories[$i] === $path . Scan::DIRECTORY_PATH_FLAG_SEPARATOR . Scan::IS_RECURSIVE) {
                    $this->coreDirectories[$i] = $dirInfo;
                    return;
                }
            }
        }

        $this->coreDirectories[] = $dirInfo;
    }

    /**
     * Get selected directories according to the flag and filter them
     *
     * @param string $directoriesRequest
     * @param boolean $areDirectoriesIncluded default false
     * @return array
     *
     * @todo find a better place
     */
    public function getSelectedDirectories($directoriesRequest, $areDirectoriesIncluded = false)
    {
        $directories = $this->getWpCoreDirectories();
        if (empty($directoriesRequest) || $directoriesRequest === '') {
            return $directories;
        }

        if ($areDirectoriesIncluded) {
            $directories = wpstg_urldecode(explode(Scan::DIRECTORIES_SEPARATOR, $directoriesRequest));
            $directories = array_map(function ($directory) {
                return $this->wpRoot . $directory;
            }, $directories);

            return $directories;
        }

        $excludedDirectories = wpstg_urldecode(explode(Scan::DIRECTORIES_SEPARATOR, $directoriesRequest));
        $excludedDirectories = array_map(function ($directory) {
            return $this->wpRoot . $directory;
        }, $excludedDirectories);

        $directories = array_filter($directories, function ($directory) use ($excludedDirectories) {
            $directory = explode(Scan::DIRECTORY_PATH_FLAG_SEPARATOR, $directory)[0];
            foreach ($excludedDirectories as $excludedDirectory) {
                $excludedDirectory = explode(Scan::DIRECTORY_PATH_FLAG_SEPARATOR, $excludedDirectory)[0];
                if ($directory === $excludedDirectory) {
                    return false;
                }
            }

            return true;
        });

        return array_values($directories);
    }
}
