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
 * Add a entry for plugin install in wp options table.
 * If that option already exists not overwrite it.
 */
add_option('wpstg_installDate', date('Y-m-d h:i:s'));
