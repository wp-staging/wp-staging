<?php







namespace WPStaging\Backend\Upgrade;

use WPStaging\Core\Utils\IISWebConfig;
use WPStaging\Core\Utils\Htaccess;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Notifications\Notifications;
use WPStaging\Staging\Sites;
use WPStaging\Staging\Service\StagingEngine;
use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Storage\Providers;
use WPStaging\Framework\Notices\NextGenEngineNotice;
use WPStaging\Framework\Upgrade\UpgradeFlags;

 
if (!defined("WPINC")) {
    die;
}

class Upgrade
{



    const OPTION_UPGRADE_DATE = 'wpstg_free_upgrade_date';




    const OPTION_INSTALL_DATE = 'wpstg_free_install_date';





    private $previousVersion;





    private $settings;





    private $db;




    private $stagingSitesHelper;




    private $upgradeFlags;

    public function __construct()
    {
 
        $this->previousVersion = preg_replace('/[^0-9.].*/', '', get_option('wpstg_version'));

        $this->settings = (object) get_option("wpstg_settings", []);

 
        $this->db = WPStaging::getInstance()->get("wpdb");

 
        $this->stagingSitesHelper = WPStaging::make(Sites::class);
        $this->upgradeFlags       = WPStaging::make(UpgradeFlags::class);
    }




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
        $this->migrateRemoteStorageOptionNames();
        $this->revertNextGenStagingEngine();

        $this->setVersion();
    }










    private function revertNextGenStagingEngine()
    {
        if ($this->upgradeFlags->has('next_gen_engine_disabled')) {
            return;
        }

        $stagingEngine = WPStaging::make(StagingEngine::class);
        if ($stagingEngine->getStoredEngine() === StagingEngine::ENGINE_NEXT_GEN) {
            $stagingEngine->saveEngine(StagingEngine::ENGINE_LEGACY);

            $hasStagingSites = !empty(get_option(Sites::STAGING_SITES_OPTION, []))
                || !empty(get_option(Sites::OLD_STAGING_SITES_OPTION, []));
            if ($hasStagingSites) {
                WPStaging::make(NextGenEngineNotice::class)->enable();
            }
        }

        $this->upgradeFlags->mark('next_gen_engine_disabled');
    }





    private function upgrade3_8_1() // phpcs:ignore
    {
 
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

 
        if (get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT) === false && !empty($optionBackupScheduleReportEmail)) {
            update_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT, 'true');
        }
    }










    private function migrateRemoteStorageOptionNames()
    {
        if ($this->upgradeFlags->has('remote_storage_option_names_migrated')) {
            return;
        }

        (new Providers())->migrateRemoteStorageOptions();
        $this->upgradeFlags->mark('remote_storage_option_names_migrated');
    }





    private function upgrade3_0_7() // phpcs:ignore
    {
 
        if (version_compare($this->previousVersion, '3.0.6', '>')) {
            return;
        }

        $queueUtil = WPStaging::make(Queue::class);
        $queueUtil->maybeAddResponseColumnToTable();
    }





    private function upgrade2_8_7() // phpcs:ignore
    {
        $this->stagingSitesHelper->addMissingCloneNameUpgradeStructure();
        $this->stagingSitesHelper->upgradeStagingSitesOption();
    }





    private function upgrade2_5_9() // phpcs:ignore
    {
 
        if (version_compare($this->previousVersion, '2.5.9', '<')) {
 
            $sites = $this->stagingSitesHelper->tryGettingStagingSites();

            $new = [];

 
            foreach ($sites as $oldKey => $site) {
                $key       = preg_replace("#\W+#", '-', strtolower($oldKey));
                $new[$key] = $sites[$oldKey];
            }

            if (!empty($new)) {
                $this->stagingSitesHelper->updateStagingSites($new);
            }
        }
    }




    private function upgrade2_4_4() // phpcs:ignore
    {
 
        if (version_compare($this->previousVersion, '2.4.4', '<')) {
 
            $htaccess = new Htaccess();
            $htaccess->create(trailingslashit(WPStaging::getContentDir()) . '.htaccess');
            $htaccess->create(trailingslashit(WPStaging::getContentDir()) . 'logs/.htaccess');

 
            if (extension_loaded('litespeed')) {
                $htaccess->createLitespeed(ABSPATH . '.htaccess');
            }

 
            $webconfig = new IISWebConfig();
            $webconfig->create(trailingslashit(WPStaging::getContentDir()) . 'web.config');
            $webconfig->create(trailingslashit(WPStaging::getContentDir()) . 'logs/web.config');
        }
    }





    public function upgrade2_2_0() // phpcs:ignore
    {
 
        if (version_compare($this->previousVersion, '2.2.0', '<')) {
            $this->upgradeElements();
        }
    }





    private function upgradeElements()
    {
 
        $sites = $this->stagingSitesHelper->tryGettingStagingSites();

        if ($sites === false || count($sites) === 0) {
            return;
        }

 
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






    private function getStagingPrefix($directory)
    {
 
        $path = ABSPATH . $directory . "/wp-config.php";

        if (($content = @file_get_contents($path)) === false) {
            $prefix = "";
        } else {
 
            preg_match("/table_prefix\s*=\s*'(\w*)';/", $content, $matches);

            if (!empty($matches[1])) {
                $prefix = $matches[1];
            } else {
                $prefix = "";
            }
        }

 
        if ($this->db->prefix != $prefix) {
            return $prefix;
        } else {
            return "";
        }
    }





    public function upgrade2_0_3() // phpcs:ignore
    {
 
        if (version_compare($this->previousVersion, '2.0.2', '<')) {
            $this->initialInstall();
            $this->upgradeNotices();
        }
    }






    private function upgrade2_1_2() // phpcs:ignore
    {
        if ($this->previousVersion === false || version_compare($this->previousVersion, '2.1.2', '<')) {
 
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





    private function initialInstall()
    {
 
        add_option('wpstg_installDate', date('Y-m-d h:i:s')); 
        add_option(self::OPTION_INSTALL_DATE, date('Y-m-d h:i:s'));
        $this->settings->optimizer = "1";
        update_option('wpstg_settings', $this->settings);
    }





    private function setVersion()
    {
 
        if (version_compare($this->previousVersion, WPStaging::getVersion(), '<')) {
 
            update_option('wpstg_version', preg_replace('/[^0-9.].*/', '', WPStaging::getVersion()));
 
            update_option('wpstg_version_upgraded_from', preg_replace('/[^0-9.].*/', '', $this->previousVersion));
 
            update_option(self::OPTION_UPGRADE_DATE, date('Y-m-d H:i'));

            return true;
        }

        return false;
    }






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
