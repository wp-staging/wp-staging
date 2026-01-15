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
    esc_html__('You can ', 'wp-staging') . '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
    esc_url(admin_url('admin.php?page=wpstg-settings')),
    esc_html__('change this limit in the settings', 'wp-staging')
);
$attr = [
    'class' => 'wpstg-banner-warning',
    'style' => 'margin: 15px 0 0;',
];
Alert::render('', $description, '', '', true, $attr);
