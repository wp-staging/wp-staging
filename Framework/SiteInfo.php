<?php

namespace WPStaging\Framework;

/**
 * Class SiteInfo
 *
 * Provides information about the current site.
 *
 * @package WPStaging\Site
 */
class SiteInfo
{
    /**
     * @return bool True if is staging site. False otherwise.
     */
    public function isStaging()
    {
        if (file_exists(ABSPATH . '.wp-staging-cloneable')) {
            return false;
        }

        if (get_option("wpstg_is_staging_site") === "true") {
            return true;
        }

        if (file_exists(ABSPATH . '.wp-staging')) {
            return true;
        }

        return false;
    }

    /**
     * Check if WP is installed in sub directory
     * If siteurl and home are not identical we assume the site is located in a subdirectory
     * related to that instruction https://wordpress.org/support/article/giving-wordpress-its-own-directory/
     *
     * @return boolean
     */
    public function isInstalledInSubDir()
    {
        // Compare names without scheme (http/https) to bypass case where siteurl and home are stored with different schemes in database
        // This happens much more often than you expect
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

        return $home !== $siteurl;
    }
}
