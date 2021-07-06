<?php

use WPStaging\Pro\Backup\Task\Tasks\JobImport\RestoreRequirementsCheckTask;

/**
 * @var \WPStaging\Framework\TemplateEngine\TemplateEngine $this
 * @var \WPStaging\Pro\Backup\Entity\ListableBackup        $backup
 * @var string                                             $urlAssets
 */
$name            = $backup->backupName;
$notes           = $backup->notes;
$createdAt       = $backup->dateCreatedTimestamp;
$uploadedAt      = $backup->dateUploadedTimestamp;
$size            = $backup->size;
$id              = $backup->id;
$automatedBackup = $backup->automatedBackup;
$legacy          = $backup->legacy;

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
?>
<li id="<?php echo esc_attr($id) ?>" class="wpstg-clone wpstg-backup" data-md5="<?php echo esc_attr($backup->md5BaseName); ?>" data-name="<?php echo esc_attr($backup->backupName); ?>">

    <div class="wpstg-clone-header">
        <span class="wpstg-clone-title">
            <?php echo esc_html($name); ?>
        </span>
        <div class="wpstg-clone-actions">
            <div class="wpstg-dropdown wpstg-action-dropdown">
                <a href="#" class="wpstg-dropdown-toggler transparent">
                    <?php _e("Actions", "wp-staging"); ?>
                    <span class="wpstg-caret"></span>
                </a>
                <div class="wpstg-dropdown-menu">
                    <?php if (!$legacy) : ?>
                    <a href="#" class="wpstg-clone-action wpstg--backup--restore"
                       data-filePath="<?php echo esc_attr($backup->relativePath) ?>"
                       data-title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging') ?>"
                       title="<?php esc_attr_e('Restore and overwrite current website according to the contents of this backup.', 'wp-staging') ?>">
                        <?php esc_html_e('Restore', 'wp-staging') ?>
                    </a>
                    <?php endif ?>
                    <a download
                       href="<?php echo esc_url($downloadUrl ?: '') ?>" class="wpstg--backup--download wpstg-merge-clone wpstg-clone-action"
                       title="<?php esc_attr_e('Download backup file on local system', 'wp-staging') ?>">
                        <?php esc_html_e('Download', 'wp-staging') ?>
                    </a>
                    <?php if (!$legacy) : ?>
                    <a href="#" class="wpstg--backup--edit wpstg-clone-action"
                       data-md5="<?php echo esc_attr($backup->md5BaseName); ?>"
                       data-name="<?php echo esc_attr($name); ?>"
                       data-notes="<?php echo esc_attr($notes); ?>"
                       title="<?php esc_attr_e('Edit backup name and / or notes', 'wp-staging') ?>">
                        <?php esc_html_e('Edit', 'wp-staging') ?>
                    </a>
                    <?php endif ?>
                    <a href="#" class="wpstg-remove-clone wpstg-clone-action wpstg-delete-backup"
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
            <?php if ($isUnsupported) : ?>
                <li class="wpstg-unsupported-backup wpstg--red">
                    <div class="wpstg-exclamation">!</div><strong><?php esc_html_e('This backup was generated on the Beta version of WP STAGING and cannot be restored with the current version.', 'wp-staging') ?></strong><br/>
                </li>
            <?php endif ?>
            <?php if ($createdAt) : ?>
            <li>
                <strong><?php esc_html_e('Created on:', 'wp-staging') ?></strong>
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
                        <?php echo nl2br(esc_html($notes)); ?>
                    </div>
                </li>
            <?php endif ?>
            <li>
                <strong><?php esc_html_e('Size: ', 'wp-staging') ?></strong>
                <?php echo esc_html($size); ?>
            </li>
            <li class="single-backup-includes">
                <strong><?php esc_html_e('Contains: ', 'wp-staging') ?></strong>
                <ul class="wpstg-import-backup-contains wpstg-listing-single-backup">
                    <?php if ($backup->isExportingDatabase) : ?>
                        <li><span class="wpstg--tooltip wpstg-backups-contains">
                            <img class="wpstg--dashicons" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/database.svg" />
                            <div class='wpstg--tooltiptext'>Database</div>
                        </span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingPlugins) : ?>
                        <li><span class="wpstg--tooltip wpstg-backups-contains">
                            <img class="wpstg--dashicons" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/admin-plugins.svg" />
                            <div class='wpstg--tooltiptext'>Plugins</div>
                        </span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingMuPlugins) : ?>
                        <li><span class="wpstg--tooltip wpstg-backups-contains">
                            <img class="wpstg--dashicons" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/plugins-checked.svg" />
                            <div class='wpstg--tooltiptext'>Must-Use Plugins</div>
                        </span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingThemes) : ?>
                        <li><span class="wpstg--tooltip wpstg-backups-contains">
                            <img class="wpstg--dashicons" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/layout.svg" />
                            <div class='wpstg--tooltiptext'>Themes</div>
                        </span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingUploads) : ?>
                        <li><span class="wpstg--tooltip wpstg-backups-contains">
                            <img class="wpstg--dashicons" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/images-alt.svg" />
                            <div class='wpstg--tooltiptext'>Uploads</div>
                        </span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingOtherWpContentFiles) : ?>
                        <li><span class="wpstg--tooltip wpstg-backups-contains">
                            <img class="wpstg--dashicons" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/admin-generic.svg" />
                            <div class='wpstg--tooltiptext'>Other files in wp-content</div>
                        </span></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php if ($automatedBackup) : ?>
                <li style="font-style: italic">
                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg-dashicons-grey" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/database.svg" /> <?php esc_html_e('This database backup was automatically created before pushing a staging site to production.', 'wp-staging') ?>
                </li>
            <?php endif ?>
            <?php if ($legacy) : ?>
                <li style="font-style: italic">
                <img class="wpstg--dashicons wpstg-dashicons-19 wpstg-dashicons-grey" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/cloud-saved.svg" /> <?php esc_html_e('This database backup was generated from an existing legacy WP STAGING Database export in the .SQL format.', 'wp-staging') ?>
                </li>
            <?php endif ?>
        </ul>
    </div>
</li>
