<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Backup\BackupScheduler;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Storage\Providers;
use WPStaging\Backup\Task\Tasks\JobBackup\FinishBackupTask;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Utils\Times;

/**
 * @todo move html code to view for renderScheduleList()
 */
class ScheduleList
{
    /** @var Times */
    private $times;

    private $backupScheduler;

    private $isPro;

    /** @var Providers */
    protected $providers;

    public function __construct(BackupScheduler $backupScheduler)
    {
        $this->backupScheduler = $backupScheduler;
        $this->providers = WPStaging::make(Providers::class);
        $this->times = new Times();
        $this->isPro = WPStaging::isPro();
    }

    /**
     * Rendered via AJAX.
     *
     * @throws \Exception
     */
    public function renderScheduleList()
    {
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            return;
        }

        $schedules = $this->backupScheduler->getSchedules();

        if (empty($schedules)) {
            wp_send_json_success('<p class="wpstg-backup-no-schedules-list">' . esc_html__('You don\'t have a backup plan yet. Create a new backup and choose a recurrent backup time to start.', 'wp-staging') . '</p>');
        }

        $scheduleHtml = ob_start();

        foreach ($schedules as $schedule) :
            $hourAndMinute = new \DateTime('now', wp_timezone());
            $hourAndMinute->setTime($schedule['time'][0], $schedule['time'][1]);
            $editMessage = $this->isPro ? __('Edit this backup plan.', 'wp-staging') : __('Please upgrade to WP Staging Pro to edit existing backup plans. You can delete this plan and create a new one if you want to change it.', 'wp-staging');
            ?>
            <li class="wpstg-backup-schedules">
                <div class="wpstg-backup-schedules-header">
                    <span class="wpstg-backup-schedules-title">
                        <?php echo esc_html($schedule['name']); ?>
                    </span>
                    <?php if (!empty($schedule['backupType'])) :?>
                    <div class="wpstg-clone-labels">
                        <span class="wpstg-clone-label"><?php echo esc_html($this->getBackupType($schedule['backupType'])) ?></span>
                    </div>
                    <?php endif;?>
                    <div class="wpstg-clone-actions">
                        <div class="wpstg-dropdown wpstg-action-dropdown">
                            <a href="#" class="wpstg-dropdown-toggler">
                                <?php esc_html_e("Actions", "wp-staging"); ?>
                                <span class="wpstg-caret"></span>
                            </a>
                            <div class="wpstg-dropdown-menu">
                                <a href="#" class="wpstg-clone-action  <?php echo $this->isPro ? "wpstg--edit-schedule" : "wpstg--edit-schedule-basic" ?>"
                                   data-schedule-id="<?php echo $this->isPro ? esc_attr($schedule['scheduleId']) : "" ?>"
                                   title="<?php echo esc_attr($editMessage) ?>">
                                    <?php esc_html_e('Edit', 'wp-staging') ?>
                                </a>
                                <a href="#" class="wpstg-clone-action wpstg--dismiss-schedule"
                                   data-schedule-id="<?php echo esc_attr($schedule['scheduleId']); ?>"
                                   title="<?php esc_attr_e('Delete this schedule and stop creating new backups. This does not delete any backup files.', 'wp-staging'); ?>">
                                    <?php esc_html_e('Delete', 'wp-staging') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="wpstg-backup-schedules-info">
                    <ul>
                        <li>
                            <strong><?php esc_html_e('Schedule:', 'wp-staging') ?></strong>
                            <span class="wpstg--edit-timer-highlight"  data-schedule-id="<?php echo esc_attr($schedule['scheduleId']); ?>">
                                <?php echo esc_html(Cron::getCronDisplayName($schedule['schedule'])); ?>
                                <?php esc_html_e(' at ', 'wp-staging') ?><?php echo $hourAndMinute->format(get_option('time_format')); ?>
                            </span>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Storages:', 'wp-staging')?></strong>
                            <?php foreach ($schedule['storages'] as $storage) :?>
                                <ul class="wpstg-restore-backup-contains wpstg-listing-single-backup">
                                    <?php
                                    $providerName     = '';
                                    $maxBackupsToKeep = '';
                                    $authClass        = '';
                                    $isActivated      = false;

                                    if ($storage === 'localStorage') {
                                        $providerName     = __('Local Storage', 'wp-staging');
                                        $isActivated      = true;
                                        $maxBackupsToKeep = (int)$schedule['rotation'];
                                    } else {
                                        $authClass        = $this->providers->getStorageProperty($storage, 'authClass', true);
                                    }

                                    if ($authClass && class_exists($authClass)) {
                                        $providerName     = $this->providers->getStorageProperty($storage, 'name', true);
                                        $provider         = WPStaging::make($authClass);
                                        $providerOptions  = $provider->getOptions();
                                        $isActivated      = $provider->isAuthenticated();
                                        $maxBackupsToKeep = empty($providerOptions['maxBackupsToKeep']) ? '' : $providerOptions['maxBackupsToKeep'];
                                    }

                                    ?>
                                    <?php if (!empty($providerName) && $isActivated) :?>
                                    <li class="wpstg-clone-labels">
                                        <span class="wpstg-clone-label">
                                            <?php
                                            echo esc_html($providerName);
                                            if (!empty($maxBackupsToKeep)) :?>
                                                <span class="wpstg-backup-retentions wpstg--tooltip">
                                                    <?php echo esc_html($maxBackupsToKeep);?>
                                                     <span class="wpstg--tooltiptext">
                                                        <?php
                                                        echo sprintf(
                                                            esc_html__('%s A maximum of %s backup%s will be kept for this storage.', 'wp-staging'),
                                                            '<strong>' .  esc_html__('Retention:', 'wp-staging') . '</strong>',
                                                            esc_html($maxBackupsToKeep),
                                                            (int)$maxBackupsToKeep > 1 ? 's' : ''
                                                        ); ?>
                                                    </span>
                                                </span>
                                            <?php endif;?>
                                        </span>
                                    </li>
                                    <?php endif;?>
                                </ul>
                            <?php endforeach; ?>
                        </li>
                        <li class="single-backup-includes">
                            <strong><?php esc_html_e('Contains: ', 'wp-staging') ?></strong>
                            <?php
                            $isExportingDatabase            = $schedule['isExportingDatabase'];
                            $isExportingPlugins             = $schedule['isExportingPlugins'];
                            $isExportingMuPlugins           = $schedule['isExportingMuPlugins'];
                            $isExportingThemes              = $schedule['isExportingThemes'];
                            $isExportingUploads             = $schedule['isExportingUploads'];
                            $isExportingOtherWpContentFiles = $schedule['isExportingOtherWpContentFiles'];
                            $isExportingOtherWpRootFiles    = $schedule['isExportingOtherWpRootFiles'] ?? false;
                            include(WPSTG_VIEWS_DIR . 'backup/modal/partials/backup-contains.php');
                            ?>
                        </li>
                    </ul>
                </div>
            </li>
        <?php endforeach;

        wp_send_json_success(ob_get_clean());
    }

    /**
     * @return string|void
     * @throws \Exception
     */
    public function renderNextBackupSnippet()
    {
        if (!current_user_can((new Capabilities())->manageWPSTG())) {
            return '';
        }

        ?>
        <ul>
            <li>
                <?php

                echo sprintf(
                    '<strong>%s: </strong>%s',
                    esc_html__('Current Time', 'wp-staging'),
                    esc_html($this->times->getCurrentTime())
                ); ?>
            </li>
            <?php
            WPStaging::silenceLogs();
            $lastRun = get_option(FinishBackupTask::OPTION_LAST_BACKUP);
            WPStaging::silenceLogs(false);

            if (is_array($lastRun)) :
                $lastRunTime = $this->times->getHumanTimeDiff($lastRun['endTime'], time());
                $lastRunDuration = str_replace(['minutes', 'seconds'], ['min', 'sec'], $this->times->getHumanReadableDuration(gmdate('i:s', $lastRun['duration'])));
                ?>
            <li>
                <?php echo sprintf(
                    '<strong>%s:</strong> %s %s (%s %s)',
                    esc_html__('Last Backup', 'wp-staging'),
                    esc_html($lastRunTime),
                    esc_html__('ago', 'wp-staging'),
                    esc_html__('Duration', 'wp-staging'),
                    esc_html($lastRunDuration)
                ); ?>
            </li>
                <?php
            endif;

            try {
                list($nextBackupTimestamp, $nextBackupCallback) = $this->backupScheduler->getNextBackupSchedule();
            } catch (\Exception $e) {
                $nextBackupTimestamp = null;
                $nextBackupCallback = null;
            }

            if (!is_null($nextBackupTimestamp)) :
                $nextBackupTimeHumanReadable = $this->times->getHumanTimeDiff(time(), $nextBackupTimestamp);
                ?>
            <li>
                <?php echo sprintf(
                    '<strong>%s:</strong> %s %s',
                    esc_html__('Next backup', 'wp-staging'),
                    esc_html__('start in', 'wp-staging'),
                    esc_html($nextBackupTimeHumanReadable)
                ); ?>
            </li>
        </ul>
                    <?php
            endif;
    }

    /**
     * @param string $backupType
     * @return string
     */
    public function getBackupType(string $backupType = ''): string
    {
        if ($backupType === BackupMetadata::BACKUP_TYPE_SINGLE) {
            return esc_html__('Single Site', 'wp-staging');
        }

        if ($backupType === BackupMetadata::BACKUP_TYPE_MULTISITE) {
            return esc_html__('Entire Network', 'wp-staging');
        }

        if ($backupType === BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE) {
            return esc_html__('Network Subsite', 'wp-staging');
        }

        if ($backupType === BackupMetadata::BACKUP_TYPE_MAIN_SITE) {
            return esc_html__('Main Network Site', 'wp-staging');
        }

        return esc_html__('Unknown Backup Type', 'wp-staging');
    }
}
