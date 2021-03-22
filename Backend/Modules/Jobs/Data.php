<?php

namespace WPStaging\Backend\Modules\Jobs;


use WPStaging\Framework\CloningProcess\Data\DataCloningDto;
use WPStaging\Framework\CloningProcess\Data\CopyWpConfig;
use WPStaging\Framework\CloningProcess\Data\MultisiteAddNetworkAdministrators;
use WPStaging\Framework\CloningProcess\Data\MultisiteUpdateActivePlugins;
use WPStaging\Framework\CloningProcess\Data\MultisiteUpdateTablePrefix;
use WPStaging\Framework\CloningProcess\Data\ResetIndexPhp;
use WPStaging\Framework\CloningProcess\Data\UpdateSiteUrlAndHome;
use WPStaging\Framework\CloningProcess\Data\UpdateTablePrefix;
use WPStaging\Framework\CloningProcess\Data\UpdateWpConfigConstants;
use WPStaging\Framework\CloningProcess\Data\UpdateWpConfigTablePrefix;
use WPStaging\Framework\CloningProcess\Data\UpdateWpOptionsTablePrefix;
use WPStaging\Framework\CloningProcess\Data\UpdateStagingOptionsTable;
use WPStaging\Core\Utils\Helper;
use WPStaging\Framework\Utils\Strings;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Class Data
 * @package WPStaging\Backend\Modules\Jobs
 */
class Data extends CloningProcess
{
    /**
     * @var string
     */
    private $prefix;

    /**
     *
     * @var string
     */
    private $homeUrl;

    /**
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Tables e.g wpstg3_options
     * @var array
     */
    private $tables;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->initializeDbObjects();

        $this->prefix = $this->options->prefix;

        $this->getTables();

        $this->homeUrl = (new Helper())->getHomeUrl();

        $this->baseUrl = (new Helper())->getBaseUrl();

        // Reset current step
        if ($this->options->currentStep == 0) {
            $this->options->currentStep = 0;
        }
    }

    /**
     * Start Module
     * @return object
     */
    public function start()
    {
        // Execute steps
        $this->run();

        // Save option, progress
        $this->saveOptions();

        return ( object )$this->response;
    }

    /**
     * @param int $stepNumber
     * @return DataCloningDto
     */
    protected function getCloningDto($stepNumber)
    {
        return new DataCloningDto(
            $this,
            $this->stagingDb,
            $this->productionDb,
            $this->isExternalDatabase(),
            $this->isMultisiteAndPro(),
            $this->isExternalDatabase() ? $this->options->databaseServer : null,
            $this->isExternalDatabase() ? $this->options->databaseUser : null,
            $this->isExternalDatabase() ? $this->options->databasePassword : null,
            $this->isExternalDatabase() ? $this->options->databaseDatabase : null,
            $stepNumber,
            $this->prefix,
            $this->tables,
            $this->getOptions()->destinationDir,
            $this->getStagingSiteUrl(),
            (new WpDefaultDirectories())->getRelativeUploadPath(),
            $this->settings,
            $this->homeUrl,
            $this->baseUrl,
            $this->options->mainJob
        );
    }

    /**
     * Get a list of tables to copy
     */
    private function getTables()
    {
        $strings = new Strings();
        $this->tables = [];
        foreach ($this->options->tables as $table) {
            $this->tables[] = $this->options->prefix . $strings->str_replace_first($this->productionDb->prefix, null, $table);
        }
        if ($this->isMultisiteAndPro()) {
            // Add extra global tables from main multisite (wpstg[x]_users and wpstg[x]_usermeta)
            $this->tables[] = $this->options->prefix . 'users';
            $this->tables[] = $this->options->prefix . 'usermeta';
        }
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        if ($this->isMultisiteAndPro()) {
            $this->options->totalSteps = 9;
        } else {
            $this->options->totalSteps = 7;
        }
    }


    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute()
    {
        // Over limits threshold
        if ($this->isOverThreshold()) {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // No more steps, finished
        if ($this->isFinished()) {
            $this->prepareResponse(true, false);
            return false;
        }

        // Execute step
        $stepMethodName = "step" . $this->options->currentStep;
        if (!$this->{$stepMethodName}()) {
            $this->prepareResponse(false, false);
            return false;
        }

        // Prepare Response
        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    protected function isFinished()
    {
        return
            !$this->isRunning() ||
            $this->options->currentStep > $this->options->totalSteps ||
            !method_exists($this, "step" . $this->options->currentStep);
    }

    /**
     * Copy wp-config.php from the staging site if it is located outside of root one level up or
     * copy default wp-config.php if production site uses bedrock or any other boilerplate solution that stores wp default config data elsewhere.
     * @return boolean
     */
    protected function step0()
    {
        return (new CopyWpConfig($this->getCloningDto(0)))->execute();
    }

    /**
     * Replace "siteurl" and "home"
     * @return bool
     */
    protected function step1()
    {
        return (new UpdateSiteUrlAndHome($this->getCloningDto(1)))->execute();
    }

    /**
     * Update various options
     * @return bool
     */
    protected function step2()
    {
        return (new UpdateStagingOptionsTable($this->getCloningDto(2)))->execute();
    }

    /**
     * Update Table Prefix in wp_usermeta
     * @return bool
     */
    protected function step3()
    {
        if ($this->isMultisiteAndPro()) {
            return (new MultisiteUpdateTablePrefix($this->getCloningDto(3)))->execute();
        }

        return (new UpdateTablePrefix($this->getCloningDto(3)))->execute();
    }

    /**
     * Update Table prefix in wp-config.php
     * @return bool
     */
    protected function step4()
    {
        return (new UpdateWpConfigTablePrefix($this->getCloningDto(4)))->execute();
    }

    /**
     * Reset index.php to WordPress default
     * This is needed if live site is located in subfolder
     * Check first if main wordpress is used in subfolder and index.php in parent directory
     * @see: https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
     * @return bool
     */
    protected function step5()
    {
        return (new ResetIndexPhp($this->getCloningDto(5)))->execute();
    }

    /**
     * Update Table Prefix in wp_options
     * @return bool
     */
    protected function step6()
    {
        return (new UpdateWpOptionsTablePrefix($this->getCloningDto(6)))->execute();
    }

    /**
     * Add UPLOADS, WP_PLUGIN_DIR, WP_LANG_DIR, and WP_TEMP_DIR constants in wp-config.php or change them to correct destination.
     * This is important when custom folders are used
     * @return bool
     */
    protected function step7()
    {
        return (new UpdateWpConfigConstants($this->getCloningDto(7)))->execute();
    }

    /**
     * Get active_sitewide_plugins from wp_sitemeta and active_plugins from subsite
     * Merge both arrays and copy them to the staging site into active_plugins
     */
    protected function step8()
    {
        if ($this->isMultisiteAndPro()) {
            return (new MultisiteUpdateActivePlugins($this->getCloningDto(8)))->execute();
        }

        return true;
    }

    /**
     * Check if there is a multisite super administrator.
     * If not add it to _usermeta
     * @return bool
     */
    protected function step9()
    {
        if ($this->isMultisiteAndPro()) {
            return (new MultisiteAddNetworkAdministrators($this->getCloningDto(9)))->execute();
        }

        return true;
    }


    /**
     * Check if WP is installed in subdir
     * @return boolean
     */
    protected function isSubDir()
    {
        return (new SiteInfo())->isInstalledInSubDir();
    }

    /**
     * Get the install sub directory if WP is installed in sub directory
     * @return string
     */
    protected function getInstallSubDir()
    {
        $home = get_option('home');
        $siteurl = get_option('siteurl');

        if (empty($home) || empty($siteurl)) {
            return '';
        }

        return str_replace([$home, '/'], '', $siteurl);
    }

    /**
     * Return URL of staging site
     * @return string
     */
    protected function getStagingSiteUrl()
    {
        if (!empty($this->options->cloneHostname)) {
            return $this->options->cloneHostname;
        }
        if ($this->isMultisiteAndPro()) {
            if ($this->isSubDir()) {
                return trailingslashit($this->baseUrl) . trailingslashit($this->getInstallSubDir()) . $this->options->cloneDirectoryName;
            }

            // Get the path to the main multisite without a trailingslash e.g. wordpress
            $multisitePath = defined('PATH_CURRENT_SITE') ? PATH_CURRENT_SITE : '/';
            return rtrim($this->baseUrl, '/\\') . $multisitePath . $this->options->cloneDirectoryName;
        }

        if ($this->isSubDir()) {
            return trailingslashit($this->homeUrl) . trailingslashit($this->getInstallSubDir()) . $this->options->cloneDirectoryName;
        }

        return trailingslashit($this->homeUrl) . $this->options->cloneDirectoryName;
    }

    /**
     * @return string|string[]
     * @todo delete
     */
/*    protected function getUploadFolder()
    {
        if ($this->isMultisiteAndPro()) {
            // Get absolute path to uploads folder
            $uploads = wp_upload_dir();
            $basedir = $uploads['basedir'];
            // Get relative upload path
            return str_replace(wpstg_replace_windows_directory_separator(ABSPATH), null, wpstg_replace_windows_directory_separator($basedir));
        }

        return trim((new WpDefaultDirectories())->getRelativeUploadDir(), '/');
    }*/
}
