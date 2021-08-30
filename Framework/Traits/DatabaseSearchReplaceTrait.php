<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Core\Utils\Helper;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Staging\Sites;

/**
 * Trait DatabaseSearchReplaceTrait
 *
 * This trait puts together some common functionality for database search and replace
 * used both by Cloning and Backups. This is not ideal, and should be refactored in the future
 * for a more robust and proper architecture.
 *
 * @package WPStaging\Framework\Traits
 */
trait DatabaseSearchReplaceTrait
{
    private $excludedStrings = [
        'Admin_custome_login_Slidshow',
        'Admin_custome_login_Social',
        'Admin_custome_login_logo',
        'Admin_custome_login_text',
        'Admin_custome_login_login',
        'Admin_custome_login_top',
        'Admin_custome_login_dashboard',
        'Admin_custome_login_Version',
        'upload_path',
        'wpstg_existing_clones_beta',
        'wpstg_existing_clones',
        Sites::STAGING_SITES_OPTION,
        'wpstg_settings',
        'wpstg_license_status',
        'wpstg_tmp_data',
        'siteurl',
        'home'
    ];

    public function excludedStrings()
    {
        return $this->excludedStrings;
    }

    /**
     * Prepend the following characters to string: %2F%2F, \/\/, //
     * This is to make sure that only valid hostnames are replaced
     * @param $string
     * @return string[]
     */
    public function generateHostnamePatterns($string)
    {
        return [
            '%2F%2F' . str_replace('/', '%2F', $string), // HTML entity for WP Backery Page Builder Plugin
            '\/\/' . str_replace('/', '\/', $string), // Escaped \/ used by revslider and several visual editors
            '//' . $string // //example.com
        ];
    }

    private function getSourceHostname()
    {
        $helper = WPStaging::getInstance()->getContainer()->make(Helper::class);

        if ($this->isSubDir()) {
            return trailingslashit($helper->getHomeUrlWithoutScheme()) . $this->getSubDir();
        }

        return $helper->getHomeUrlWithoutScheme();
    }

    /**
     * Check if WP is installed in subdir
     * @return boolean
     */
    private function isSubDir()
    {
        // Compare names without scheme to bypass cases where siteurl and home have different schemes http / https
        // This is happening much more often than you would expect
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

        return $home !== $siteurl;
    }

    /**
     * Get the install sub directory if WP is installed in sub directory
     * @return string
     */
    private function getSubDir()
    {
        $home = get_option('home');
        $siteurl = get_option('siteurl');

        if (empty($home) || empty($siteurl)) {
            return '';
        }

        return str_replace([$home, '/'], '', $siteurl);
    }
}
