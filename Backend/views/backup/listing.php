<?php

use WPStaging\Core\WPStaging;
use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Pro\Backup\BackupProcessLock;
use WPStaging\Pro\Backup\Exceptions\ProcessLockedException;

/**
 * @see \WPStaging\Pro\Backup\Ajax\Listing::render
 *
 * @var TemplateEngine              $this
 * @var array                       $directories
 * @var string                      $urlAssets
 * @var Directory                   $directory
 * @var string                      $isValidLicense
 */

$disabledProperty = $isValidLicense ? '' : 'disabled';


$backupProcessLock = WPStaging::make(BackupProcessLock::class);
try {
    $backupProcessLock->checkProcessLocked();
    $isLocked = false;
    $disabledPropertyCreateBackup = '';
} catch (ProcessLockedException $e) {
    $isLocked = true;
    $disabledPropertyCreateBackup = 'disabled';
}
?>

<?php if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) : ?>
    <div class="notice notice-warning" style="margin-bottom: 10px;">
        <p><strong><?php esc_html_e('WP STAGING:', 'wp-staging') ?></strong></p>
        <p><?php echo sprintf(__('The backup background creation depends on WP CRON but %s is set to %s. So backup background processing will not work. You can remove the constant %s or set its value to %s to make background processing work.', 'wp-staging'), '<code>DISABLE_WP_CRON</code>', '<code>true</code>', '<code>DISABLE_WP_CRON</code>', '<code>false</code>') ?></p>
    </div>
<?php endif; ?>

<?php if ($isLocked) : ?>
    <div id="wpstg-backup-locked">
        <div class="icon"><img width="20" src="<?php echo WPSTG_PLUGIN_URL . "assets/img/wpstaging-icon.png"; ?>"></div>
        <div class="text"><?php esc_html_e('There is a backup work in progress...', 'wp-staging'); ?></div>
    </div>
<?php endif; ?>

<div id="wpstg-step-1">
    <button id="wpstg-new-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" <?php echo $disabledProperty; ?> <?php echo $disabledPropertyCreateBackup ?>>
        <?php esc_html_e('Create New Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-upload-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" <?php echo $disabledProperty ?>>
        <?php esc_html_e('Upload Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-manage-backup-schedules" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" <?php echo $disabledProperty ?>>
        <?php esc_html_e('Edit Backup Plans', 'wp-staging') ?>
    </button>
    <div id="wpstg-report-issue-wrapper">
        <button type="button" id="wpstg-report-issue-button" class="wpstg-button">
            <i class="wpstg-icon-issue"></i><?php echo __("Report Issue", "wp-staging"); ?>
        </button>
        <?php require_once($this->views . '_main/report-issue.php'); ?>
    </div>
</div>

<div id="wpstg-backup-runs-info">
    <?php \WPStaging\Core\WPStaging::make(\WPStaging\Pro\Backup\Ajax\ScheduleList::class)->renderNextBackupSnippet(); ?>
</div>

<div id="wpstg-existing-backups">
        <div id="backup-messages"></div>
        <div class="wpstg-backup-list">
            <ul>
                <li><?php _e('Searching for existing backups...', 'wp-staging') ?></li>
            </ul>
        </div>
</div>

<?php include(__DIR__ . '/modal/export.php'); ?>
<?php include(__DIR__ . '/modal/progress.php'); ?>
<?php include(__DIR__ . '/modal/download.php'); ?>
<?php include(__DIR__ . '/modal/upload.php'); ?>
<?php include(__DIR__ . '/modal/manage-schedules.php'); ?>
<?php include(__DIR__ . '/modal/import.php'); ?>

<?php include(__DIR__ . '/restore-wait.php'); ?>

<div
    id="wpstg--js--translations"
    style="display:none;"
    data-modal-txt-critical="<?php esc_attr_e('Critical', 'wp-staging') ?>"
    data-modal-txt-errors="<?php esc_attr_e('Error(s)', 'wp-staging') ?>"
    data-modal-txt-warnings="<?php esc_attr_e('Warning(s)', 'wp-staging') ?>"
    data-modal-txt-and="<?php esc_attr_e('and', 'wp-staging') ?>"
    data-modal-txt-found="<?php esc_attr_e('Found', 'wp-staging') ?>"
    data-modal-txt-show-logs="<?php esc_attr_e('Show Logs', 'wp-staging') ?>"
    data-modal-logs-title="<?php esc_attr_e(
        '{critical} Critical, {errors} Error(s) and {warnings} Warning(s) Found',
        'wp-staging'
    ) ?>"
></div>

<div id="wpstg-delete-confirmation"></div>
