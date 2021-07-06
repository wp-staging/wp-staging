<?php

use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Adapter\Directory;

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
?>

<div id="wpstg-step-1">
    <button id="wpstg-new-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button" <?php echo $disabledProperty ?>>
        <?php esc_html_e('Create New Backup', 'wp-staging') ?>
    </button>
    <button id="wpstg-upload-backup" class="wpstg-next-step-link wpstg-blue-primary wpstg-button wpstg-ml-4" <?php echo $disabledProperty ?>>
        <?php esc_html_e('Upload Backup', 'wp-staging') ?>
    </button>
    <div class="wpstg--tooltip">
        <img class="wpstg--dashicons wpstg-dashicons-21" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/info-outline.svg"></img>
        <p class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                <?php _e("Upload a WP STAGING backup file (*.wpstg) and restore your site to it at any time. This backup can have been created from this site, or even created on another website. So you can migrate the other site to this one.", "wp-staging")?>
                <br><br>
                <?php _e("Videos:", "wp-staging")?>
                <br>
                <?php echo sprintf(__('&#8226; <a href="%s" target="_blank">How to backup WordPress</a>', 'wp-staging'), 'https://www.youtube.com/watch?v=q352aYduOUY'); ?>
                <br>
                <?php echo sprintf(__('&#8226; <a href="%s" target="_blank">How to migrate WordPress</a>', 'wp-staging'), 'https://www.youtube.com/watch?v=DBaZQg1Efq4'); ?>
        </p>
    </div>
    <div id="wpstg-report-issue-wrapper">
        <button type="button" id="wpstg-report-issue-button" class="wpstg-button">
            <i class="wpstg-icon-issue"></i><?php echo __("Report Issue", "wp-staging"); ?>
        </button>
        <?php require_once($this->views . '_main/report-issue.php'); ?>
    </div>
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
