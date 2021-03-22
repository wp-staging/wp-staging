<?php

/**
 * @var \WPStaging\Framework\TemplateEngine\TemplateEngine $this
 * @var \WPStaging\Pro\Backup\Entity\ListableBackup        $backup
 */
$name            = $backup->backupName;
$downloadUrl     = $backup->downloadUrl;
$notes           = $backup->notes;
$createdAt       = $backup->dateCreatedFormatted;
$size            = $backup->size;
$id              = $backup->id;
$automatedBackup = $backup->automatedBackup;
$legacy          = $backup->legacy;
?>
<li id="<?php echo esc_attr($id) ?>" class="wpstg-clone wpstg-backup">

    <div class="wpstg-clone-header">
        <span class="wpstg-clone-title">
            <?php echo esc_html($name); ?>
        </span>
        <div class="wpstg-clone-actions">
            <div class="wpstg-dropdown wpstg-action-dropdown">
                <a href="#" class="wpstg-dropdown-toggler transparent">
                    <?php _e("Actions", "wp-staging"); ?>
                </a>
                <div class="wpstg-dropdown-menu">
                    <a href="#" class="wpstg-clone-action wpstg--backup--import"
                       data-filePath="<?php echo esc_attr($backup->fullPath) ?>"
                       data-title="<?php esc_attr_e('Import Backup', 'wp-staging') ?>"
                       title="<?php esc_attr_e('Import Backup', 'wp-staging') ?>">
                        <?php esc_html_e('Import', 'wp-staging') ?>
                    </a>
                    <a href="#" class="wpstg--backup--download wpstg-merge-clone wpstg-clone-action"
                       data-md5="<?php echo esc_attr($backup->md5BaseName) ?>"
                       data-url="<?php echo esc_url($downloadUrl ?: '') ?>"
                       data-title="<?php esc_attr_e('Download Backup', 'wp-staging') ?>"
                       data-title-export="<?php esc_attr_e('Exporting Database Tables...', 'wp-staging') ?>"
                       data-btn-cancel-txt="<?php esc_attr_e('CANCEL', 'wp-staging') ?>"
                       data-btn-download-txt="<?php esc_attr_e($downloadUrl ? 'Download' : 'Export & Download', 'wp-staging') ?>"
                       title="<?php esc_attr_e('Download backup file on local system', 'wp-staging') ?>">
                        <?php esc_html_e('Download', 'wp-staging') ?>
                    </a>
                    <a href="#" class="wpstg--backup--edit wpstg-clone-action"
                       data-md5="<?php echo esc_attr($backup->md5BaseName); ?>"
                       data-name="<?php echo esc_attr($name); ?>"
                       data-notes="<?php echo esc_attr($notes); ?>"
                       title="<?php esc_attr_e('Edit backup name and / or notes', 'wp-staging') ?>">
                        <?php esc_html_e('Edit', 'wp-staging') ?>
                    </a>
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
            <li><strong>Id:</strong> <?php esc_html_e($id); ?></li>
            <li>
                <strong><?php esc_html_e('Created on:', 'wp-staging') ?></strong>
                <?php echo esc_html($this->transformToWpFormat(new DateTime($createdAt))); ?>
            </li>
            <?php if ($notes) : ?>
                <li>
                    <strong><?php esc_html_e('Notes:', 'wp-staging') ?></strong><br/>
                    <?php echo esc_html(nl2br($notes)); ?>
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
                        <li><span class="dashicons dashicons-database wpstg--tooltip"><div class='wpstg--tooltiptext'>Database</div></span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingPlugins) : ?>
                        <li><span class="dashicons dashicons-admin-plugins wpstg--tooltip"><div class='wpstg--tooltiptext'>Plugins</div></span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingMuPlugins) : ?>
                        <li><span class="dashicons dashicons-plugins-checked wpstg--tooltip"><div class='wpstg--tooltiptext'>Mu-plugins</div></span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingThemes) : ?>
                        <li><span class="dashicons dashicons-layout wpstg--tooltip"><div class='wpstg--tooltiptext'>Themes</div></span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingUploads) : ?>
                        <li><span class="dashicons dashicons-images-alt wpstg--tooltip"><div class='wpstg--tooltiptext'>Uploads</div></span></li>
                    <?php endif; ?>
                    <?php if ($backup->isExportingOtherWpContentFiles) : ?>
                        <li><span class="dashicons dashicons-admin-generic wpstg--tooltip"><div class='wpstg--tooltiptext'>Other files in wp-content</div></span></li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php if ($automatedBackup) : ?>
                <li style="font-style: italic">
                    <span class="dashicons dashicons-database"></span> <?php esc_html_e('This database backup was automatically created before pushing a staging site to production.', 'wp-staging') ?>
                </li>
            <?php endif ?>
            <?php if ($legacy) : ?>
                <li style="font-style: italic">
                    <span class="dashicons dashicons-hourglass"></span> <?php esc_html_e('This database backup was automatically converted from an existing legacy WPSTAGING Database export in the .SQL format.', 'wp-staging') ?>
                </li>
            <?php endif ?>
        </ul>
    </div>
</li>
