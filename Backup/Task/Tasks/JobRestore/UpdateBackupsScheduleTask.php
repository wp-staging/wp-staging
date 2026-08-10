<?php

namespace WPStaging\Backup\Task\Tasks\JobRestore;

use Exception;
use WPStaging\Backup\Task\RestoreTask;
use WPStaging\Core\Cron\Cron;
use WPStaging\Framework\Traits\SerializeTrait;

use function WPStaging\functions\debug_log;

class UpdateBackupsScheduleTask extends RestoreTask
{
    use SerializeTrait;

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

        $tmpOptionsTable = $this->jobDataDto->getTmpDatabasePrefix() . 'options';
        if (!$this->wpdb->get_var("SHOW TABLES LIKE '{$tmpOptionsTable}'")) {
            $this->logger->warning('Skipped preserved backup schedules in the database. No option table in the backup!');
            return $this->generateResponse();
        }

        if ($this->updateWpStagingCronJobs($tmpOptionsTable)) {
            $this->logger->info('Preserved backup schedules in the database.');
        }

        return $this->generateResponse();
    }

    /**
     * @param string $tmpOptionsTable
     * @return bool
     * @throws Exception
     */
    protected function updateWpStagingCronJobs(string $tmpOptionsTable): bool
    {
        $prodOptionsTable = $this->wpdb->prefix . 'options';

        // Cron jobs contained in the production site
        $productionCronJobs = $this->wpdb->get_col("SELECT option_value FROM {$prodOptionsTable} WHERE option_name = 'cron';");
        $rejected           = false;
        $productionCronJobs = isset($productionCronJobs[0]) ? $this->safeMaybeUnserialize($productionCronJobs[0], [], $rejected) : [];

        if ($rejected) {
            $this->logger->warning('Skipped preserved backup schedules in the database. The cron option of this site could not be unserialized safely.');
            return false;
        }

        // Cron jobs contained in the backup file
        $backupCronJobs = $this->wpdb->get_col("SELECT option_value FROM {$tmpOptionsTable} WHERE option_name = 'cron';");
        $rejected       = false;
        $backupCronJobs = isset($backupCronJobs[0]) ? $this->safeMaybeUnserialize($backupCronJobs[0], [], $rejected) : [];

        if ($rejected) {
            $this->logger->warning('Skipped preserved backup schedules in the database. The cron option in the backup could not be unserialized safely.');
            return false;
        }

        // WP STAGING Cron jobs from production site
        $wpstgCronJobs = $this->extractWpStagingCrons($productionCronJobs);

        // Clean all WP STAGING cron jobs from the backup file
        $backupCronJobs = $this->removeWpStagingCronJobs($backupCronJobs);

        // Add all WP STAGING cron jobs from production site to cron jobs of backup file
        $backupCronJobs = $this->addWpStagingCronJobs($backupCronJobs, $wpstgCronJobs);
        $backupCronJobs = serialize($backupCronJobs);

        $query = $this->wpdb->prepare(
            "UPDATE `{$tmpOptionsTable}` SET option_value = %s WHERE option_name = 'cron'",
            $backupCronJobs
        );

        $result = $this->wpdb->query($query);

        if ($result === false) {
            debug_log('Failed to Update WP STAGING Cron Jobs! Error: ' . $this->wpdb->last_error . ' Query: ' . $query);
            return false;
        }

        return true;
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
                    if ($callback === Cron::ACTION_CREATE_CRON_BACKUP) {
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
                    if ($callback === Cron::ACTION_CREATE_CRON_BACKUP) {
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
