<?php

/**
 * Plugin Name: WP STAGING WordPress Backup Plugin - Backup Duplicator & Migration
 * Plugin URI: https://wordpress.org/plugins/wp-staging
 * Description: Backup and staging environments, migrating WordPress sites. Update plugins without risk. Full backup and testing suite - 100% unit and end-to-end tested.
 * Version: 4.1.3
 * Requires at least: 3.6+
 * Requires PHP: 7.0
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
if (version_compare(phpversion(), '7.0.0', '>=')) {
    // The absolute path to the main file of this plugin.
    global $pluginFilePath;
    $pluginFilePath = __FILE__;
    include dirname(__FILE__) . '/opcacheBootstrap.php';
    include_once dirname(__FILE__) . '/freeBootstrap.php';
} else {
    if (!function_exists('wpstg_unsupported_php_version')) {
        function wpstg_unsupported_php_version()
        {
            echo '<div class="notice-warning notice is-dismissible">';
            echo '<p style="font-weight: bold;">' . esc_html__('PHP Version not supported', 'wp-staging') . '</p>';
            echo '<p>' . sprintf(esc_html__('WP STAGING requires PHP %s or higher. Your site is running an outdated version of PHP (%s), which requires an update. If you can not upgrade WordPress, install WP STAGING 2.16.0 which supports PHP 5.6.', 'wp-staging'), '7.0', esc_html(phpversion())) . '</p>';
            echo '</div>';
        }
    }

    add_action('wpstg.admin_notices', 'wpstg_unsupported_php_version');
}
