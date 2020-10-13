<?php


namespace WPStaging;

// WP STAGING version number
if (!defined('WPSTG_VERSION')) {
    define('WPSTG_VERSION', '3.1.2rc');
}

// Compatible up to WordPress Version
if (!defined('WPSTG_COMPATIBLE')) {
    define('WPSTG_COMPATIBLE', '5.5.1');
}

if (!defined('WPSTG_PLUGIN_SLUG')) {
    define('WPSTG_PLUGIN_SLUG', 'wp-staging');
}