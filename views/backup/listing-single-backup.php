<?php

use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\UI\Alert;
use WPStaging\Framework\Utils\Urls;

/**
 * @var \WPStaging\Framework\TemplateEngine\TemplateEngine $this
 * @var \WPStaging\Backup\Entity\ListableBackup            $backup
 * @var string                                             $urlAssets
 * @var bool                                               $isProVersion
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
$isUnsupported          = $backup->isUnsupported;
$isProVersion           = WPStaging::isPro();
$requires64Bit          = empty($size) && (PHP_INT_SIZE !== 8);
$isContaining2GbFile    = $backup->isContaining2GBFile;
$backupVersion          = $backup->generatedOnBackupVersion;

// Default error message
if (empty($indexFileError)) {
    $indexFileError = __("This backup has an invalid files index. Please create a new backup!", 'wp-staging');
}

// Download URL of backup file
$downloadUrl = $backup->downloadUrl;

/** @var ZlibCompressor $compressor */
$compressor = WPStaging::make(ZlibCompressor::class);

// Fix mixed http/https
$downloadFileUrl = $downloadUrl;
$downloadUrl     = (new Urls())->maybeUseProtocolRelative($downloadUrl);

$logUrl = add_query_arg([
    'action' => 'wpstg--backups--logs',
    'nonce'  => wp_create_nonce('wpstg_log_nonce'),
    'md5'    => $backup->md5BaseName,
], admin_url('admin-post.php'));

$downloadAttribute = 'download';
if (WPStaging::isOnWordPressPlayground()) {
    $downloadAttribute = 'target=_blank';
}

// For wpstg-restore
$wpstgRestorePageUrl = add_query_arg([
    'page' => 'wpstg-restorer',
    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
    'hash' => rtrim(base64_encode($downloadUrl . '.backupid:' . $id), "="),
], admin_url('admin.php'));
?>
<li id="<?php echo esc_attr($id) ?>" class="wpstg-clone wpstg-backup" data-md5="<?php echo esc_attr($backup->md5BaseName); ?>" data-name="<?php echo esc_attr($backup->backupName); ?>">
    <div class="wpstg-clone-header">
        <span class="wpstg-clone-title">
            <?php echo esc_html(str_replace(['\\&quot;', '\\&#039;'], ['"', "'"], $backupName)); ?>
        </span>
        <?php if (!$isCorrupt) : ?>
            <div class="wpstg-clone-labels">
                <span class="wpstg-clone-label"><?php echo esc_html($backup->getBackupType()); ?></span>
                <?php echo $backup->isMultipartBackup ? '<span class="wpstg-clone-label">' . esc_html__('Multipart Backup', 'wp-staging') . '</span>' : ''; ?>
                <?php if (version_compare($backupVersion, '2.0.0', '<')) : ?>
                    <span class="wpstg-clone-label wpstg-clone-label--warning wpstg--tooltip">
                        <?php esc_html_e('V1', 'wp-staging'); ?>
                        <div class="wpstg--tooltiptext"><?php esc_html_e('This backup is generated on an old version of the plugin', 'wp-staging'); ?></div>
                    </span>
                <?php endif; ?>
                <?php if ($backup->isZlibCompressed) : ?>
                    <span class="wpstg-clone-label wpstg-clone-label--primary-btn-lighter">
                        <?php esc_html_e('Compressed', 'wp-staging'); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="wpstg-clone-actions">
            <div class="wpstg-dropdown wpstg-action-dropdown">
                <a href="#" class="wpstg-dropdown-toggler">
                    <?php esc_html_e("Actions", "wp-staging"); ?>
                    <span class="wpstg-caret"></span>
                </a>
                <div class="wpstg-dropdown-menu">
                    <?php if (!$isLegacy && !$isCorrupt && !$requires64Bit) : ?>
                        <a href="javascript:void(0)" class="wpstg-clone-action wpstg--backup--restore"
                           data-filePath="<?php echo esc_attr($backup->relativePath); ?>"
                           data-title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging'); ?>"
                           title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging'); ?>">
                            <div class="wpstg-dropdown-item-icon">
                                <?php $this->getAssets()->renderSvg('restore'); ?>
                            </div>
                            <?php esc_html_e('Restore', 'wp-staging'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!$isProVersion) :?>
                        <a href="javascript:void(0)" class="wpstg-pro-clone-feature wpstg-clone-action"  title="<?php echo esc_html__("Upload Backup to Cloud", "wp-staging"); ?>">
                            <div class="wpstg-dropdown-item-icon">
                                <?php $this->getAssets()->renderSvg('upload-cloud'); ?>
                            </div>
                            <?php esc_html_e("Upload Backup to Cloud", "wp-staging"); ?>
                            <span>(Pro)</span>
                        </a>
                    <?php endif;?>
                    <?php if (!$isLegacy && !$isCorrupt && $isProVersion) : ?>
                        <a href="javascript:void(0)" class="wpstg--backup--remote-upload wpstg-clone-action"
                           data-filePath="<?php echo esc_attr($backup->relativePath); ?>"
                           title="<?php esc_attr_e('Upload to remote storage', 'wp-staging'); ?>">
                            <div class="wpstg-dropdown-item-icon">
                                <?php $this->getAssets()->renderSvg('upload-cloud'); ?>
                            </div>
                            <?php esc_html_e('Upload Backup to Cloud', 'wp-staging'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($isMultipartBackup) : ?>
                        <a href="javascript:void(0)" class="wpstg--backup--download wpstg--backup--download-modal wpstg-merge-clone wpstg-clone-action"
                           data-filePath="<?php echo esc_attr($backup->relativePath); ?>"
                           title="<?php esc_attr_e('Download backup file to local system', 'wp-staging'); ?>">
                            <div class="wpstg-dropdown-item-icon">
                                <?php $this->getAssets()->renderSvg('download'); ?>
                            </div>
                            <?php esc_html_e('Download Backup File', 'wp-staging'); ?>
                        </a>
                    <?php else : ?>
                        <a <?php echo esc_attr($downloadAttribute);?> href="<?php echo esc_url($downloadUrl ?: ''); ?>" class="wpstg--backup--download wpstg-clone-action"
                           title="<?php esc_attr_e('Download backup file to local system', 'wp-staging'); ?>">
                            <div class="wpstg-dropdown-item-icon">
                                <?php $this->getAssets()->renderSvg('download'); ?>
                            </div>
                            <?php esc_html_e('Download Backup File', 'wp-staging'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="javascript:void(0)" id="wpstg-copy-backup-url" class="wpstg-clone-action"
                       data-copy-content="<?php echo esc_attr($downloadFileUrl); ?>"
                       title="<?php esc_attr_e('Copy url to backup file to restore it quickly on another website.', 'wp-staging'); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $this->getAssets()->renderSvg('copy-link'); ?>
                        </div>
                        <?php esc_html_e('Copy Link to Backup', 'wp-staging'); ?>
                    </a>
                    <a <?php echo esc_attr($downloadAttribute);?> href="<?php echo esc_url($logUrl ?: ''); ?>" class="wpstg-clone-action" title="<?php esc_attr_e('Download Log File', 'wp-staging'); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $this->getAssets()->renderSvg('file'); ?>
                        </div>
                        <?php esc_html_e('Download Log File', 'wp-staging'); ?>
                    </a>
                    <?php if (!$isLegacy && !$isCorrupt && !$requires64Bit) : ?>
                        <a href="javascript:void(0)" class="wpstg--backup--edit wpstg-clone-action"
                           data-md5="<?php echo esc_attr($backup->md5BaseName); ?>"
                           data-name="<?php echo esc_attr($backupName); ?>"
                           data-notes="<?php echo esc_attr($notes); ?>"
                           title="<?php esc_attr_e('Edit backup name and / or notes', 'wp-staging'); ?>">
                            <div class="wpstg-dropdown-item-icon">
                                <?php $this->getAssets()->renderSvg('edit'); ?>
                            </div>
                            <?php esc_html_e('Edit', 'wp-staging'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!$isMultipartBackup) : ?>
                        <?php if ($isProVersion) :?>
                            <a href="<?php echo esc_url($wpstgRestorePageUrl); ?>" class="wpstg-clone-action"
                               title="<?php esc_attr_e('Download restore tool', 'wp-staging'); ?>">
                                <div class="wpstg-dropdown-item-icon">
                                    <?php $this->getAssets()->renderSvg('restore-tool'); ?>
                                </div>
                                <?php esc_html_e('Get Restore Tool', 'wp-staging'); ?>
                            </a>
                        <?php else : ?>
                            <a href="javascript:void(0)" class="wpstg-pro-clone-feature wpstg-clone-action"
                               title="<?php esc_attr_e('Download restore tool', 'wp-staging'); ?>">
                                <div class="wpstg-dropdown-item-icon">
                                    <?php $this->getAssets()->renderSvg('restore-tool'); ?>
                                </div>
                                <?php esc_html_e('Get Restore Tool', 'wp-staging'); ?>
                                <span>(Pro)</span>
                            </a>
                        <?php endif; ?>
                    <?php endif;?>
                    <a href="javascript:void(0)" class="wpstg-remove-clone wpstg-clone-action wpstg-delete-backup"
                       data-name="<?php echo esc_attr($backupName); ?>"
                       data-md5="<?php echo esc_attr($backup->md5BaseName); ?>"
                       title="<?php esc_attr_e('Delete this backup. This action can not be undone!', 'wp-staging'); ?>">
                        <div class="wpstg-dropdown-item-icon">
                            <?php $this->getAssets()->renderSvg('trash'); ?>
                        </div>
                        <?php esc_html_e('Delete', 'wp-staging'); ?>
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
            <?php if ($createdAt) : ?>
                <li>
                    <strong><?php $isCorrupt ? esc_html_e('Last modified:', 'wp-staging') : esc_html_e('Created on:', 'wp-staging'); ?></strong>
                    <?php
                    $date = new DateTime();
                    $date->setTimestamp($createdAt);
                    echo esc_html($this->transformToWpFormat($date));
                    ?>
                </li>
            <?php endif; ?>
            <?php if ($notes) : ?>
                <li>
                    <strong><?php esc_html_e('Notes:', 'wp-staging'); ?></strong><br/>
                    <div class="backup-notes">
                        <?php
                            $notes = str_replace(['\\"', "\\'"], ['"', "'"], $notes);
                            echo Escape::escapeHtml(nl2br($notes));
                        ?>
                    </div>
                </li>
            <?php endif; ?>
            <li>
                <strong><?php esc_html_e('Size: ', 'wp-staging'); ?></strong>
                <?php echo $requires64Bit ? '<b class="wpstg--red"> 2GB+ </b>' : esc_html($size); ?>
            </li>
            <?php if (!$isCorrupt) : ?>
                <li class="single-backup-includes">
                    <strong><?php esc_html_e('Contains: ', 'wp-staging'); ?></strong>
                    <?php
                    $isExportingDatabase            = $backup->isExportingDatabase;
                    $isExportingPlugins             = $backup->isExportingPlugins;
                    $isExportingMuPlugins           = $backup->isExportingMuPlugins;
                    $isExportingThemes              = $backup->isExportingThemes;
                    $isExportingUploads             = $backup->isExportingUploads;
                    $isExportingOtherWpContentFiles = $backup->isExportingOtherWpContentFiles;
                    $isExportingOtherWpRootFiles    = $backup->isExportingOtherWpRootFiles;
                    $indexPartSize                  = $backup->indexPartSize;
                    include(__DIR__ . '/modal/partials/backup-contains.php');
                    ?>
                </li>
                <?php if ($automatedBackup) : ?>
                    <li class="wpstg-automated-backup">
                        <img class="wpstg--dashicons wpstg-dashicons-19 wpstg-dashicons-grey wpstg--backup-automated" src="<?php echo esc_url($urlAssets); ?>svg/update.svg"/> <?php esc_html_e('Backup created automatically.', 'wp-staging'); ?>
                    </li>
                <?php endif; ?>
                <?php if ($isLegacy) : ?>
                    <li class="wpstg-legacy-backup">
                        <img class="wpstg--dashicons wpstg-dashicons-19 wpstg-dashicons-grey" src="<?php echo esc_url($urlAssets); ?>svg/cloud-saved.svg"/> <?php esc_html_e('This database backup was generated from an existing legacy WP STAGING Database backup in the .SQL format.', 'wp-staging'); ?>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($isMultipartBackup) : ?>
                <div class="wpstg-tabs-wrapper wpstg-invalid-backup-tabs">
                    <?php include(__DIR__ . '/partials/invalid-backup.php'); ?>
                </div>
            <?php endif; ?>
            <?php
            if ($requires64Bit) {
                $title        = __('Incompatible PHP Version', 'wp-staging');
                $extraMessage = $isContaining2GbFile ? __('This backup contains a file that exceeds 2GB', 'wp-staging') : __('This backup exceeds 2GB', 'wp-staging');
                $description  = sprintf(
                    __('This server uses a 32-bit version of PHP, which cannot read files larger than 2GB. %s and therefore the backup file cannot be extracted. To restore this backup, use a 64-bit version of PHP. Contact WP STAGING support for assistance in extracting this backup on this server.', 'wp-staging'),
                    $extraMessage
                );

                Alert::render($title, $description);
            }

            if (!$isMultipartBackup && !$isValidFileIndex && !$requires64Bit) {
                $title = __('Corrupted Backup', 'wp-staging');
                Alert::render($title, $indexFileError);
            }

            if ($isCorrupt) {
                $title       = __('Corrupted Backup', 'wp-staging');
                $description = __('This backup file is corrupt. Please create a new backup!', 'wp-staging');
                Alert::render($title, $description);
            }

            if ($isUnsupported && !$isCorrupt) {
                $title       = __('Backup Restore Requires Upgrade', 'wp-staging');
                $description = __('This backup was generated on a beta version of WP STAGING and cannot be restored with the current version.', 'wp-staging');
                Alert::render($title, $description);
            }

            if ($backup->isZlibCompressed && !$compressor->supportsCompression()) {
                $title       = __('Compression Not Supported', 'wp-staging');
                $description = sprintf(
                    __('This backup is compressed, but your server does not support compression. Click %s to learn how to create a compressed backup.', 'wp-staging'),
                    '<a href="https://wp-staging.com/how-to-create-compressed-backup-in-wordpress/" target="_blank" rel="noopener">' . __('here', 'wp-staging') . '</a>'
                );
                $buttonText  = __('Learn how to fix it', 'wp-staging');
                $buttonUrl   = 'https://wp-staging.com/how-to-install-and-activate-gzcompress-and-gzuncompress-functions-in-php/';
                Alert::render($title, $description, $buttonText, $buttonUrl);
            } elseif ($backup->isZlibCompressed && $compressor->supportsCompression() && !$compressor->canUseCompression()) {
                $title       = __('Upgrade required', 'wp-staging');
                $description = __('This backup is compressed, you need WP Staging Pro to restore it.', 'wp-staging');
                $buttonText  = __('Get WP Staging Pro', 'wp-staging');
                $buttonUrl   = 'https://wp-staging.com?utm_source=wpstg-license-ui&utm_medium=website&utm_campaign=compressed-backup-restore&utm_id=purchase-key&utm_content=wpstaging';
                Alert::render($title, $description, $buttonText, $buttonUrl);
            }
            ?>
        </ul>
    </div>
</li>
