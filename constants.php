<?php

// Absolute path to plugin dir /var/www/.../plugins/wp-staging(-pro)
if (!defined('WPSTG_PLUGIN_DIR')) {
    define('WPSTG_PLUGIN_DIR', plugin_dir_path(WPSTG_PLUGIN_FILE));
}

// URL of the base folder
if (!defined('WPSTG_PLUGIN_URL')) {
    define('WPSTG_PLUGIN_URL', plugin_dir_url(WPSTG_PLUGIN_FILE));
}

// Expected version number of the must-use plugin 'optimizer'. Used for automatic updates of the mu-plugin
if (!defined('WPSTG_OPTIMIZER_MUVERSION')) {
    define('WPSTG_OPTIMIZER_MUVERSION', 1.4);
}

if (!defined('WPSTG_PLUGIN_SLUG')) {
    define('WPSTG_PLUGIN_SLUG', dirname(WPSTG_PLUGIN_FILE));
}

if (!defined('WPSTG_PLUGIN_DOMAIN')) {
    // An identifier that is the same both for WPSTAGING Free and WPSTAGING Pro
    define('WPSTG_PLUGIN_DOMAIN', 'wp-staging');
}
