<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\CloningProcess\Data\CopyWpConfig;
use WPStaging\Framework\CloningProcess\Data\Job as DataJob;
use WPStaging\Framework\CloningProcess\Data\MultisiteAddNetworkAdministrators;
use WPStaging\Framework\CloningProcess\Data\MultisiteUpdateActivePlugins;
use WPStaging\Framework\CloningProcess\Data\ResetIndexPhp;
use WPStaging\Framework\CloningProcess\Data\UpdateSiteUrlAndHome;
use WPStaging\Framework\CloningProcess\Data\UpdateTablePrefix;
use WPStaging\Framework\CloningProcess\Data\UpdateWpConfigConstants;
use WPStaging\Framework\CloningProcess\Data\UpdateWpConfigTablePrefix;
use WPStaging\Framework\CloningProcess\Data\UpdateWpOptionsTablePrefix;
use WPStaging\Framework\CloningProcess\Data\UpdateStagingOptionsTable;
use WPStaging\Framework\Utils\Strings;

/**
 * Class Data
 * @package WPStaging\Backend\Modules\Jobs
 */
class Data extends DataJob
{
    /**
     * Initialize
     */
    public function initialize()
    {
        parent::initialize();
        $this->getTables();
    }

    /**
     * Get a list of tables to copy
     */
    protected function getTables()
    {
        $strings = new Strings();
        $this->tables = [];
        foreach ($this->options->tables as $table) {
            $this->tables[] = $this->options->prefix . $strings->str_replace_first(WPStaging::getTablePrefix(), null, $table);
        }

        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
            // Add extra global tables from main multisite (wpstg[x]_users and wpstg[x]_usermeta)
            $this->tables[] = $this->options->prefix . 'users';
            $this->tables[] = $this->options->prefix . 'usermeta';
        }
    }

    /**
     * Calculate total steps in this job and assign it to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = 7;

        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
            $this->options->totalSteps = 9;
        }
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
        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
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
        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
            return (new MultisiteAddNetworkAdministrators($this->getCloningDto(9)))->execute();
        }

        return true;
    }
}
