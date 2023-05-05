<?php

use WPStaging\Framework\Facades\Escape;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreRequirementsCheckTask;

/**
 * @var \WPStaging\Framework\TemplateEngine\TemplateEngine $this
 * @var \WPStaging\Backup\Entity\ListableBackup $backup
 * @var string $urlAssets
 */
$backupName             = $backup->backupName;
$notes                  = $backup->notes;
$createdAt              = $backup->dateCreatedTimestamp;
$uploadedAt             = $backup->dateUploadedTimestamp;
$size                   = $backup->size;
$id                     = $backup->id;
$automatedBackup        = $backup->automatedBackup;
$isLegacy               = $backup->isLegacy;
$isCorrupt              = $backup->isCorrupt;
$isValidMultipartBackup = $backup->isValidMultipartBackup;
$isMultipartBackup      = $backup->isMultipartBackup;
$missingParts           = isset($backup->validationIssues['missingParts']) ? $backup->validationIssues['missingParts'] : [];
$sizeIssues             = isset($backup->validationIssues['sizeIssues']) ? $backup->validationIssues['sizeIssues'] : [];
$existingBackupParts    = $backup->existingBackupParts;
$isValidFileIndex       = $backup->isValidFileIndex;
$indexFileError         = $backup->errorMessage;

// Default error message
if (empty($indexFileError)) {
    $indexFileError = __("This backup has an invalid files index. Please create a new backup!", 'wp-staging');
}

$isUnsupported = version_compare($backup->generatedOnWPStagingVersion, RestoreRequirementsCheckTask::BETA_VERSION_LIMIT, '<');

if (defined('WPSTG_DOWNLOAD_BACKUP_USING_PHP') && WPSTG_DOWNLOAD_BACKUP_USING_PHP) {
    // Download through PHP. Useful when the server mistakenly reads the .wpstg file as plain text instead of downloading it.
    $downloadUrl = add_query_arg([
        'wpstgBackupDownloadNonce' => wp_create_nonce('wpstg_download_nonce'),
        'wpstgBackupDownloadMd5' => $backup->md5BaseName,
    ], admin_url());
} else {
    // Direct download of .wpstg file.
    $downloadUrl = $backup->downloadUrl;
}

$logUrl = add_query_arg([
    'action' => 'wpstg--backups--logs',
    'nonce' => wp_create_nonce('wpstg_log_nonce'),
    'md5' => $backup->md5BaseName,
], admin_url('admin-post.php'));

?>
<li id="<?php echo esc_attr($id) ?>" class="wpstg-clone wpstg-backup" data-md5="<?php echo esc_attr($backup->md5BaseName); ?>" data-name="<?php echo esc_attr($backup->backupName); ?>">

    <div class="wpstg-clone-header">
        <span class="wpstg-clone-title">
            <?php echo esc_html($backupName); ?>
        </span>
        <?php if (!$isCorrupt) : ?>
            <div class="wpstg-clone-labels">
                <span class="wpstg-clone-label"><?php echo $backup->type === 'single' ? esc_html__('Single Site', 'wp-staging') : esc_html__('Multisite', 'wp-staging') ?></span>
                <?php echo $backup->isMultipartBackup ? '<span class="wpstg-clone-label">' . esc_html__('Multipart Backup', 'wp-staging') . '</span>' : '' ?>
            </div>
        <?php endif ?>
        <div class="wpstg-clone-actions">
            <div class="wpstg-dropdown wpstg-action-dropdown">
                <a href="#" class="wpstg-dropdown-toggler">
                    <?php esc_html_e("Actions", "wp-staging"); ?>
                    <span class="wpstg-caret"></span>
                </a>
                <div class="wpstg-dropdown-menu">
                    <?php if (!$isLegacy && !$isCorrupt) : ?>
                        <a href="#" class="wpstg-clone-action wpstg--backup--restore"
                           data-filePath="<?php echo esc_attr($backup->relativePath) ?>"
                           data-title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging') ?>"
                           title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging') ?>">
                            <?php esc_html_e('Restore', 'wp-staging') ?>
                        </a>
                    <?php endif ?>
                    <?php if ($isMultipartBackup) : ?>
                        <a href="#" class="wpstg--backup--download wpstg--backup--download-modal wpstg-merge-clone wpstg-clone-action"
                           data-filePath="<?php echo esc_attr($backup->relativePath) ?>"
                           title="<?php esc_attr_e('Download backup file to local system', 'wp-staging') ?>">
                            <?php esc_html_e('Download', 'wp-staging') ?>
                        </a>
                    <?php else : ?>
                        <a download
                           href="<?php echo esc_url($downloadUrl ?: '') ?>" class="wpstg--backup--download wpstg-merge-clone wpstg-clone-action"
                           title="<?php esc_attr_e('Download backup file to local system', 'wp-staging') ?>">
                            <?php esc_html_e('Download', 'wp-staging') ?>
                        </a>
                    <?php endif ?>
                    <a download
                       href="<?php echo esc_url($logUrl ?: '') ?>" class="wpstg-merge-clone wpstg-clone-action"
                       title="<?php esc_attr_e('Download backup log file', 'wp-staging') ?>">
                        <?php esc_html_e('Log File', 'wp-staging') ?>
                    </a>
                    <?php if (!$isLegacy && !$isCorrupt) : ?>
                        <a href="#" class="wpstg--backup--edit wpstg-clone-action"
                           data-md5="<?php echo esc_attr($backup->md5BaseName); ?>"
                           data-name="<?php echo esc_attr($backupName); ?>"
                           data-notes="<?php echo esc_attr($notes); ?>"
                           title="<?php esc_attr_e('Edit backup name and / or notes', 'wp-staging') ?>">
                            <?php esc_html_e('Edit', 'wp-staging') ?>
                        </a>
                    <?php endif ?>
                    <a href="#" class="wpstg-remove-clone wpstg-clone-action wpstg-delete-backup"
                       data-name="<?php echo esc_attr($backupName); ?>"
                       data-md5="<?php echo esc_attr($backup->md5BaseName) ?>"
                       title="<?php esc_attr_e('Delete this backup. This action can not be undone!', 'wp-staging') ?>">
                        <?php esc_html_e('Delete', 'wp-staging') ?>
                    </a>
                    <?php
                    do_action('wpstg.views.backup.listing.single.after_actions', $backup);
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="wpstg-staging-info">
        <ul>
            <?php if ($isCorrupt) : ?>
                <li class="wpstg-corrupted-backup wpstg--red">
                    <div class="wpstg-exclamation">!</div>
                    <strong><?php esc_html_e('This backup file is corrupt. Please create a new backup!', 'wp-staging') ?></strong><br/>
                </li>
            <?php endif ?>
            <?php if ($isUnsupported && !$isCorrupt) : ?>
                <li class="wpstg-unsupported-backup wpstg--red">
                    <div class="wpstg-exclamation">!</div>
                    <strong><?php esc_html_e('This backup was generated on a beta version of WP STAGING and cannot be restored with the current version.', 'wp-staging') ?></strong><br/>
                </li>
            <?php endif ?>
            <?php if ($createdAt) : ?>
                <li>
                    <strong><?php $isCorrupt ? esc_html_e('Last modified:', 'wp-staging') : esc_html_e('Created on:', 'wp-staging') ?></strong>
                    <?php
                    $date = new DateTime();
                    $date->setTimestamp($createdAt);
                    echo esc_html($this->transformToWpFormat($date));
                    ?>
                </li>
            <?php endif ?>
            <?php if ($notes) : ?>
                <li>
                    <strong><?php esc_html_e('Notes:', 'wp-staging') ?></strong><br/>
                    <div class="backup-notes">
                        <?php echo Escape::escapeHtml(__(nl2br($notes, 'wp-staging'))); ?>
                    </div>
                </li>
            <?php endif ?>
            <li>
                <strong><?php esc_html_e('Size: ', 'wp-staging') ?></strong>
                <?php echo esc_html($size); ?>
            </li>
            <?php if (!$isCorrupt) : ?>
                <li class="single-backup-includes">
                    <strong><?php esc_html_e('Contains: ', 'wp-staging') ?></strong>
                    <?php
                    $isExportingDatabase            = $backup->isExportingDatabase;
                    $isExportingPlugins             = $backup->isExportingPlugins;
                    $isExportingMuPlugins           = $backup->isExportingMuPlugins;
                    $isExportingThemes              = $backup->isExportingThemes;
                    $isExportingUploads             = $backup->isExportingUploads;
                    $isExportingOtherWpContentFiles = $backup->isExportingOtherWpContentFiles;
                    include(__DIR__ . '/modal/partials/backup-contains.php');
                    ?>
                </li>
                <?php if ($automatedBackup) : ?>
                    <li style="font-style: italic">
                        <img class="wpstg--dashicons wpstg-dashicons-19 wpstg-dashicons-grey wpstg--backup-automated" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/update.svg"/> <?php esc_html_e('Backup created automatically.', 'wp-staging') ?>
                    </li>
                <?php endif ?>
                <?php if ($isLegacy) : ?>
                    <li style="font-style: italic">
                        <img class="wpstg--dashicons wpstg-dashicons-19 wpstg-dashicons-grey" src="<?php echo esc_url($urlAssets); ?>svg/vendor/dashicons/cloud-saved.svg"/> <?php esc_html_e('This database backup was generated from an existing legacy WP STAGING Database backup in the .SQL format.', 'wp-staging') ?>
                    </li>
                <?php endif ?>
            <?php endif ?>
            <?php if ($isMultipartBackup) : ?>
                <div class="wpstg-tabs-wrapper invalid-backup-tabs" style="margin-left: -8px; text-align: left;">
                    <?php include(__DIR__ . '/partials/invalid-backup.php'); ?>
                </div>
            <?php endif ?>
            <?php if (!$isMultipartBackup && !$isValidFileIndex) : ?>
                <li class="wpstg-corrupted-backup wpstg--red">
                    <div class="wpstg-exclamation">!</div>
                    <strong><?php echo esc_html($indexFileError); ?></strong><br/>
                </li>
            <?php endif ?>
        </ul>
    </div>
</li>
