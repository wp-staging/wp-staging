<?php

/**
 * Plugin Name: Backup Duplicator & Migration - WP STAGING
 * Plugin URI: https://wordpress.org/plugins/wp-staging
 * Description: Backup & Duplicator Plugin - Clone, backup, move, duplicate & migrate websites to staging, backup, and development sites for authorized users only.
 * Version: 2.14.1
 * Requires at least: 3.6+
 * Requires PHP: 5.6
 * Author: WP-STAGING, WPStagingBackup
 * Author URI: https://wp-staging.com/backup-wordpress
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-staging
 * Domain Path: /languages/
 */

if (!defined("WPINC")) {
    die;
}

/**
 * Welcome to WP STAGING.
 *
 * If you're reading this, you are a curious person that likes
 * to understand how things works, and that's awesome!
 *
 * The philosophy of this file is to work on all PHP versions.
 *
 * Before PHP can understand conditionals such as "if, else",
 * it has to parse this file and split it into "tokens". This
 * process is called "lexical analysis", and exists in almost
 * all programming languages.
 *
 * This file uses only syntax that works with all PHP versions,
 * so that any PHP version can parse it and run our version check
 * conditional.
 *
 * Then we add more PHP files to be parsed, making sure they are
 * running in a PHP version capable of parsing the syntax we are using.
 */
if (version_compare(phpversion(), '5.6.0', '>=')) {
    // The absolute path to the main file of this plugin.
    $pluginFilePath = __FILE__;
    include dirname(__FILE__) . '/opcacheBootstrap.php';
    include_once dirname(__FILE__) . '/freeBootstrap.php';
} else {
    if (!function_exists('wpstg_unsupported_php_version')) {
        function wpstg_unsupported_php_version()
        {
            echo '<div class="notice-warning notice is-dismissible">';
            echo '<p style="font-weight: bold;">' . esc_html__('PHP Version not supported') . '</p>';
            echo '<p>' . sprintf(esc_html__('WP STAGING requires PHP %s or higher. Your site is running an outdated version of PHP (%s), which requires an update.', 'wp-staging'), '5.5', esc_html(phpversion())) . '</p>';
            echo '</div>';
        }
    }
    add_action('admin_notices', 'wpstg_unsupported_php_version');
}
