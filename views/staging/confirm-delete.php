<?php

/**
 * @see \WPStaging\Staging\Ajax\Delete\DeleteConfirm::ajaxConfirm()
 * @var WPStaging\Staging\Dto\StagingSiteDto     $stagingSite
 * @var WPStaging\Framework\Database\TableDto[]  $tables
 * @var bool                                     $isDatabaseConnected
 * @var string                                   $stagingSiteSize
 */

use WPStaging\Framework\Facades\UI\Checkbox;

require_once(WPSTG_VIEWS_DIR . 'job/modal/process.php');

$siteName     = $stagingSite->getSiteName();
$tablePrefix  = $stagingSite->getUsedPrefix();
$databaseName = $stagingSite->getDatabaseName();
$stagingPath  = $stagingSite->getPath();
$stagingUrl   = $stagingSite->getUrl();
$stagingHost  = preg_replace('#^https?://#', '', (string)$stagingUrl);
$folderLabel  = $stagingSite->getDirectoryName();
if (empty($folderLabel)) {
    $folderLabel = basename($stagingPath);
}

$stagingTables = [];
foreach ($tables as $table) {
    if (strpos($table->getName(), $tablePrefix) === 0) {
        $stagingTables[] = $table;
    }
}

/**
 * A staging table holds sensitive data when its name maps to wp_options
 * (settings) or wp_users / wp_usermeta (user accounts). Tag it so the design's
 * amber chip + critical note can flag it.
 */
$sensitiveTag = function ($tableName) use ($tablePrefix) {
    $suffix = substr($tableName, strlen($tablePrefix));
    if ($suffix === 'options') {
        return 'settings';
    }

    if ($suffix === 'users' || $suffix === 'usermeta') {
        return 'users';
    }

    return '';
};

$wpstgIcons = [
    'trash'     => 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0',
    'warn'      => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    'check'     => 'M4.5 12.75l6 6 9-13.5',
    'server'    => 'M21.75 17.25v-.228a4.5 4.5 0 00-.12-1.03l-2.268-9.64a3.375 3.375 0 00-3.285-2.602H7.923a3.375 3.375 0 00-3.285 2.602l-2.268 9.64a4.5 4.5 0 00-.12 1.03v.228m19.5 0a3 3 0 01-3 3H5.25a3 3 0 01-3-3m19.5 0a3 3 0 00-3-3H5.25a3 3 0 00-3 3m16.5 0h.008v.008h-.008v-.008zm-3 0h.008v.008h-.008v-.008z',
    'database'  => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125',
    'folder'    => 'M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z',
    'clipboard' => 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z',
    'disk'      => 'M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z',
    'archive'   => 'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
    'external'  => 'M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25',
    'x'         => 'M6 18L18 6M6 6l12 12',
    'chevron'   => 'M19.5 8.25l-7.5 7.5-7.5-7.5',
];

/**
 * Render an inline Heroicons-outline glyph. Color is inherited from the parent
 * text color (currentColor); size is set in px.
 */
$wpstgIcon = function ($name, $size = 16, $classes = '', $strokeWidth = '1.75') use ($wpstgIcons) {
    if (!isset($wpstgIcons[$name])) {
        return;
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG built from constant icon paths; all interpolated values are escaped.
    printf(
        '<svg class="%4$s" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="%3$s"></path></svg>',
        (int)$size,
        esc_attr((string)$strokeWidth),
        esc_attr($wpstgIcons[$name]),
        esc_attr($classes)
    );
};
?>

<div class="wpstg-delete-setup-modal wpstg-text-left" role="document"
    data-wpstg-delete-site-name="<?php echo esc_attr($siteName); ?>">

    <button type="button" class="wpstg-delete-modal-close" aria-label="<?php esc_attr_e('Close modal', 'wp-staging'); ?>">
        <?php $wpstgIcon('x', 18); ?>
    </button>

    <header class="wpstg-delete-setup-modal__header">
        <span class="wpstg-delete-header-badge">
            <?php $wpstgIcon('trash', 20); ?>
        </span>
        <div class="wpstg-min-w-0 wpstg-flex-1">
            <h2 id="wpstg-delete-title" class="wpstg-u-m-0 wpstg-text-2xl wpstg-font-bold wpstg-leading-8 wpstg-text-[#001b3d] dark:wpstg-text-slate-100">
                <?php esc_html_e('Permanently delete staging site:', 'wp-staging'); ?>
                <span class="wpstg-delete-site-name"><?php echo esc_html($siteName); ?></span>
            </h2>
            <p class="wpstg-u-m-0 wpstg-mt-1 wpstg-text-base wpstg-leading-6 wpstg-text-[#536579] dark:wpstg-text-slate-400">
                <?php esc_html_e('WP STAGING will delete the selected staging tables and staging files.', 'wp-staging'); ?>
            </p>
        </div>
    </header>

    <div class="wpstg-delete-setup-modal__body">
        <div class="wpstg-delete-setup-modal__main">

            <!-- Target identity — the single staging site being deleted. -->
            <section class="wpstg-delete-section">
                <h3 class="wpstg-u-m-0 wpstg-mb-2.5 wpstg-text-[14px] wpstg-font-bold wpstg-text-gray-800 dark:wpstg-text-slate-100">
                    <?php esc_html_e('Staging site to delete', 'wp-staging'); ?>
                </h3>
                <div class="wpstg-delete-identity-card">
                    <div class="wpstg-flex wpstg-items-center wpstg-gap-2">
                        <?php $wpstgIcon('server', 16, 'wpstg-flex-shrink-0 wpstg-text-red-500 dark:wpstg-text-red-400'); ?>
                        <span class="wpstg-min-w-0 wpstg-truncate wpstg-text-[14px] wpstg-font-bold wpstg-text-gray-900 dark:wpstg-text-slate-100"><?php echo esc_html($siteName); ?></span>
                        <span class="wpstg-delete-pill wpstg-delete-pill--blue"><?php esc_html_e('Staging site', 'wp-staging'); ?></span>
                    </div>
                    <div class="wpstg-mt-2.5 wpstg-grid wpstg-grid-cols-1 wpstg-gap-2.5 sm:wpstg-grid-cols-3">
                        <div class="wpstg-min-w-0">
                            <div class="wpstg-delete-field-label"><?php esc_html_e('URL', 'wp-staging'); ?></div>
                            <div class="wpstg-delete-field-value"><?php echo esc_html($stagingHost); ?></div>
                        </div>
                        <div class="wpstg-min-w-0">
                            <div class="wpstg-delete-field-label"><?php esc_html_e('Database', 'wp-staging'); ?></div>
                            <div class="wpstg-delete-field-value"><?php echo esc_html($databaseName); ?></div>
                        </div>
                        <div class="wpstg-min-w-0">
                            <div class="wpstg-delete-field-label"><?php esc_html_e('Folder', 'wp-staging'); ?></div>
                            <div class="wpstg-delete-field-value"><?php echo esc_html($stagingPath); ?></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Red hero warning + the single green reassurance line. -->
            <section class="wpstg-delete-section">
                <div class="wpstg-delete-hero">
                    <div class="wpstg-flex wpstg-items-start wpstg-gap-3">
                        <span class="wpstg-delete-hero__icon">
                            <?php $wpstgIcon('warn', 16, '', '2.2'); ?>
                        </span>
                        <div class="wpstg-min-w-0">
                            <h3 class="wpstg-u-m-0 wpstg-text-[15px] wpstg-font-bold wpstg-text-red-900 dark:wpstg-text-red-200">
                                <?php esc_html_e('This permanently deletes the staging site', 'wp-staging'); ?>
                            </h3>
                            <p class="wpstg-u-m-0 wpstg-mt-1 wpstg-text-[13px] wpstg-leading-relaxed wpstg-text-red-800 dark:wpstg-text-red-200/80">
                                <?php
                                echo wp_kses_post(sprintf(
                                    /* translators: %s: staging site name (emphasised). */
                                    __('The selected staging tables and files for <span class="wpstg-font-semibold">%s</span> are permanently removed. This cannot be undone in WP STAGING.', 'wp-staging'),
                                    esc_html($siteName)
                                ));
                                ?>
                            </p>
                        </div>
                    </div>
                    <p class="wpstg-delete-hero__reassurance">
                        <?php $wpstgIcon('check', 14, 'wpstg-mt-0.5 wpstg-flex-shrink-0'); ?>
                        <span><?php esc_html_e('Production files and non-staging database tables are never selected.', 'wp-staging'); ?></span>
                    </p>
                </div>
            </section>

            <!-- What gets deleted — collapsed by default; holds the selection. -->
            <section class="wpstg-delete-section">
                <div class="wpstg-delete-accordion">
                    <button type="button" class="wpstg-delete-accordion__toggle" aria-expanded="false" aria-controls="wpstg-delete-what-panel">
                        <?php $wpstgIcon('trash', 18, 'wpstg-flex-shrink-0 wpstg-text-gray-500 dark:wpstg-text-slate-400'); ?>
                        <span class="wpstg-min-w-0 wpstg-flex-1">
                            <span class="wpstg-flex wpstg-items-center wpstg-gap-2">
                                <span class="wpstg-text-[14px] wpstg-font-bold wpstg-text-gray-800 dark:wpstg-text-slate-100"><?php esc_html_e('What gets deleted', 'wp-staging'); ?></span>
                                <span class="wpstg-delete-pill wpstg-delete-pill--gray" data-wpstg-delete-accordion-chip><?php esc_html_e('All selected', 'wp-staging'); ?></span>
                            </span>
                            <span class="wpstg-delete-accordion__subtitle" data-wpstg-delete-accordion-subtitle></span>
                        </span>
                        <?php $wpstgIcon('chevron', 16, 'wpstg-delete-accordion__chevron wpstg-flex-shrink-0 wpstg-text-gray-400'); ?>
                    </button>

                    <div id="wpstg-delete-what-panel" class="wpstg-delete-accordion__panel" hidden>
                        <p class="wpstg-u-m-0 wpstg-text-[12.5px] wpstg-leading-relaxed wpstg-text-gray-500 dark:wpstg-text-slate-400">
                            <?php esc_html_e('Everything is selected by default. Deselect items only if you want to keep specific tables or folders.', 'wp-staging'); ?>
                        </p>

                        <?php if (!$isDatabaseConnected) { ?>
                            <div class="wpstg-delete-db-error">
                                <strong><?php esc_html_e('Cannot connect to the staging database.', 'wp-staging'); ?></strong>
                                <p class="wpstg-u-m-0 wpstg-mt-1">
                                    <?php esc_html_e('The staging tables cannot be listed — they may already be gone, or the database password changed. You can still delete the staging folder below; any remaining tables must be removed manually.', 'wp-staging'); ?>
                                </p>
                            </div>
                        <?php } ?>

                        <?php if ($isDatabaseConnected) { ?>
                            <div class="wpstg-delete-subblock">
                                <div class="wpstg-delete-subblock__label">
                                    <?php $wpstgIcon('database', 15, 'wpstg-text-gray-500 dark:wpstg-text-slate-400'); ?>
                                    <?php esc_html_e('Database tables', 'wp-staging'); ?>
                                </div>
                                <p class="wpstg-u-m-0 wpstg-mb-2 wpstg-text-[12px] wpstg-leading-snug wpstg-text-gray-500 dark:wpstg-text-slate-400">
                                    <?php
                                    echo wp_kses_post(sprintf(
                                        /* translators: %s: staging database table prefix (monospace). */
                                        __('These staging tables use the <span class="wpstg-delete-mono-strong">%s</span> prefix. No production tables are selected.', 'wp-staging'),
                                        esc_html($tablePrefix)
                                    ));
                                    ?>
                                </p>

                                <div class="wpstg-delete-list-head">
                                    <span class="wpstg-delete-list-count" data-wpstg-delete-tablecount></span>
                                    <span class="wpstg-delete-list-actions">
                                        <button type="button" id="wpstg-select-recommended-tables" class="wpstg-delete-select-all"><?php esc_html_e('Select all', 'wp-staging'); ?></button>
                                        <span class="wpstg-delete-list-sep">·</span>
                                        <button type="button" id="wpstg-clear-table-selection" class="wpstg-delete-deselect-all"><?php esc_html_e('Deselect all', 'wp-staging'); ?></button>
                                    </span>
                                </div>

                                <div class="wpstg-delete-table-list">
                                    <?php foreach ($stagingTables as $table) : ?>
                                        <?php $tag = $sensitiveTag($table->getName()); ?>
                                        <label class="wpstg-delete-row wpstg-cursor-pointer">
                                            <?php Checkbox::render('', $table->getName(), $table->getName(), true, ['classes' => 'wpstg-db-table-checkboxes']); ?>
                                            <span class="wpstg-delete-row__name"><?php echo esc_html($table->getName()); ?></span>
                                            <?php if ($tag !== '') : ?>
                                                <span class="wpstg-delete-pill wpstg-delete-pill--amber"><?php echo esc_html($tag); ?></span>
                                            <?php endif; ?>
                                            <span class="wpstg-delete-row__size" data-wpstg-delete-table-size="<?php echo esc_attr((string)(int)$table->getSize()); ?>"><?php echo esc_html($table->getHumanReadableSize()); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <p class="wpstg-delete-note wpstg-delete-note--amber" data-wpstg-delete-sensitive-note hidden>
                                    <?php $wpstgIcon('warn', 13, 'wpstg-mt-0.5 wpstg-flex-shrink-0'); ?>
                                    <span><?php esc_html_e('Some selected tables hold settings or user accounts. Deleting them removes that staging data permanently.', 'wp-staging'); ?></span>
                                </p>
                            </div>
                        <?php } ?>

                        <div class="wpstg-delete-subblock">
                            <div class="wpstg-delete-subblock__label">
                                <?php $wpstgIcon('folder', 15, 'wpstg-text-gray-500 dark:wpstg-text-slate-400'); ?>
                                <?php esc_html_e('Files &amp; folders', 'wp-staging'); ?>
                            </div>
                            <p class="wpstg-u-m-0 wpstg-mb-2 wpstg-font-mono wpstg-text-[11.5px] wpstg-text-gray-500 dark:wpstg-text-slate-400"><?php echo esc_html($stagingPath); ?></p>

                            <div class="wpstg-delete-list-head">
                                <span class="wpstg-delete-list-count" data-wpstg-delete-foldercount></span>
                                <span class="wpstg-delete-list-actions">
                                    <button type="button" id="wpstg-select-folder" class="wpstg-delete-select-all"><?php esc_html_e('Select all', 'wp-staging'); ?></button>
                                    <span class="wpstg-delete-list-sep">·</span>
                                    <button type="button" id="wpstg-deselect-folder" class="wpstg-delete-deselect-all"><?php esc_html_e('Deselect all', 'wp-staging'); ?></button>
                                </span>
                            </div>

                            <div class="wpstg-delete-folder-list">
                                <label class="wpstg-delete-row wpstg-cursor-pointer">
                                    <?php Checkbox::render('deleteDirectory', 'deleteDirectory', '1', true, [], ['deletePath' => urlencode($stagingPath)]); ?>
                                    <?php $wpstgIcon('folder', 15, 'wpstg-flex-shrink-0 wpstg-text-gray-400 dark:wpstg-text-slate-500'); ?>
                                    <span class="wpstg-delete-row__name wpstg-font-semibold"><?php echo esc_html($folderLabel); ?>/</span>
                                    <span class="wpstg-delete-row__size" data-wpstg-delete-folder-size="0"><?php echo empty($stagingSiteSize) ? '' : esc_html($stagingSiteSize); ?></span>
                                </label>
                            </div>

                            <p class="wpstg-delete-note wpstg-delete-note--amber" data-wpstg-delete-file-caution hidden>
                                <?php $wpstgIcon('warn', 13, 'wpstg-mt-0.5 wpstg-flex-shrink-0'); ?>
                                <span><?php esc_html_e('Deleting files is recommended. Keeping the folder can leave an orphaned staging directory that still uses disk space.', 'wp-staging'); ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Deletion summary rail (sticky on desktop). -->
        <aside class="wpstg-delete-setup-modal__summary">
            <div class="wpstg-delete-summary-sticky">
                <div class="wpstg-delete-summary__heading">
                    <?php $wpstgIcon('clipboard', 16, 'wpstg-flex-shrink-0 wpstg-text-blue-600 dark:wpstg-text-blue-400'); ?>
                    <?php esc_html_e('Deletion Summary', 'wp-staging'); ?>
                </div>

                <dl class="wpstg-delete-summary-list">
                    <div class="wpstg-delete-summary-row">
                        <dt><?php $wpstgIcon('server', 13, 'wpstg-delete-summary__icon'); ?><?php esc_html_e('Staging site', 'wp-staging'); ?></dt>
                        <dd><span class="wpstg-font-mono"><?php echo esc_html($siteName); ?></span></dd>
                    </div>
                    <div class="wpstg-delete-summary-divider"></div>
                    <div class="wpstg-delete-summary-row">
                        <dt><?php $wpstgIcon('database', 13, 'wpstg-delete-summary__icon'); ?><?php esc_html_e('Tables', 'wp-staging'); ?></dt>
                        <dd data-wpstg-delete-tables-summary></dd>
                    </div>
                    <div class="wpstg-delete-summary-row">
                        <dt><?php $wpstgIcon('folder', 13, 'wpstg-delete-summary__icon'); ?><?php esc_html_e('Folders', 'wp-staging'); ?></dt>
                        <dd data-wpstg-delete-folders-summary></dd>
                    </div>
                    <div class="wpstg-delete-summary-row">
                        <dt><?php $wpstgIcon('disk', 13, 'wpstg-delete-summary__icon'); ?><?php esc_html_e('Data to delete', 'wp-staging'); ?></dt>
                        <dd><span class="wpstg-font-mono" data-wpstg-delete-size-summary></span></dd>
                    </div>
                </dl>

                <div class="wpstg-delete-backup-card">
                    <div class="wpstg-delete-backup-card__title">
                        <?php $wpstgIcon('archive', 15, 'wpstg-flex-shrink-0 wpstg-text-amber-600 dark:wpstg-text-amber-300'); ?>
                        <?php esc_html_e('Back up first', 'wp-staging'); ?>
                        <span class="wpstg-delete-backup-card__chip"><?php esc_html_e('Recommended', 'wp-staging'); ?></span>
                    </div>
                    <p class="wpstg-u-m-0 wpstg-mt-1.5 wpstg-text-[12px] wpstg-leading-snug wpstg-text-amber-800 dark:wpstg-text-amber-200/85">
                        <?php esc_html_e("Deletion can't be undone. Open the staging site and create a backup before continuing.", 'wp-staging'); ?>
                    </p>
                    <a href="<?php echo esc_url($stagingUrl); ?>" target="_blank" rel="noopener noreferrer" class="wpstg-delete-backup-card__button">
                        <?php $wpstgIcon('external', 13); ?>
                        <?php esc_html_e('Open staging site', 'wp-staging'); ?>
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <footer class="wpstg-delete-setup-modal__footer">
        <label class="wpstg-delete-confirm-row wpstg-cursor-pointer">
            <?php Checkbox::render('wpstg-delete-acknowledgement', 'wpstg-delete-acknowledgement', '1', false, []); ?>
            <span class="wpstg-delete-confirm-row__text">
                <?php
                echo wp_kses_post(sprintf(
                    /* translators: 1: emphasised word "deletes"; 2: staging site name (monospace). */
                    __('I understand this permanently %1$s %2$s and cannot be undone.', 'wp-staging'),
                    '<span class="wpstg-delete-confirm-emphasis">' . esc_html__('deletes', 'wp-staging') . '</span>',
                    '<span class="wpstg-delete-confirm-name">' . esc_html($siteName) . '</span>'
                ));
                ?>
            </span>
        </label>
        <div class="wpstg-delete-footer-actions">
            <button type="button" class="wpstg-delete-modal-cancel wpstg-delete-btn-ghost"><?php esc_html_e('Cancel', 'wp-staging'); ?></button>
            <button type="button" class="wpstg-delete-cta" data-cloneid="<?php echo esc_attr($stagingSite->getCloneId()); ?>" disabled>
                <?php $wpstgIcon('trash', 16, '', '2'); ?>
                <span><?php esc_html_e('Delete Staging Site Permanently', 'wp-staging'); ?></span>
            </button>
        </div>
    </footer>
</div>
