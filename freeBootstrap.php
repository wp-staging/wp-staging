<?php

if (!function_exists('wpstgHandleMissingRequiredFile')) {
    /**
     * @param string $filePath
     */
    function wpstgHandleMissingRequiredFile(string $filePath)
    {
        $errorMessage = sprintf("WP STAGING WARNING: Attempted to require missing file: %s.", esc_html($filePath));
        if (defined('WPSTG_DEBUG') && (bool)WPSTG_DEBUG) {
            error_log($errorMessage);
        }

        if (defined('WPSTGPRO_VERSION')) {
            return;
        }

        add_action('admin_notices', function () use ($errorMessage) {
            $errorMessage = "$errorMessage Please contact support@wp-staging.com for help!";
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($errorMessage) . '</p></div>';
        });

        return;
    }
}

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
$pluginFilePath = empty($pluginFilePath) ? '' : $pluginFilePath;

add_action('plugins_loaded', function () use ($pluginFilePath) {
    // Unused $pluginFilePath: Other code will fail if removed it
    try {
        $files = [
            __DIR__ . '/runtimeRequirements.php',
            __DIR__ . '/bootstrap.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
            } else {
                wpstgHandleMissingRequiredFile($file);
            }
        }
    } catch (Exception $e) {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WP STAGING: ' . $e->getMessage());
        }
    }
}, 11, 0); // The priority of this hook must be larger than 10 for the runtime requirement check to detect older versions of WPSTAGING.

register_activation_hook($pluginFilePath, function () use ($pluginFilePath) {
    // Unused $pluginFilePath: Other code will fail if removed it

    try {
        $files = [
            __DIR__ . '/runtimeRequirements.php',
            __DIR__ . '/bootstrap.php',
            __DIR__ . '/install.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                require_once $file;
            } else {
                wpstgHandleMissingRequiredFile($file);
            }
        }
    } catch (Exception $e) {
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WP STAGING: ' . $e->getMessage());
        }
    }
});

register_deactivation_hook($pluginFilePath, function () use ($pluginFilePath) {
    if (!class_exists('WPStaging\Deactivate')) {
        $file = __DIR__ . '/Deactivate.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            wpstgHandleMissingRequiredFile($file);
        }
    }

    new WPStaging\Deactivate($pluginFilePath);
});
