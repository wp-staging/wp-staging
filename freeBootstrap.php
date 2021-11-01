<?php

/**
 * The purpose of the pre-bootstrap process is to make sure the environment is able to run
 * the plugin without any errors, such as making sure there are no other WPSTAGING instances
 * active at the same time.
 *
 * It works at a low level, without the autoloader, using anonymous callbacks and local variables
 * to make sure we always use and execute the expected code.
 *
 * Since it uses closures, you can't dequeue those actions, but this is expected.
 *
 * @var string $pluginFilePath The absolute path to the main file of this plugin.
 */

add_action('plugins_loaded', function () use ($pluginFilePath) {
    try {
        require __DIR__ . '/runtimeRequirements.php';
        require_once __DIR__ . '/bootstrap.php';
    } catch (Exception $e) {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WP STAGING: ' . $e->getMessage());
        }
    }
}, 11, 0); // The priority of this hook must be larger than 10 for the runtime requirement check to detect older versions of WPSTAGING.

register_activation_hook($pluginFilePath, function () use ($pluginFilePath) {
    // Prevent WPSTAGING Free activation when Pro is active
    if (is_multisite()) {
        foreach (wp_get_active_network_plugins() as $networkActivePlugin) {
            if (strpos($networkActivePlugin, 'wp-staging-pro.php') !== false) {
                set_site_transient('wpstgActivatingFreeWhileProIsActive', true, 1 * HOUR_IN_SECONDS);
                wp_safe_redirect(self_admin_url('plugins.php'));
                exit;
            }
        }
    }
    foreach (wp_get_active_and_valid_plugins() as $sitewidePlugin) {
        if (strpos($sitewidePlugin, 'wp-staging-pro.php') !== false) {
            // Set a transient that Pro picks up to render a notice to the user.
            set_site_transient('wpstgActivatingFreeWhileProIsActive', true, 1 * HOUR_IN_SECONDS);

            // Redirects to prevent "Plugin could not be activated because it triggered a fatal error notice".
            wp_safe_redirect(self_admin_url('plugins.php'));

            // Prevents the activation of this plugin.
            exit;
        }
    }

    try {
        require __DIR__ . '/runtimeRequirements.php';
        require_once __DIR__ . '/bootstrap.php';
        require_once __DIR__ . '/install.php';
    } catch (Exception $e) {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WP STAGING: ' . $e->getMessage());
        }
    }
});

register_deactivation_hook($pluginFilePath, function () use ($pluginFilePath) {
    require_once __DIR__ . '/Deactivate.php';
    new WPStaging\Deactivate($pluginFilePath);
});
