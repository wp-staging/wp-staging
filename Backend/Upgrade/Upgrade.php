<?php

/**
 * Upgrade Class
 * This must be loaded on every page init to ensure all settings are
 * adjusted correctly and to run any upgrade process if necessary.
 */

namespace WPStaging\Backend\Upgrade;

use WPStaging\Core\Utils\IISWebConfig;
use WPStaging\Core\Utils\Htaccess;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Notifications\Notifications;
use WPStaging\Staging\Sites;
use WPStaging\Backup\BackupScheduler;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

class Upgrade
{
    /**
     * @var string
     */
    const OPTION_UPGRADE_DATE = 'wpstg_free_upgrade_date';

    /**
     * @var string
     */
    const OPTION_INSTALL_DATE = 'wpstg_free_install_date';

    /**
     * Previous Version number
     * @var string
     */
    private $previousVersion;

    /**
     * Global settings
     * @var object
     */
    private $settings;

    /**
     * db object
     * @var object
     */
    private $db;

    /**
     * @var Sites
     */
    private $stagingSitesHelper;

    public function __construct()
    {
        // Previous version
        $this->previousVersion = preg_replace('/[^0-9.].*/', '', get_option('wpstg_version'));

        $this->settings = (object) get_option("wpstg_settings", []);

        // db
        $this->db = WPStaging::getInstance()->get("wpdb");

        /** @var Sites */
        $this->stagingSitesHelper = WPStaging::make(Sites::class);
    }

    /**
     * @return void
     */
    public function doUpgrade()
    {
        $this->upgrade2_0_3();
        $this->upgrade2_1_2();
        $this->upgrade2_2_0();
        $this->upgrade2_4_4();
        $this->upgrade2_5_9();
        $this->upgrade2_8_7();
        $this->upgrade3_0_7();
        $this->upgrade3_8_1();

        $this->setVersion();
    }

    /**
     * Enable backup notification by default
     * @return void
     */
    private function upgrade3_8_1() // phpcs:ignore
    {
        // Early bail: Previous version is greater than 3.8.1
        if (version_compare($this->previousVersion, '3.8.1', '>')) {
            return;
        }

        if (!class_exists('WPStaging\Backup\BackupScheduler')) {
            return;
        }

        $optionBackupScheduleReportEmail = get_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL);
        if (empty($optionBackupScheduleReportEmail) || !filter_var($optionBackupScheduleReportEmail, FILTER_VALIDATE_EMAIL)) {
            $wpstgSettings = (array)get_option('wpstg_settings');

            if (!empty($wpstgSettings['schedulesReportEmail'])) {
                $optionBackupScheduleReportEmail = $wpstgSettings['schedulesReportEmail'];
            } else {
                $userObject = wp_get_current_user();
                if (is_object($userObject) && !empty($userObject->user_email)) {
                    $optionBackupScheduleReportEmail = $userObject->user_email;
                }
            }

            update_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL, $optionBackupScheduleReportEmail);
        }

        // boolean false: Default value if the option does not exist/created yet
        if (get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT) === false && !empty($optionBackupScheduleReportEmail)) {
            update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT, 'true');
        }
    }

    /**
     * Add response field to queue table if not exist
     * @return void
     */
    private function upgrade3_0_7() // phpcs:ignore
    {
        // Early bail: Previous version is greater than 3.0.6
        if (version_compare($this->previousVersion, '3.0.6', '>')) {
            return;
        }

        $queueUtil = WPStaging::make(Queue::class);
        $queueUtil->maybeAddResponseColumnToTable();
    }

    /**
     * Move existing staging sites to new option defined in Sites::STAGING_SITES_OPTION
     * @return void
     */
    private function upgrade2_8_7() // phpcs:ignore
    {
        $this->stagingSitesHelper->addMissingCloneNameUpgradeStructure();
        $this->stagingSitesHelper->upgradeStagingSitesOption();
    }

    /**
     * Fix array keys of staging sites
     * @return void
     */
    private function upgrade2_5_9() // phpcs:ignore
    {
        // Previous version lower than 2.5.9
        if (version_compare($this->previousVersion, '2.5.9', '<')) {
            // Current options
            $sites = $this->stagingSitesHelper->tryGettingStagingSites();

            $new = [];

            // Fix keys. Replace white spaces with dash character
            foreach ($sites as $oldKey => $site) {
                $key       = preg_replace("#\W+#", '-', strtolower($oldKey));
                $new[$key] = $sites[$oldKey];
            }

            if (!empty($new)) {
                $this->stagingSitesHelper->updateStagingSites($new);
            }
        }
    }

    /**
     * @return void
     */
    private function upgrade2_4_4() // phpcs:ignore
    {
        // Previous version lower than 2.4.4
        if (version_compare($this->previousVersion, '2.4.4', '<')) {
            // Add htaccess to wp staging uploads folder
            $htaccess = new Htaccess();
            $htaccess->create(trailingslashit(WPStaging::getContentDir()) . '.htaccess');
            $htaccess->create(trailingslashit(WPStaging::getContentDir()) . 'logs/.htaccess');

            // Add litespeed htaccess to wp root folder
            if (extension_loaded('litespeed')) {
                $htaccess->createLitespeed(ABSPATH . '.htaccess');
            }

            // create web.config file for IIS in wp staging uploads folder
            $webconfig = new IISWebConfig();
            $webconfig->create(trailingslashit(WPStaging::getContentDir()) . 'web.config');
            $webconfig->create(trailingslashit(WPStaging::getContentDir()) . 'logs/web.config');
        }
    }

    /**
     * Upgrade method 2.2.0
     * @return void
     */
    public function upgrade2_2_0() // phpcs:ignore
    {
        // Previous version lower than 2.2.0
        if (version_compare($this->previousVersion, '2.2.0', '<')) {
            $this->upgradeElements();
        }
    }

    /**
     * Add missing elements
     * @return void
     */
    private function upgradeElements()
    {
        // Current options
        $sites = $this->stagingSitesHelper->tryGettingStagingSites();

        if ($sites === false || count($sites) === 0) {
            return;
        }

        // Check if key prefix is missing and add it
        foreach ($sites as $key => $value) {
            if (empty($sites[$key]['directoryName'])) {
                continue;
            }

            !empty($sites[$key]['prefix']) ?
                            $sites[$key]['prefix'] = $value['prefix'] :
                            $sites[$key]['prefix'] = $this->getStagingPrefix($sites[$key]['directoryName']);
        }

        if (count($sites) > 0) {
            $this->stagingSitesHelper->updateStagingSites($sites);
        }
    }

    /**
     * Check and return prefix of the staging site
     * @param string $directory
     * @return string
     */
    private function getStagingPrefix($directory)
    {
        // Try to get staging prefix from wp-config.php of staging site
        $path = ABSPATH . $directory . "/wp-config.php";

        if (($content = @file_get_contents($path)) === false) {
            $prefix = "";
        } else {
            // Get prefix from wp-config.php
            preg_match("/table_prefix\s*=\s*'(\w*)';/", $content, $matches);

            if (!empty($matches[1])) {
                $prefix = $matches[1];
            } else {
                $prefix = "";
            }
        }

        // return result: Check if staging prefix is the same as the live prefix
        if ($this->db->prefix != $prefix) {
            return $prefix;
        } else {
            return "";
        }
    }

    /**
     * Upgrade method 2.0.3
     * @return void
     */
    public function upgrade2_0_3() // phpcs:ignore
    {
        // Previous version lower than 2.0.2
        if (version_compare($this->previousVersion, '2.0.2', '<')) {
            $this->initialInstall();
            $this->upgradeNotices();
        }
    }

    /**
     * Upgrade method 2.1.2
     * Sanitize the clone key value.
     * @return void
     */
    private function upgrade2_1_2() // phpcs:ignore
    {
        if ($this->previousVersion === false || version_compare($this->previousVersion, '2.1.2', '<')) {
            // Current options
            $clones = $this->stagingSitesHelper->tryGettingStagingSites();

            foreach ($clones as $key => $value) {
                unset($clones[$key]);
                $clones[preg_replace("#\W+#", '-', strtolower($key))] = $value;
            }

            if (!empty($clones)) {
                $this->stagingSitesHelper->updateStagingSites($clones);
            }
        }
    }

    /**
     * Upgrade routine for new install
     * @return void
     */
    private function initialInstall()
    {
        // Write some default vars
        add_option('wpstg_installDate', date('Y-m-d h:i:s')); // Common install date for free or pro version - deprecated. Remove 2023
        add_option(self::OPTION_INSTALL_DATE, date('Y-m-d h:i:s'));
        $this->settings->optimizer = "1";
        update_option('wpstg_settings', $this->settings);
    }

    /**
     * Write new version number into db
     * @return bool
     */
    private function setVersion()
    {
        // Check if version number in DB is lower than version number in current plugin
        if (version_compare($this->previousVersion, WPStaging::getVersion(), '<')) {
            // Update Version number
            update_option('wpstg_version', preg_replace('/[^0-9.].*/', '', WPStaging::getVersion()));
            // Update "upgraded from" version number
            update_option('wpstg_version_upgraded_from', preg_replace('/[^0-9.].*/', '', $this->previousVersion));
            // Update the time version upgraded at
            update_option(self::OPTION_UPGRADE_DATE, date('Y-m-d H:i'));

            return true;
        }

        return false;
    }

    /**
     * Upgrade Notices db options from wpstg 1.3 -> 2.0.1
     * Fix some logical db options
     * @return void
     */
    private function upgradeNotices()
    {
        $poll   = get_option("wpstg_start_poll", false);
        $beta   = get_option("wpstg_hide_beta", false);
        $rating = get_option("wpstg_RatingDiv", false);

        if ($beta && $beta === "yes") {
            update_option('wpstg_beta', 'no');
        }

        if ($rating && $rating === 'yes') {
            update_option('wpstg_rating', 'no');
        }
    }
}
