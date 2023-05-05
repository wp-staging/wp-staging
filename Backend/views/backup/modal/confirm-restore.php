<?php

use WPStaging\Backup\Entity\BackupMetadata;

if (!defined("WPINC")) {
    die();
}

/**
 * @var BackupMetadata $info
 */

$isDatabaseOnlyBackup = $info->getIsExportingDatabase()
                        && !$info->getIsExportingPlugins()
                        && !$info->getIsExportingThemes()
                        && !$info->getIsExportingMuPlugins()
                        && !$info->getIsExportingUploads()
                        && !$info->getIsExportingOtherWpContentFiles();

?>
<div id="wpstg-confirm-backup-restore-wrapper">
    <div class="wpstg-confirm-backup-restore-header">
        <h3 class="wpstg--swal2-title" style="text-align: center;"><?php echo wp_kses_post(__('This will restore your website! </br> Are you sure?', 'wp-staging')); ?></h3>
    </div>
    <div id="wpstg-confirm-backup-restore-data">
        <ul>
            <?php if ($info->getIsExportingDatabase()) : ?>
                <li style="list-style-type: square;"><?php esc_html_e('Database will be replaced.', 'wp-staging'); ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingPlugins()) : ?>
                <li style="list-style-type: square;"><?php esc_html_e('Plugins will be added.', 'wp-staging') ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingThemes()) : ?>
                <li style="list-style-type: square;"><?php esc_html_e('Themes will be added.', 'wp-staging') ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingMuPlugins()) : ?>
                <li style="list-style-type: square;"><?php esc_html_e('Mu-plugins will be added.', 'wp-staging') ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingUploads()) : ?>
                <li style="list-style-type: square;"><?php esc_html_e('Media files and images will be added. ', 'wp-staging') ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingOtherWpContentFiles()) : ?>
                <li style="list-style-type: square;"><?php esc_html_e('Other files in wp-content folder will be added. ', 'wp-staging') ?></li>
            <?php endif; ?>
        </ul>
        <?php if (!$isDatabaseOnlyBackup && !empty($info->getTotalFiles())) : ?>
            <div class="wpstg-db-table" style="margin-top:5px;">
                <strong><?php esc_html_e('Total Files:', 'wp-staging') ?></strong>
                <span class=""><?php echo esc_html($info->getTotalFiles()) ?></span>
            </div>
        <?php endif; ?>
        <div class="wpstg-db-table" style="margin-top:5px;display:none;">
            <?php
            $backupGeneratedInVersion = $info->getVersion();
            $thisVersion = \WPStaging\Core\WPStaging::getVersion();
            // Use this in the future if we need to warn the user about compatibility issues between backup version and current version.
            ?>
            <small><?php echo sprintf(wp_kses_post('This backup was generated on WP STAGING %s. </br> You are running WP STAGING %s.', 'wp-staging'), esc_html($backupGeneratedInVersion), esc_html($thisVersion)) ?></small>
        </div>
    </div>
</div>
