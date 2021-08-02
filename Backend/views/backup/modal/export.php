<?php
/**
 * @var \WPStaging\Framework\Adapter\Directory $directories
 * @var string                                 $urlAssets
 */
?>
<div id="wpstg--modal--backup--new" data-confirmButtonText="<?php esc_attr_e('Start Backup', 'wp-staging') ?>" style="display: none">
    <h3 class="wpstg--swal2-title wpstg-w-100" for="wpstg-backup-name-input"><?php esc_html_e('Create Site Backup', 'wp-staging') ?></h3>
    <input id="wpstg-backup-name-input" name="backup_name" class="wpstg--swal2-input" placeholder="<?php esc_attr_e('Backup Name (Optional)', 'wp-staging') ?>">

    <div class="wpstg-advanced-options" style="text-align: left;">

        <!-- BACKUP CHECKBOXES -->
        <div class="wpstg-advanced-options-site">
            <label>
                <input type="checkbox" name="includedDirectories[]" id="includeMediaLibraryInBackup" value="<?php echo esc_attr($directories['uploads']); ?>" checked/>
                <?php esc_html_e('Backup Media Library', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" name="includedDirectories[]" id="includeThemesInBackup" value="<?php echo esc_attr($directories['themes']); ?>" checked/>
                <?php esc_html_e('Backup Themes', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" name="includedDirectories[]" id="includeMuPluginsInBackup" value="<?php echo esc_attr($directories['muPlugins']); ?>" checked/>
                <?php esc_html_e('Backup Must-Use Plugins', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" name="includedDirectories[]" id="includePluginsInBackup" value="<?php echo esc_attr($directories['plugins']); ?>" checked/>
                <?php esc_html_e('Backup Plugins', 'wp-staging') ?>
            </label>
            <label>
                <input type="checkbox" name="includeOtherFilesInWpContent" id="includeOtherFilesInWpContent" value="true" checked/>
                <?php esc_html_e('Backup Other Files In wp-content', 'wp-staging') ?>
                <div class="wpstg--tooltip" style="position: absolute;">
                <img class="wpstg--dashicons wpstg-dashicons-21" src="<?php echo $urlAssets; ?>svg/vendor/dashicons/info-outline.svg" alt="info" />
                    <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                            <?php esc_html_e('All files in folder wp-content that are not plugins, themes, mu-plugins and uploads. Recommended for full-site backups.', 'wp-staging') ?>
                    </span>
                </div>
            </label>
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" name="export_database" id="includeDatabaseInBackup" value="true" checked/>
                <?php esc_html_e('Backup Database', 'wp-staging') ?>
                <div id="exportUploadsWithoutDatabaseWarning" style="display:none;">
                    <?php esc_html_e('When exporting the Media Library without the Database, the attachments will be migrated but won\'t show up in the media library after import.', 'wp-staging'); ?>
                </div>
            </label>
            <input type="hidden" name="wpContentDir" value="<?php echo esc_attr($directories['wpContent']); ?>"/>
            <input type="hidden" name="wpStagingDir" value="<?php echo esc_attr($directories['wpStaging']); ?>"/>
            <?php unset($directories['wpContent'], $directories['wpStaging']) ?>
            <input type="hidden" name="availableDirectories" value="<?php echo esc_attr(implode('|', $directories)); ?>"/>
        </div>

        <!-- ADVANCED OPTIONS DROPDOWN -->
        <div class="wpstg-advanced-options-dropdown-wrapper">
            <a href="#" class="wpstg--tab--toggle" data-target=".wpstg-advanced-options-dropdown" style="text-decoration: none;">
                <span style="margin-right: .25em">►</span>
                <?php esc_html_e('Advanced Options', 'wp-staging') ?>
            </a>

            <div class="wpstg-advanced-options-dropdown" style="display:none; padding-left: .75em;">
                Advanced options
            </div>
        </div>

    </div>
</div>
