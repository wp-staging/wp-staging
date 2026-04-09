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
<div class="wpstg-callout wpstg-callout-info" style="margin: 20px 0 0;">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
    <div class="wpstg-text-sm" style="flex: 1;">
        <div class="wpstg-backup-filters-toggle" style="display: flex; align-items: center; justify-content: space-between; cursor: pointer;">
            <span class="wpstg-m-0 wpstg-text-sm"><?php echo esc_html__('Some files are excluded from backups by default.', 'wp-staging'); ?></span>
            <svg class="wpstg-backup-filters-chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-left: 8px; transition: transform 0.15s ease;"><path d="m6 9 6 6 6-6"/></svg>
        </div>
        <div class="wpstg-backup-filters-details" style="display: none;">
            <ul class="wpstg-m-0 wpstg-mt-1" style="list-style: none; padding: 0; font-size: inherit;">
                <li><?php echo wp_kses_post(sprintf(__('&bull; Files with these extensions: %s', 'wp-staging'), $extensionsList)); ?></li>
                <li><?php echo wp_kses_post(sprintf(__('&bull; Files larger than <strong>%s</strong>', 'wp-staging'), esc_html($maxFileSizeDisplay))); ?></li>
                <?php if ($zipMaxSizeDisplay) : ?>
                    <li><?php echo wp_kses_post(sprintf(__('&bull; <code style="font-size:inherit">.zip</code> files larger than <strong>%s</strong>', 'wp-staging'), esc_html($zipMaxSizeDisplay))); ?></li>
                <?php endif; ?>
            </ul>
            <p class="wpstg-m-0 wpstg-mt-2">
                <a href="https://wp-staging.com/docs/actions-and-filters/#Exclude_a_file_extension_from_backup" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Customize these settings', 'wp-staging'); ?></a>
            </p>
        </div>
    </div>
</div>
<script>
document.addEventListener('click', function(e) {
    var toggle = e.target.closest('.wpstg-backup-filters-toggle');
    if (!toggle) return;
    var details = toggle.nextElementSibling;
    var chevron = toggle.querySelector('.wpstg-backup-filters-chevron');
    if (details && details.classList.contains('wpstg-backup-filters-details')) {
        var isHidden = details.style.display === 'none';
        details.style.display = isHidden ? 'block' : 'none';
        if (chevron) chevron.style.transform = isHidden ? 'rotate(180deg)' : '';
    }
});
</script>
