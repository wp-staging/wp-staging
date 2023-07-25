<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\CloningProcess\Data\CleanThirdPartyConfigs;
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

    protected function initializeSteps()
    {
        $this->steps = [
            CopyWpConfig::class, // Copy wp-config.php from the staging site if it is located outside of root one level up or copy default wp-config.php if production site uses bedrock or any other boilerplate solution that stores wp default config data elsewhere.
            UpdateSiteUrlAndHome::class,
            UpdateStagingOptionsTable::class,
            UpdateTablePrefix::class,
            UpdateWpConfigTablePrefix::class,
            ResetIndexPhp::class, // This is needed if live site is located in subfolder. @see: https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory
            UpdateWpOptionsTablePrefix::class, // This is important when custom folders are used
            UpdateWpConfigConstants::class,
            CleanThirdPartyConfigs::class, // Remove or use dummy config files for hosting like Flywheel etc
        ];

        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
            $this->steps[] = MultisiteUpdateActivePlugins::class; // Merge sitewide active plugins and subsite active plugins
            $this->steps[] = MultisiteAddNetworkAdministrators::class; // Add network administrators to _usermeta
        }
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
        $this->options->totalSteps = 8;

        if ($this->isMultisiteAndPro() && !$this->isNetworkClone()) {
            $this->options->totalSteps = 10;
        }
    }
}
