<?php
/**
 * This file is hooked as the \register_activation_hook of the plugin,
 * therefore it runs as a standalone script that needs to be bootstrapped.
 *
 * @var string $pluginFilePath The absolute path to the main file of this plugin.
 */

use WPStaging\Backend\Optimizer\Optimizer;
use WPStaging\Core\Cron\Cron;
use WPStaging\Core\DTO\Settings;
use WPStaging\Core\Utils\Htaccess;

/**
 * Register Cron Events
 */
$cron = (new Cron)->scheduleEvent();

/**
 * Install the Optimizer
 */
$optimizer = (new Optimizer)->installOptimizer();

/**
 * Add the transient to redirect for class Welcome (Not for multisites) and not for Pro version
 */
if (!defined('WPSTGPRO_VERSION')) {
    set_transient('wpstg_activation_redirect', true, 3600);
}

/**
 * Create Htaccess
 */
$htaccess = new Htaccess();
if (extension_loaded('litespeed')) {
    $htaccess->createLitespeed(ABSPATH . '.htaccess');
}

/**
 * Set default values for settings
 */
$settings = (new Settings())->setDefault();

/**
 * Add plugin install for free or pro version in wp options table.
 * If that option already exists do not overwrite it to always keep it
 */
if (defined('WPSTGPRO_VERSION')) {
    add_option('wpstgpro_install_date', date('Y-m-d h:i:s'));
} else {
    add_option('wpstg_free_install_date', date('Y-m-d h:i:s'));
}

// @deprecated since 13.10.2022 Remove in 2023
add_option('wpstg_installDate', date('Y-m-d h:i:s'));

/**
 * Register the Cron Events for Scheduled Backups
 */
WPStaging\Core\WPStaging::make(\WPStaging\Backup\BackupScheduler::class)->reCreateCron();
