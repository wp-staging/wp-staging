<?php

use WPStaging\Framework\Facades\Hooks;

$extensions = (array) Hooks::applyFilters('wpstg.export.files.ignore.file_extension', [
    'wpstg',
    'gz',
    'tmp',
]);
$extensions        = array_unique($extensions);
$maxFileSize       = (int) Hooks::applyFilters('wpstg.export.files.ignore.file_bigger_than', 200 * MB_IN_BYTES);
$extensionMaxSizes = (array) Hooks::applyFilters('wpstg.export.files.ignore.file_extension_bigger_than', [
    'zip' => 50 * MB_IN_BYTES,
]);

// Format extensions for display
$extensionsFormatted = array_map(function ($ext) {
    return '<code style="font-size:inherit">.' . esc_html($ext) . '</code>';
}, $extensions);

$extensionsList     = implode(', ', $extensionsFormatted);
$maxFileSizeDisplay = size_format($maxFileSize);
$zipMaxSizeDisplay  = isset($extensionMaxSizes['zip']) ? size_format((int)$extensionMaxSizes['zip']) : false;
?>
<div class="wpstg-backup-filters-note wpstg-mt-2.5">
    <button type="button" class="dark:hover:wpstg-text-slate-200 dark:wpstg-text-slate-400 focus-visible:wpstg-outline-none focus-visible:wpstg-ring-2 hover:wpstg-text-gray-700 wpstg-backup-filters-toggle wpstg-bg-transparent wpstg-border-0 wpstg-cursor-pointer wpstg-flex wpstg-gap-1.5 wpstg-items-start wpstg-p-0 wpstg-text-[12px] wpstg-text-left wpstg-w-full" aria-expanded="false">
        <span class="wpstg-backup-filters-icon wpstg-relative wpstg-top-px wpstg-shrink-0 wpstg-text-gray-400 dark:wpstg-text-slate-500" aria-hidden="true">
            <svg class="dark:wpstg-text-slate-500 wpstg-h-4 wpstg-w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4"/>
                <path d="M12 8h.01"/>
            </svg>
        </span>
        <span class="dark:wpstg-text-gray-400 wpstg-items-center wpstg-leading-5 wpstg-text-gray-400"><?php echo esc_html__('Default exclusions: cache files, logs, temporary files, and backup archives are skipped.', 'wp-staging'); ?></span>
        <svg class="wpstg-backup-filters-chevron wpstg-ml-auto wpstg-mt-px wpstg-shrink-0 wpstg-text-slate-450 wpstg-transition-transform dark:wpstg-text-slate-500" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>
    <div class="wpstg-backup-filters-details wpstg-ml-5 wpstg-mt-2 wpstg-text-xs wpstg-leading-5 wpstg-text-slate-550 dark:wpstg-text-slate-400 [&_a]:wpstg-text-blue-600 dark:[&_a]:wpstg-text-blue-300" hidden>
        <ul class="wpstg-mb-1.5 wpstg-mt-0 wpstg-pl-4">
            <li><?php echo wp_kses_post(sprintf(__('Files with these extensions: %s', 'wp-staging'), $extensionsList)); ?></li>
            <li><?php echo wp_kses_post(sprintf(__('Files larger than <strong>%s</strong>', 'wp-staging'), esc_html($maxFileSizeDisplay))); ?></li>
            <?php if ($zipMaxSizeDisplay) : ?>
                <li><?php echo wp_kses_post(sprintf(__('<code style="font-size:inherit">.zip</code> files larger than <strong>%s</strong>', 'wp-staging'), esc_html($zipMaxSizeDisplay))); ?></li>
            <?php endif; ?>
        </ul>
        <a href="https://wp-staging.com/docs/actions-and-filters/#Exclude_a_file_extension_from_backup" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Customize these settings', 'wp-staging'); ?></a>
    </div>
</div>
