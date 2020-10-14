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

        if ("true" === get_option("wpstg_is_staging_site")) {
            return true;
        }

        if (file_exists(ABSPATH . '.wp-staging')) {
            return true;
        }

        return false;
    }
}
