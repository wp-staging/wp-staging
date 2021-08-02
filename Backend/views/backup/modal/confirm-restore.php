<?php

use WPStaging\Pro\Backup\Entity\BackupMetadata;

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
        <h3 class="wpstg--swal2-title" style="text-align: center;"><?php _e('This will restore your website! </br> Are you sure?', 'wp-staging'); ?></h3>
    </div>
    <div id="wpstg-confirm-backup-restore-data">
        <ul>
            <?php if ($info->getIsExportingDatabase()) : ?>
                <li style="list-style-type: square;"><?php _e('Database will be replaced.', 'wp-staging'); ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingPlugins()) : ?>
                <li style="list-style-type: square;"><?php _e('Plugins will be added.', 'wp-staging') ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingThemes()) : ?>
                <li style="list-style-type: square;"><?php _e('Themes will be added.', 'wp-staging') ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingMuPlugins()) : ?>
                <li style="list-style-type: square;"><?php _e('Mu-plugins will be added.', 'wp-staging') ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingUploads()) : ?>
                <li style="list-style-type: square;"><?php _e('Media files and images will be added. ', 'wp-staging') ?></li>
            <?php endif; ?>
            <?php if ($info->getIsExportingOtherWpContentFiles()) : ?>
                <li style="list-style-type: square;"><?php _e('Other files in wp-content folder will be added. ', 'wp-staging') ?></li>
            <?php endif; ?>
        </ul>
        <?php if (!$isDatabaseOnlyBackup && !empty($info->getTotalFiles())) : ?>
            <div class="wpstg-db-table" style="margin-top:5px;">
                <strong><?php _e('Total Files:', 'wp-staging') ?></strong>
                <span class=""><?php echo $info->getTotalFiles() ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($_POST['search'])) : ?>
            <div class="wpstg-db-table" style="margin-top:20px;">
                <?php foreach ($_POST['search'] as $index => $search) : ?>
                    <span class=""><?php echo sprintf(__('Search: %s', 'wp-staging'), $search) ?></span> <br/>
                    <span class=""><?php echo sprintf(__('Replace: %s', 'wp-staging'), $_POST['replace'][$index]) ?></span>
                    <hr>
                <?php endforeach ?>
            </div>
        <?php endif ?>
        <div class="wpstg-db-table" style="margin-top:5px;display:none;">
            <?php
            $backupGeneratedInVersion = $info->getVersion();
            $thisVersion = \WPStaging\Core\WPStaging::getVersion();
            // Use this in the future if we need to warn the user about compatibility issues between export version and current version.
            ?>
            <small><?php _e(sprintf('This backup was generated on WP STAGING %s. You are running WP STAGING %s.', $info->getVersion(), \WPStaging\Core\WPStaging::getVersion()), 'wp-staging') ?></small>
        </div>
    </div>
</div>
