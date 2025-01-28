<?php

use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Facades\Escape;

if (!defined("WPINC")) {
    die();
}

/**
 * @var BackupMetadata $info
 * @var bool[] $excluded
 * @var bool[] $replaced
 */

$backupParts = [
    [
        'backupContains'       => $info->getIsExportingDatabase(),
        'excluded'             => $excluded['database'],
        'messageWhenRestoring' => $info->getBackupType() === BackupMetadata::BACKUP_TYPE_MULTISITE ? __('Database tables of whole network will be replaced.', 'wp-staging') : __('Database tables of current site will be replaced.', 'wp-staging'),
        'messageWhenExcluded'  => __('Database restore excluded by filter.', 'wp-staging')
    ],
    [
        // Importing Users in Database for restoring single/subsite backups on subsite
        'backupContains'       => $info->getIsExportingDatabase() && is_multisite() && $info->getBackupType() !== BackupMetadata::BACKUP_TYPE_MULTISITE,
        'excluded'             => $excluded['database'],
        'messageWhenRestoring' => __('Users from the backup will be imported.', 'wp-staging'),
        'messageWhenExcluded'  => __('Importing user excluded by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingPlugins(),
        'excluded'             => $excluded['plugins'],
        'messageWhenRestoring' => $replaced['plugins'] ? __('Plugins will be replaced.', 'wp-staging') : __('Plugins will be added.', 'wp-staging'),
        'messageWhenExcluded'  => __('Plugins restore excluded by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingThemes(),
        'excluded'             => $excluded['themes'],
        'messageWhenRestoring' => $replaced['themes'] ? __('Themes will be replaced.', 'wp-staging') : __('Themes will be added.', 'wp-staging'),
        'messageWhenExcluded'  => __('Themes restore excluded by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingMuPlugins(),
        'excluded'             => $excluded['muPlugins'],
        'messageWhenRestoring' => $replaced['muPlugins'] ? __('Mu-plugins will be replaced.', 'wp-staging') : __('Mu-plugins will be added.', 'wp-staging'),
        'messageWhenExcluded'  => __('Mu-plugins restore excluded by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingUploads(),
        'excluded'             => $excluded['uploads'],
        'messageWhenRestoring' => $replaced['uploads'] ? __('Media files and images will be replaced.', 'wp-staging') : __('Media files and images will be added.', 'wp-staging'),
        'messageWhenExcluded'  => __('Media files and images restore excluded by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingOtherWpContentFiles(),
        'excluded'             => $excluded['wpContent'],
        'messageWhenRestoring' => $replaced['wpContent'] ? __('Other files in wp-content folder will be replaced.', 'wp-staging') : __('Other files in wp-content folder will be added.', 'wp-staging'),
        'messageWhenExcluded'  => __('Other files in wp-content folder restore excluded by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingOtherWpRootFiles(),
        'excluded'             => $excluded['wpRoot'],
        'messageWhenRestoring' => __('Other files in WP root folder will be added.', 'wp-staging'),
        'messageWhenExcluded'  => __('Other files in WP root folder restore excluded by filter.', 'wp-staging')
    ]
];

$isDatabaseOnlyBackup = $info->getIsExportingDatabase() && !$excluded['database']
    && (!$info->getIsExportingPlugins() || $excluded['plugins'])
    && (!$info->getIsExportingThemes() || $excluded['themes'])
    && (!$info->getIsExportingMuPlugins() || $excluded['muPlugins'])
    && (!$info->getIsExportingUploads() || $excluded['uploads'])
    && (!$info->getIsExportingOtherWpContentFiles() || $excluded['wpContent'])
    && (!$info->getIsExportingOtherWpRootFiles() || $excluded['wpRoot']);

$areFilesExcluded = $excluded['plugins']
    || $excluded['themes']
    || $excluded['muPlugins']
    || $excluded['uploads']
    || $excluded['wpContent']
    || $excluded['wpRoot'];

?>
<div id="wpstg-confirm-backup-restore-wrapper">
    <div class="wpstg-confirm-backup-restore-header">
        <h3 class="wpstg--swal2-title" style="text-align: center;"><?php echo wp_kses_post(__('This will restore your website! </br> Are you sure?', 'wp-staging')); ?></h3>
    </div>
    <div id="wpstg-confirm-backup-restore-data">
        <ul>
            <?php foreach ($backupParts as $part) : ?>
                <?php if ($part['backupContains']) : ?>
                    <li class="<?php echo $part['excluded'] ? 'wpstg--red-warning' : '' ?>"> <?php echo $part['excluded'] ? esc_html($part['messageWhenExcluded']) : esc_html($part['messageWhenRestoring']) ?> </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php if (!$isDatabaseOnlyBackup && !empty($info->getTotalFiles())) : ?>
            <div class="wpstg-db-table" style="margin-top:5px;">
                <strong><?php esc_html_e('Total Files:', 'wp-staging') ?></strong>
                <span><?php echo esc_html($info->getTotalFiles()) ?></span>
                <?php if ($areFilesExcluded) : ?>
                    <span class="wpstg--red-warning">(<?php esc_html_e('Some files restore will be excluded by filter', 'wp-staging') ?>)</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="wpstg-mt-10px">
            <?php echo sprintf(
                Escape::escapeHtml(__('Note: If you want to keep specific files and don\'t want them to be replaced, you can use this <a href="%s" target="_blank">filter</a>', 'wp-staging')),
                'https://wp-staging.com/docs/actions-and-filters/#Restore_Backup_and_Keep_Existing_Media_Files_Plugins_or_Themes'
            ); ?>
        </div>
        <div class="wpstg-db-table" style="margin-top:5px;display:none;">
            <?php
            $backupGeneratedInVersion = $info->getBackupVersion();
            $thisVersion = BackupHeader::BACKUP_VERSION;
            // Use this in the future if we need to warn the user about compatibility issues between backup version and current version.
            ?>
            <small><?php echo sprintf(wp_kses_post('This backup was generated on WP STAGING %s. </br> You are running WP STAGING %s.', 'wp-staging'), esc_html($backupGeneratedInVersion), esc_html($thisVersion)) ?></small>
        </div>
    </div>
</div>
