<?php

use WPStaging\Backup\Entity\BackupMetadata;

if (!defined("WPINC")) {
    die();
}

/**
 * @var BackupMetadata $info
 */

$backupParts = [
    [
        'backupContains'       => $info->getIsExportingDatabase(),
        'isSkipped'            => $filters['database'],
        'messageWhenRestoring' => __('Database will be replaced.', 'wp-staging'),
        'messageWhenSkipped'   => __('Database restore skipped by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingPlugins(),
        'isSkipped'            => $filters['plugins'],
        'messageWhenRestoring' => __('Plugins will be added.', 'wp-staging'),
        'messageWhenSkipped'   => __('Plugins restore skipped by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingThemes(),
        'isSkipped'            => $filters['themes'],
        'messageWhenRestoring' => __('Themes will be added.', 'wp-staging'),
        'messageWhenSkipped'   => __('Themes restore skipped by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingMuPlugins(),
        'isSkipped'            => $filters['muPlugins'],
        'messageWhenRestoring' => __('Mu-plugins will be added.', 'wp-staging'),
        'messageWhenSkipped'   => __('Mu-plugins restore skipped by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingUploads(),
        'isSkipped'            => $filters['uploads'],
        'messageWhenRestoring' => __('Media files and images will be added.', 'wp-staging'),
        'messageWhenSkipped'   => __('Media files and images restore skipped by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingOtherWpContentFiles(),
        'isSkipped'            => $filters['wpContent'],
        'messageWhenRestoring' => __('Other files in wp-content folder will be added.', 'wp-staging'),
        'messageWhenSkipped'   => __('Other files in wp-content folder restore skipped by filter.', 'wp-staging')
    ],
    [
        'backupContains'       => $info->getIsExportingOtherWpRootFiles(),
        'isSkipped'            => $filters['wpRoot'],
        'messageWhenRestoring' => __('Other files in WP root folder will be added.', 'wp-staging'),
        'messageWhenSkipped'   => __('Other files in WP root folder restore skipped by filter.', 'wp-staging')
    ]
];

$isDatabaseOnlyBackup = $info->getIsExportingDatabase() && !$filters['database']
    && (!$info->getIsExportingPlugins() || $filters['plugins'])
    && (!$info->getIsExportingThemes() || $filters['themes'])
    && (!$info->getIsExportingMuPlugins() || $filters['muPlugins'])
    && (!$info->getIsExportingUploads() || $filters['uploads'])
    && (!$info->getIsExportingOtherWpContentFiles() || $filters['wpContent'])
    && (!$info->getIsExportingOtherWpRootFiles() || $filters['wpRoot']);

$hasFilesFiltered = $filters['plugins']
    || $filters['themes']
    || $filters['muPlugins']
    || $filters['uploads']
    || $filters['wpContent']
    || $filters['wpRoot'];

?>
<div id="wpstg-confirm-backup-restore-wrapper">
    <div class="wpstg-confirm-backup-restore-header">
        <h3 class="wpstg--swal2-title" style="text-align: center;"><?php echo wp_kses_post(__('This will restore your website! </br> Are you sure?', 'wp-staging')); ?></h3>
    </div>
    <div id="wpstg-confirm-backup-restore-data">
        <ul>
            <?php foreach ($backupParts as $part) : ?>
                <?php if ($part['backupContains']) : ?>
                    <li class="<?php echo $part['isSkipped'] ? 'wpstg--red-warning' : '' ?>"> <?php echo $part['isSkipped'] ? esc_html($part['messageWhenSkipped']) : esc_html($part['messageWhenRestoring']) ?> </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <?php if (!$isDatabaseOnlyBackup && !empty($info->getTotalFiles())) : ?>
            <div class="wpstg-db-table" style="margin-top:5px;">
                <strong><?php esc_html_e('Total Files:', 'wp-staging') ?></strong>
                <span><?php echo esc_html($info->getTotalFiles()) ?></span>
                <?php if ($hasFilesFiltered) : ?>
                    <span class="wpstg--red-warning">(<?php esc_html_e('Some files will be skipped due to filter', 'wp-staging') ?>)</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="wpstg-db-table" style="margin-top:5px;display:none;">
            <?php
            $backupGeneratedInVersion = $info->getBackupVersion();
            $thisVersion = BackupMetadata::BACKUP_VERSION;
            // Use this in the future if we need to warn the user about compatibility issues between backup version and current version.
            ?>
            <small><?php echo sprintf(wp_kses_post('This backup was generated on WP STAGING %s. </br> You are running WP STAGING %s.', 'wp-staging'), esc_html($backupGeneratedInVersion), esc_html($thisVersion)) ?></small>
        </div>
    </div>
</div>
