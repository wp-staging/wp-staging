<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Staging\Sites;










trait DatabaseSearchReplaceTrait
{
    use WordPressOptionNameTrait;

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
        FinishBackupTask::OPTION_LAST_BACKUP,
        'wpstg_settings',
        'wpstg_license_status',
        'wpstg_tmp_data',
        'siteurl',
        'home',
    ];

    public function excludedStrings()
    {
        return array_merge($this->excludedStrings, $this->getPrefixIndependentWordPressOptionNames());
    }







    public function generateHostnamePatterns($string)
    {
        return [
            '%2F%2F' . str_replace('/', '%2F', $string), 
            '\/\/' . str_replace('/', '\/', $string), 
            '//' . $string, 
        ];
    }

    protected function getSourceHostname()
    {
        $urlsHelper = WPStaging::make(Urls::class);

        if ($this->isSubDir()) {
            return trailingslashit($urlsHelper->getHomeUrlWithoutScheme()) . $this->getSubDir();
        }

        return $urlsHelper->getHomeUrlWithoutScheme();
    }





    private function isSubDir()
    {
 
 
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

        return $home !== $siteurl;
    }





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
