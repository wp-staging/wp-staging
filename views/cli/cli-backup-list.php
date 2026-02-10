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
    <div class="wpstg-rounded-lg wpstg-border wpstg-border-solid wpstg-border-gray-300 wpstg-bg-white dark:wpstg-border-gray-700 dark:wpstg-bg-dark-boxes">
        <table class="wpstg-w-full wpstg-text-sm wpstg-cli-backup-table-header">
            <thead>
                <tr>
                    <th class="wpstg-h-7 wpstg-px-4 wpstg-text-left wpstg-font-medium wpstg-text-gray-500 dark:wpstg-text-gray-400"><?php echo esc_html__('Backup Name', 'wp-staging'); ?></th>
                    <th class="wpstg-h-7 wpstg-px-4 wpstg-text-left wpstg-font-medium wpstg-text-gray-500 dark:wpstg-text-gray-400"><?php echo esc_html__('Created', 'wp-staging'); ?></th>
                    <th class="wpstg-h-7 wpstg-px-4 wpstg-text-left wpstg-font-medium wpstg-text-gray-500 dark:wpstg-text-gray-400"><?php echo esc_html__('Size', 'wp-staging'); ?></th>
                    <th class="wpstg-h-7 wpstg-px-4 wpstg-text-right wpstg-font-medium wpstg-text-gray-500 dark:wpstg-text-gray-400"><?php echo esc_html__('Contents', 'wp-staging'); ?></th>
                </tr>
            </thead>
        </table>
        <div class="wpstg-cli-backup-table-body-wrapper">
        <table class="wpstg-w-full wpstg-text-sm" id="wpstg-cli-backup-list">
            <tbody>
                <?php
                $isFirst = true;
                foreach ($backups as $backup) :
                    if ($backup->isCorrupt || $backup->isLegacy) {
                        continue;
                    }

                    $backupFileName = $backup->name;
                    $selectedClass = $isFirst ? ' wpstg-cli-backup-item-selected wpstg-bg-blue-50 dark:wpstg-bg-blue-950' : '';
                    ?>
                    <tr class="wpstg-cli-backup-item wpstg-border-b wpstg-border-gray-200 last:wpstg-border-b-0 hover:wpstg-bg-gray-50 wpstg-cursor-pointer wpstg-transition-colors dark:wpstg-border-gray-700 dark:hover:wpstg-bg-gray-800<?php echo esc_attr($selectedClass); ?>" data-backup-url="<?php echo esc_attr($backup->downloadUrl); ?>" data-backup-name="<?php echo esc_attr($backupFileName); ?>">
                        <td class="wpstg-py-2.5 wpstg-px-4">
                            <label class="wpstg-flex wpstg-items-center wpstg-gap-3 wpstg-cursor-pointer">
                                <input type="radio" name="wpstg-cli-backup-selection" value="<?php echo esc_attr($backup->md5BaseName); ?>" <?php echo $isFirst ? 'checked' : ''; ?> class="wpstg-h-4 wpstg-w-4 wpstg-accent-blue-500" />
                                <span class="wpstg-font-medium wpstg-text-gray-900 wpstg-truncate wpstg-max-w-xs dark:wpstg-text-gray-100" title="<?php echo esc_attr($backup->backupName); ?>"><?php echo esc_html($backup->backupName); ?></span>
                            </label>
                        </td>
                        <td class="wpstg-py-2.5 wpstg-px-4 wpstg-text-gray-500 wpstg-whitespace-nowrap dark:wpstg-text-gray-400"><?php echo esc_html($backup->dateCreatedFormatted); ?></td>
                        <td class="wpstg-py-2.5 wpstg-px-4 wpstg-text-gray-500 wpstg-whitespace-nowrap dark:wpstg-text-gray-400"><?php echo esc_html(size_format($backup->size, 1)); ?></td>
                        <td class="wpstg-py-2.5 wpstg-px-4">
                            <div class="wpstg-flex wpstg-items-center wpstg-justify-end wpstg-gap-0.5">
                                <?php if ($backup->isExportingDatabase) : ?>
                                    <span class="wpstg-cli-content-icon wpstg-p-1 wpstg-rounded" data-tooltip="<?php echo esc_attr__('Database', 'wp-staging'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-text-gray-400 dark:wpstg-text-gray-500">
                                            <ellipse cx="12" cy="5" rx="9" ry="3"/>
                                            <path d="M3 5V19A9 3 0 0 0 21 19V5"/>
                                            <path d="M3 12A9 3 0 0 0 21 12"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <?php if ($backup->isExportingPlugins) : ?>
                                    <span class="wpstg-cli-content-icon wpstg-p-1 wpstg-rounded" data-tooltip="<?php echo esc_attr__('Plugins', 'wp-staging'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-text-gray-400 dark:wpstg-text-gray-500">
                                            <rect width="7" height="7" x="3" y="3" rx="1"/>
                                            <rect width="7" height="7" x="14" y="3" rx="1"/>
                                            <rect width="7" height="7" x="14" y="14" rx="1"/>
                                            <rect width="7" height="7" x="3" y="14" rx="1"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <?php if ($backup->isExportingThemes) : ?>
                                    <span class="wpstg-cli-content-icon wpstg-p-1 wpstg-rounded" data-tooltip="<?php echo esc_attr__('Themes', 'wp-staging'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-text-gray-400 dark:wpstg-text-gray-500">
                                            <path d="M18.37 2.63 14 7l-1.59-1.59a2 2 0 0 0-2.82 0L8 7l9 9 1.59-1.59a2 2 0 0 0 0-2.82L17 10l4.37-4.37a2.12 2.12 0 1 0-3-3Z"/>
                                            <path d="M9 8c-2 3-4 3.5-7 4l8 10c2-1 6-5 6-7"/>
                                            <path d="M14.5 17.5 4.5 15"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <?php if ($backup->isExportingUploads) : ?>
                                    <span class="wpstg-cli-content-icon wpstg-p-1 wpstg-rounded" data-tooltip="<?php echo esc_attr__('Uploads', 'wp-staging'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-text-gray-400 dark:wpstg-text-gray-500">
                                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                            <circle cx="9" cy="9" r="2"/>
                                            <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <?php if ($backup->isExportingMuPlugins) : ?>
                                    <span class="wpstg-cli-content-icon wpstg-p-1 wpstg-rounded" data-tooltip="<?php echo esc_attr__('MU-Plugins', 'wp-staging'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-text-gray-400 dark:wpstg-text-gray-500">
                                            <path d="M12 2v4"/>
                                            <path d="M12 18v4"/>
                                            <path d="M4.93 4.93l2.83 2.83"/>
                                            <path d="M16.24 16.24l2.83 2.83"/>
                                            <path d="M2 12h4"/>
                                            <path d="M18 12h4"/>
                                            <path d="M4.93 19.07l2.83-2.83"/>
                                            <path d="M16.24 7.76l2.83-2.83"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <?php if ($backup->isExportingOtherWpContentFiles) : ?>
                                    <span class="wpstg-cli-content-icon wpstg-p-1 wpstg-rounded" data-tooltip="<?php echo esc_attr__('wp-content', 'wp-staging'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-text-gray-400 dark:wpstg-text-gray-500">
                                            <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <?php if ($backup->isExportingOtherWpRootFiles) : ?>
                                    <span class="wpstg-cli-content-icon wpstg-p-1 wpstg-rounded" data-tooltip="<?php echo esc_attr__('WP Root', 'wp-staging'); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-text-gray-400 dark:wpstg-text-gray-500">
                                            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php
                    $isFirst = false;
                endforeach;
                ?>
            </tbody>
        </table>
        </div>
    </div>
<?php else : ?>
    <div class="wpstg-rounded-lg wpstg-border wpstg-border-red-200 wpstg-bg-red-50 wpstg-p-4">
        <div class="wpstg-flex wpstg-items-start wpstg-gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="wpstg-text-red-500 wpstg-flex-shrink-0 wpstg-mt-0.5">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <p class="wpstg-text-sm wpstg-text-red-700 wpstg-m-0"><?php echo esc_html__('No backups found. Create a backup first, then come back here to get the command to restore the backup on your new local Docker site.', 'wp-staging'); ?></p>
        </div>
    </div>
<?php endif; ?>
