<?php

use WPStaging\Framework\Facades\UI\Alert;

$settings      = json_decode(json_encode(get_option('wpstg_settings', [])));
$maxFileSizeMb = isset($settings->maxFileSize) ? $settings->maxFileSize : '8';
$description  = sprintf(
    __('Files larger than <strong>%s MB</strong> are skipped during site processing.', 'wp-staging'),
    esc_html((string) $maxFileSizeMb)
);

$description .= '<br>';
$description .= sprintf(
    /* translators: %1$s is the opening link tag, %2$s is the closing link tag */
    __('You can %1$schange this limit in the settings%2$s.', 'wp-staging'),
    '<a href="' . esc_url(admin_url('admin.php?page=wpstg-settings')) . '" target="_blank" rel="noopener noreferrer">',
    '</a>'
);
$attr = [
    'class' => 'wpstg-banner-warning',
    'style' => 'margin: 15px 0 0;',
];
Alert::render('', $description, '', '', true, $attr);
