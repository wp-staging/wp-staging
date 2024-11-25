<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use Exception;
use WPStaging\Backup\Task\RestoreTask;

use function WPStaging\functions\debug_log;

class UpdateBackupsScheduleTask extends RestoreTask
{
    /** @var object */
    protected $wpdb;

    public static function getTaskName()
    {
        return 'backup_restore_update_backup_schedules';
    }

    public static function getTaskTitle()
    {
        return 'Update Backup Schedules';
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        global $wpdb;
        $this->wpdb = $wpdb;

        $this->stepsDto->setTotal(1);

        if ($this->jobDataDto->getIsMissingDatabaseFile()) {
            $this->logger->warning('Skipped preserved backup schedules in the database. Database file missing!');
            return $this->generateResponse();
        }

        $tmpOptionsTable  = $this->jobDataDto->getTmpDatabasePrefix() . 'options';
        if (!$this->wpdb->get_var("SHOW TABLES LIKE '{$tmpOptionsTable}'")) {
            $this->logger->warning('Skipped preserved backup schedules in the database. No option table in the backup!');
            return $this->generateResponse();
        }

        $this->updateWpStagingCronJobs($tmpOptionsTable);

        $this->logger->info('Preserved backup schedules in the database.');

        return $this->generateResponse();
    }

    /**
     * @throws Exception
     */
    protected function updateWpStagingCronJobs(string $tmpOptionsTable)
    {
        $prodOptionsTable = $this->wpdb->prefix . 'options';

        // Cron jobs contained in the production site
        $productionCronJobs = $this->wpdb->get_col("SELECT option_value FROM {$prodOptionsTable} WHERE option_name = 'cron';");
        $productionCronJobs = maybe_unserialize($productionCronJobs[0]);

        // Cron jobs contained in the backup file
        $backupCronJobs = $this->wpdb->get_col("SELECT option_value FROM {$tmpOptionsTable} WHERE option_name = 'cron';");

        if (isset($backupCronJobs[0])) {
            $backupCronJobs = maybe_unserialize($backupCronJobs[0]);
        }

        // WP STAGING Cron jobs from production site
        $wpstgCronJobs = $this->extractWpStagingCrons($productionCronJobs);

        // Clean all WP STAGING cron jobs from the backup file
        $backupCronJobs = $this->removeWpStagingCronJobs($backupCronJobs);

        // Add all WP STAGING cron jobs from production site to cron jobs of backup file
        $backupCronJobs = $this->addWpStagingCronJobs($backupCronJobs, $wpstgCronJobs);
        $backupCronJobs = serialize($backupCronJobs);

        $query = "UPDATE {$tmpOptionsTable} SET option_value = '{$backupCronJobs}' WHERE option_name = 'cron';";

        $result = $this->wpdb->query($query);

        if (!$result) {
            debug_log('Failed to Update WP STAGING Cron Jobs! Error: ' . $this->wpdb->last_error . ' Query: ' . $query);
        }
    }

    /**
     * @param array $cronJobs
     * @return array
     */
    protected function extractWpStagingCrons($cronJobs)
    {
        // Bail: Unexpected value - should never happen.
        if (!is_array($cronJobs)) {
            debug_log('Can not extract WP STAGING cron jobs. Is no array: ' . $cronJobs);
            return [];
        }

        ksort($cronJobs, SORT_NUMERIC);

        $wpstgCronJobs = [];

        // Extract backup schedules from Cron
        foreach ($cronJobs as $timestamp => &$events) {
            if (is_array($events)) {
                foreach ($events as $callback => &$args) {
                    if ($callback === 'wpstg_create_cron_backup') {
                        if (!isset($wpstgCronJobs[$timestamp])) {
                            $wpstgCronJobs[$timestamp] = [];
                        }

                        $wpstgCronJobs[$timestamp][$callback] = $args;
                    }
                }
            }
        }

        return $wpstgCronJobs;
    }

    /**
     * @param array $cronJobs
     * @return array
     */
    protected function removeWpStagingCronJobs($cronJobs)
    {
        // Bail: Unexpected value - should never happen.
        if (!is_array($cronJobs)) {
            debug_log('Can not remove WP STAGING cron jobs. Is no array: ' . $cronJobs);
            return [];
        }

        ksort($cronJobs, SORT_NUMERIC);

        // Remove any WP STAGING backup schedules from Cron
        foreach ($cronJobs as $timestamp => &$events) {
            if (is_array($events)) {
                foreach ($events as $callback => &$args) {
                    if ($callback === 'wpstg_create_cron_backup') {
                        unset($events[$callback]);
                    }
                }
            }

            if (is_array($events) && empty($events)) {
                unset($cronJobs[$timestamp]);
            }
        }

        return $cronJobs;
    }

    /**
     * @param array $cronJobs
     * @param array $wpstgCronJobs
     * @return array
     */
    protected function addWpStagingCronJobs($cronJobs, $wpstgCronJobs)
    {
        // Bail: Unexpected value - should never happen.
        if (!is_array($cronJobs)) {
            return [];
        }

        foreach ($wpstgCronJobs as $timestamp => $events) {
            foreach ($events as $callback => &$args) {
                $cronJobs[$timestamp][$callback] = $args;
            }
        }

        return $cronJobs;
    }
}
