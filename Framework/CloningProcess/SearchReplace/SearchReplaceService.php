<?php


namespace WPStaging\Framework\CloningProcess\SearchReplace;


class SearchReplaceService
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
}