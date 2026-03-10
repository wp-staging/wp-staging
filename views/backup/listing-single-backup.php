<?php

use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Facades\UI\Alert;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Core\Cron\Cron;

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
$isUnsignedBackup       = $backup->isUnsignedBackup;

// Default error message
if (empty($indexFileError)) {
    $indexFileError = __("This backup has an invalid files index.", 'wp-staging');
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
                <div class="wpstg-dropdown-menu wpstg-backup-actions-menu wpstg-w-64 wpstg-rounded-md wpstg-border wpstg-border-solid wpstg-bg-popover wpstg-p-1 wpstg-text-popover-foreground wpstg-shadow-md">
                    <?php if (!$isLegacy && !$isCorrupt && !$requires64Bit) : ?>
                        <div class="wpstg-px-2 wpstg-py-1.5 wpstg-text-[11px] wpstg-uppercase wpstg-tracking-wider wpstg-text-dim-foreground wpstg-font-medium"><?php esc_html_e('Restore', 'wp-staging'); ?></div>
                        <a href="javascript:void(0)" class="wpstg-clone-action wpstg--backup--restore wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground"
                           data-filePath="<?php echo esc_attr($backup->relativePath); ?>"
                           data-title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging'); ?>"
                           title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging'); ?>">
                            <span class="wpstg-h-4 wpstg-w-4 wpstg-text-dim-foreground wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('restore'); ?></span>
                            <?php esc_html_e('Restore', 'wp-staging'); ?>
                        </a>
                        <div class="wpstg--mx-1 wpstg-my-1 wpstg-h-px wpstg-bg-dim"></div>
                    <?php endif; ?>

                    <div class="wpstg-px-2 wpstg-py-1.5 wpstg-text-[11px] wpstg-uppercase wpstg-tracking-wider wpstg-text-dim-foreground wpstg-font-medium"><?php esc_html_e('Manage Backup', 'wp-staging'); ?></div>
                    <?php if (!$isLegacy && !$isCorrupt && !$requires64Bit && $isValidFileIndex) : ?>
                        <a href="javascript:void(0)" class="wpstg--backup--explore wpstg-clone-action wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground"
                           data-filePath="<?php echo esc_attr($backup->relativePath); ?>"
                           data-name="<?php echo esc_attr($backupName); ?>"
                           title="<?php esc_attr_e('Browse the contents of this backup.', 'wp-staging'); ?>">
                            <span class="wpstg-h-4 wpstg-w-4 wpstg-text-dim-foreground wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('folder-new'); ?></span>
                            <?php esc_html_e('Browse Backup', 'wp-staging'); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($isMultipartBackup) : ?>
                        <a href="javascript:void(0)" class="wpstg--backup--download wpstg--backup--download-modal wpstg-merge-clone wpstg-clone-action wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground"
                           data-filePath="<?php echo esc_attr($backup->relativePath); ?>"
                           title="<?php esc_attr_e('Download backup to local system', 'wp-staging'); ?>">
                            <span class="wpstg-h-4 wpstg-w-4 wpstg-text-dim-foreground wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('download'); ?></span>
                            <?php esc_html_e('Download Backup', 'wp-staging'); ?>
                        </a>
                    <?php else : ?>
                        <a <?php echo esc_attr($downloadAttribute);?> href="<?php echo esc_url($downloadUrl ?: ''); ?>" class="wpstg--backup--download wpstg-clone-action wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground"
                           title="<?php esc_attr_e('Download backup to local system', 'wp-staging'); ?>">
                            <span class="wpstg-h-4 wpstg-w-4 wpstg-text-dim-foreground wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('download'); ?></span>
                            <?php esc_html_e('Download Backup', 'wp-staging'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="javascript:void(0)" id="wpstg-copy-backup-url" class="wpstg-clone-action wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground"
                       data-copy-content="<?php echo esc_attr($downloadFileUrl); ?>"
                       title="<?php esc_attr_e('Copy backup link to restore it quickly on another website.', 'wp-staging'); ?>">
                        <span class="wpstg-h-4 wpstg-w-4 wpstg-text-dim-foreground wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('copy-link'); ?></span>
                        <?php esc_html_e('Copy Backup Link', 'wp-staging'); ?>
                    </a>
                    <?php if (!$isLegacy && !$isCorrupt && !$requires64Bit) : ?>
                        <a href="javascript:void(0)" class="wpstg--backup--edit wpstg-clone-action wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground"
                           data-md5="<?php echo esc_attr($backup->md5BaseName); ?>"
                           data-name="<?php echo esc_attr($backupName); ?>"
                           data-notes="<?php echo esc_attr($notes); ?>"
                           title="<?php esc_attr_e('Edit backup name and notes', 'wp-staging'); ?>">
                            <span class="wpstg-h-4 wpstg-w-4 wpstg-text-dim-foreground wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('edit'); ?></span>
                            <?php esc_html_e('Edit Backup Name', 'wp-staging'); ?>
                        </a>
                    <?php endif; ?>
                    <a <?php echo esc_attr($downloadAttribute);?> href="<?php echo esc_url($logUrl ?: ''); ?>" class="wpstg-clone-action wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground" title="<?php esc_attr_e('Download Log', 'wp-staging'); ?>">
                        <span class="wpstg-h-4 wpstg-w-4 wpstg-text-dim-foreground wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('file'); ?></span>
                        <?php esc_html_e('Download Log', 'wp-staging'); ?>
                    </a>

                    <div class="wpstg--mx-1 wpstg-my-1 wpstg-h-px wpstg-bg-dim"></div>

                    <div class="wpstg-px-2 wpstg-py-1.5 wpstg-text-[11px] wpstg-uppercase wpstg-tracking-wider wpstg-text-dim-foreground wpstg-font-medium">Pro</div>
                    <?php if (!$isProVersion) :?>
                        <a href="javascript:void(0)" class="wpstg-pro-clone-feature wpstg-clone-action wpstg-relative wpstg-flex wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none wpstg-opacity-60 wpstg-pointer-events-none"
                           title="<?php echo esc_html__("Upload Backup to Cloud", "wp-staging"); ?>">
                            <span class="wpstg-h-4 wpstg-w-4 wpstg-text-primary wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('upload-cloud'); ?></span>
                            <?php esc_html_e("Upload Backup to Cloud", "wp-staging"); ?>
                            <span class="wpstg-ml-auto wpstg-text-[10px] wpstg-font-semibold wpstg-text-primary">(Pro)</span>
                        </a>
                    <?php else : ?>
                        <?php if (!$isLegacy && !$isCorrupt) : ?>
                            <a href="javascript:void(0)" class="wpstg--backup--remote-upload wpstg-clone-action wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground"
                               data-filePath="<?php echo esc_attr($backup->relativePath); ?>"
                               title="<?php esc_attr_e('Upload to remote storage', 'wp-staging'); ?>">
                                <span class="wpstg-h-4 wpstg-w-4 wpstg-text-primary wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('upload-cloud'); ?></span>
                                <?php esc_html_e('Upload Backup to Cloud', 'wp-staging'); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif;?>
                    <?php if (!$isMultipartBackup) : ?>
                        <?php if ($isProVersion) :?>
                            <a href="<?php echo esc_url($wpstgRestorePageUrl); ?>" class="wpstg-clone-action wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none hover:wpstg-bg-accent hover:wpstg-text-accent-foreground"
                               title="<?php esc_attr_e('Download restore tool', 'wp-staging'); ?>">
                                <span class="wpstg-h-4 wpstg-w-4 wpstg-text-primary wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('restore-tool'); ?></span>
                                <?php esc_html_e('Download Restore Tool', 'wp-staging'); ?>
                            </a>
                        <?php else : ?>
                            <a href="javascript:void(0)" class="wpstg-pro-clone-feature wpstg-clone-action wpstg-relative wpstg-flex wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none wpstg-opacity-60 wpstg-pointer-events-none"
                               title="<?php esc_attr_e('Download restore tool', 'wp-staging'); ?>">
                                <span class="wpstg-h-4 wpstg-w-4 wpstg-text-primary wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('restore-tool'); ?></span>
                                <?php esc_html_e('Download Restore Tool', 'wp-staging'); ?>
                                <span class="wpstg-ml-auto wpstg-text-[10px] wpstg-font-semibold wpstg-text-primary">(Pro)</span>
                            </a>
                        <?php endif; ?>
                    <?php endif;?>

                    <div class="wpstg--mx-1 wpstg-my-1 wpstg-h-px wpstg-bg-dim"></div>

                    <a href="javascript:void(0)" class="wpstg-remove-clone wpstg-clone-action wpstg-delete-backup wpstg-relative wpstg-flex wpstg-cursor-pointer wpstg-select-none wpstg-items-center wpstg-gap-2.5 wpstg-rounded-sm wpstg-px-2 wpstg-py-1.5 wpstg-text-sm wpstg-outline-none wpstg-text-destructive hover:wpstg-bg-destructive/10 hover:wpstg-text-destructive"
                       data-name="<?php echo esc_attr($backupName); ?>"
                       data-md5="<?php echo esc_attr($backup->md5BaseName); ?>"
                       title="<?php esc_attr_e('Delete this backup. This action can not be undone!', 'wp-staging'); ?>">
                        <span class="wpstg-h-4 wpstg-w-4 wpstg-flex wpstg-items-center wpstg-justify-center wpstg-shrink-0"><?php $this->getAssets()->renderSvg('trash'); ?></span>
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
                <?php echo $requires64Bit ? '<b class="wpstg--red"> 2GB+ </b>' : esc_html((string)size_format($size, 2));?>

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
                        <img class="wpstg--dashicons wpstg-dashicons-19 wpstg-dashicons-grey wpstg--backup-automated" src="<?php echo esc_url($urlAssets); ?>svg/update.svg"/> 
                        <?php
                        $message = 'Backup created automatically.';
                        if (!empty($backup->scheduleRecurrence)) {
                            $scheduleDisplay = Cron::getCronDisplayName($backup->scheduleRecurrence);
                            $message = sprintf(__('Backup created automatically (%s).', 'wp-staging'), esc_html($scheduleDisplay));
                        }

                        echo esc_html($message);
                        ?>
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

            $errors = [];
            if ($isCorrupt) {
                $errors[] = __('This backup file is corrupt.', 'wp-staging');
            }

            if (!$isMultipartBackup && !$isValidFileIndex && !$requires64Bit) {
                $errors[] = $indexFileError;
            }

            if ($isUnsignedBackup) {
                $errors[] = __('Backup file couldn’t be signed properly.', 'wp-staging');
            }

            if (!empty($errors)) {
                $title       = __('Corrupted Backup', 'wp-staging');
                $description = implode(' ', $errors);
                $footer      = __('Please create a new backup.', 'wp-staging');

                Alert::render($title, $description . ' ' . $footer);
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
