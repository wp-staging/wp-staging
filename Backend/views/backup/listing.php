<?php

use WPStaging\Framework\TemplateEngine\TemplateEngine;
use WPStaging\Framework\Adapter\Directory;

/**
 * @see \WPStaging\Pro\Backup\Ajax\Listing::render
 *
 * @var TemplateEngine              $this
 * @var array                       $directories
 * @var string                      $urlPublic
 * @var Directory                   $directory
 */
?>

<div id="wpstg-step-1">
    <button id="wpstg-new-backup" class="wpstg-next-step-link wpstg-link-btn wpstg-blue-primary wpstg-button"
            data-action="wpstg--backups--export">
        <?php esc_html_e('Backup & Export', 'wp-staging') ?>
    </button>
    <button id="wpstg-import-backup" class="wpstg-next-step-link wpstg-link-btn wpstg-blue-primary wpstg-button"
            data-action="wpstg--backups--import">
        <?php esc_html_e('Import', 'wp-staging') ?>
    </button>
</div>

<div id="wpstg-existing-backups">
        <div style="display: flex; flex-direction: row; justify-content: space-between">
            <h3><?php _e('Your Backups:', 'wp-staging') ?></h3>
        </div>
        <div class="wpstg-backup-list">
            <ul>
                <li><?php _e('Searching for existing backups...', 'wp-staging') ?></li>
            </ul>
        </div>
</div>

<?php include(__DIR__ . '/modal/export.php'); ?>
<?php include(__DIR__ . '/modal/progress.php'); ?>
<?php include(__DIR__ . '/modal/download.php'); ?>
<?php include(__DIR__ . '/modal/import.php'); ?>

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
