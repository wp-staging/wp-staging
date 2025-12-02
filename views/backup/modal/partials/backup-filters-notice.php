<?php

use WPStaging\Framework\Facades\UI\Alert;
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
    return '<code>.' . esc_html($ext) . '</code>';
}, $extensions);

$extensionsList     = implode(', ', $extensionsFormatted);
$maxFileSizeDisplay = size_format($maxFileSize);
$zipMaxSizeDisplay  = isset($extensionMaxSizes['zip']) ? size_format((int)$extensionMaxSizes['zip']) : false;

$description        = __('To make Backups faster and smaller, WP Staging skips:', 'wp-staging') . '<br/>';
// Files with extensions
$description .= sprintf(
    __('%1$s Files with these extensions: %2$s', 'wp-staging'),
    '•',
    $extensionsList
) . '<br/>';

// Files larger than max size
$description .= sprintf(
    __('%1$s Files larger than <strong>%2$s</strong>', 'wp-staging'),
    '•',
    $maxFileSizeDisplay
) . '<br/>';

if ($zipMaxSizeDisplay) {
    $description .= sprintf(
        __('%1$s <code>.zip</code> files larger than <strong>%2$s</strong>', 'wp-staging'),
        '•',
        $zipMaxSizeDisplay
    ) . '<br/>';
}

$description .= '<a href="https://wp-staging.com/docs/actions-and-filters/#Exclude_a_file_extension_from_backup" target="_blank" style="margin-left:10px;" rel="noopener noreferrer">' . esc_html__('Customize these settings', 'wp-staging') . '</a>';
$attr = [
    'class' => 'wpstg-banner-warning',
    'style' => 'margin: 20px 0 0;',
];
Alert::render('', $description, '', '', true, $attr);
