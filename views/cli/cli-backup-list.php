<?php
/**
 * CLI Backup List - Partial template for backup selection in CLI modal step 3
 *
 * This partial is used both in the initial modal render and for AJAX refresh
 * after a new backup is created.
 *
 * @var array  $backups    Array of available backups
 * @var string $urlAssets  URL to assets directory
 */
?>
<?php if (!empty($backups)) : ?>
    <div class="wpstg-cli-backup-list-wrapper">
        <div class="wpstg-cli-backup-list-header">
            <span class="wpstg-cli-backup-col-select"></span>
            <span class="wpstg-cli-backup-col-name"><?php echo esc_html__('Backup Name', 'wp-staging'); ?></span>
            <span class="wpstg-cli-backup-col-date"><?php echo esc_html__('Created', 'wp-staging'); ?></span>
            <span class="wpstg-cli-backup-col-size"><?php echo esc_html__('Size', 'wp-staging'); ?></span>
            <span class="wpstg-cli-backup-col-contents"><?php echo esc_html__('Contents', 'wp-staging'); ?></span>
        </div>
        <div class="wpstg-cli-backup-list" id="wpstg-cli-backup-list">
            <?php
            $isFirst = true;
            foreach ($backups as $backup) :
                if ($backup->isCorrupt || $backup->isLegacy) {
                    continue;
                }

                $backupFileName = $backup->name;
                ?>
                <label class="wpstg-cli-backup-item<?php echo $isFirst ? ' wpstg-cli-backup-item-selected' : ''; ?>" data-backup-url="<?php echo esc_attr($backup->downloadUrl); ?>" data-backup-name="<?php echo esc_attr($backupFileName); ?>">
                    <span class="wpstg-cli-backup-col-select">
                        <input type="radio" name="wpstg-cli-backup-selection" value="<?php echo esc_attr($backup->md5BaseName); ?>" <?php echo $isFirst ? 'checked' : ''; ?>>
                    </span>
                    <span class="wpstg-cli-backup-col-name" title="<?php echo esc_attr($backup->backupName); ?>">
                        <?php echo esc_html($backup->backupName); ?>
                    </span>
                    <span class="wpstg-cli-backup-col-date">
                        <?php echo esc_html($backup->dateCreatedFormatted); ?>
                    </span>
                    <span class="wpstg-cli-backup-col-size">
                        <?php echo esc_html(size_format($backup->size, 1)); ?>
                    </span>
                    <span class="wpstg-cli-backup-col-contents">
                        <?php if ($backup->isExportingDatabase) : ?>
                            <span class="wpstg--tooltip wpstg-cli-backup-icon">
                                <img src="<?php echo esc_url($urlAssets); ?>svg/database.svg" alt="Database" />
                                <span class="wpstg--tooltiptext"><?php echo esc_html__('Database', 'wp-staging'); ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($backup->isExportingPlugins) : ?>
                            <span class="wpstg--tooltip wpstg-cli-backup-icon">
                                <img src="<?php echo esc_url($urlAssets); ?>svg/admin-plugins.svg" alt="Plugins" />
                                <span class="wpstg--tooltiptext"><?php echo esc_html__('Plugins', 'wp-staging'); ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($backup->isExportingThemes) : ?>
                            <span class="wpstg--tooltip wpstg-cli-backup-icon">
                                <img src="<?php echo esc_url($urlAssets); ?>svg/layout.svg" alt="Themes" />
                                <span class="wpstg--tooltiptext"><?php echo esc_html__('Themes', 'wp-staging'); ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($backup->isExportingUploads) : ?>
                            <span class="wpstg--tooltip wpstg-cli-backup-icon">
                                <img src="<?php echo esc_url($urlAssets); ?>svg/images-alt.svg" alt="Uploads" />
                                <span class="wpstg--tooltiptext"><?php echo esc_html__('Uploads', 'wp-staging'); ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($backup->isExportingMuPlugins) : ?>
                            <span class="wpstg--tooltip wpstg-cli-backup-icon">
                                <img src="<?php echo esc_url($urlAssets); ?>svg/plugins-checked.svg" alt="MU Plugins" />
                                <span class="wpstg--tooltiptext"><?php echo esc_html__('Must-Use Plugins', 'wp-staging'); ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($backup->isExportingOtherWpContentFiles) : ?>
                            <span class="wpstg--tooltip wpstg-cli-backup-icon">
                                <img src="<?php echo esc_url($urlAssets); ?>svg/admin-generic.svg" alt="WP Content" />
                                <span class="wpstg--tooltiptext"><?php echo esc_html__('Other files in wp-content', 'wp-staging'); ?></span>
                            </span>
                        <?php endif; ?>
                        <?php if ($backup->isExportingOtherWpRootFiles) : ?>
                            <span class="wpstg--tooltip wpstg-cli-backup-icon">
                                <img src="<?php echo esc_url($urlAssets); ?>svg/root-folder.svg" alt="WP Root" />
                                <span class="wpstg--tooltiptext"><?php echo esc_html__('Other files in WP root folder', 'wp-staging'); ?></span>
                            </span>
                        <?php endif; ?>
                    </span>
                </label>
                <?php
                $isFirst = false;
            endforeach;
            ?>
        </div>
    </div>
<?php else : ?>
    <div class="wpstg-cli-no-backups wpstg-cli-no-backups-warning">
        <p>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-cli-warning-icon">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <?php echo esc_html__('No backups found. Create a backup first, then come back here to get the command to restore the backup on your new local Docker site.', 'wp-staging'); ?>
        </p>
    </div>
<?php endif; ?>
