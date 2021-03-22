<?php
/**
 * @var \WPStaging\Framework\Adapter\Directory $directories
 */
?>
<div id="wpstg--modal--backup--new" data-confirmButtonText="<?php esc_attr_e('Start Backup', 'wp-staging') ?>" style="display: none">
    <label for="wpstg-backup-name-input"><?php esc_html_e('Backup & Export', 'wp-staging') ?></label>
    <input id="wpstg-backup-name-input" name="backup_name" class="swal2-input" placeholder="<?php esc_attr_e('Name your backup for better distinction', 'wp-staging') ?>">

    <div class="wpstg-advanced-options" style="text-align: left;">

        <!-- EXPORT CHECKBOXES -->
        <div class="wpstg-advanced-options-site" style="padding-left: .75em;">
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" name="includedDirectories[]" id="includeMediaLibraryInBackup" value="<?php echo esc_attr($directories['uploads']); ?>" checked/>
                <?php esc_html_e('Export Media Library', 'wp-staging') ?>
            </label>
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" name="includedDirectories[]" id="includeThemesInBackup" value="<?php echo esc_attr($directories['themes']); ?>" checked/>
                <?php esc_html_e('Export Themes', 'wp-staging') ?>
            </label>
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" name="includedDirectories[]" id="includeMuPluginsInBackup" value="<?php echo esc_attr($directories['muPlugins']); ?>" checked/>
                <?php esc_html_e('Export Must-Use Plugins', 'wp-staging') ?>
            </label>
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" name="includedDirectories[]" id="includePluginsInBackup" value="<?php echo esc_attr($directories['plugins']); ?>" checked/>
                <?php esc_html_e('Export Plugins', 'wp-staging') ?>
            </label>
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" name="includeOtherFilesInWpContent" id="includeOtherFilesInWpContent" value="true" checked/>
                <?php esc_html_e('Export Other Files In wp-content', 'wp-staging') ?>
                <div class="wpstg--tooltip">
                    <span class="dashicons dashicons-info-outline"></span>
                    <span class="wpstg--tooltiptext wpstg--tooltiptext-backups">
                        <p>
                            <?php esc_html_e('Export files at wp-content that are not plugins, themes, mu-plugins or uploads. Eg: Cache and database drop-ins, etc. Recommended for full-site backups.', 'wp-staging') ?>
                        </p>
                    </span>
                </div>
            </label>
            <label style="display: block;margin: .5em 0;">
                <input type="checkbox" name="export_database" id="includeDatabaseInBackup" value="true" checked/>
                <?php esc_html_e('Export Database', 'wp-staging') ?>
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
