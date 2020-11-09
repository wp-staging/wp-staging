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
    define('WPSTG_OPTIMIZER_MUVERSION', 1.3);
}

if (file_exists(__DIR__ . '/wp-staging-pro.php')) {
    /** WPSTAGING Pro Constants */

    // WP STAGING version number
    if (!defined('WPSTGPRO_VERSION')) {
        define('WPSTGPRO_VERSION', '3.1.3');
    }

    // Compatible up to WordPress Version
    if (!defined('WPSTG_COMPATIBLE')) {
        define('WPSTG_COMPATIBLE', '5.5.3');
    }
} else {
    /** WPSTAGING Free Constants */

    // WP STAGING version number
    if (!defined('WPSTG_VERSION')) {
        define('WPSTG_VERSION', '2.7.8');
    }

    // Compatible up to WordPress Version
    if (!defined('WPSTG_COMPATIBLE')) {
        define('WPSTG_COMPATIBLE', '5.5.2');
    }
}
