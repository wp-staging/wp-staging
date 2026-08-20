<?php

if (!function_exists('wpstgHandleMissingRequiredFile')) {



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













$pluginFilePath = empty($pluginFilePath) ? '' : $pluginFilePath;

$commonBootstrap = __DIR__ . '/commonBootstrap.php';
if (file_exists($commonBootstrap)) {
    require_once $commonBootstrap;
} else {
    wpstgHandleMissingRequiredFile($commonBootstrap);
}

add_action('plugins_loaded', function () use ($pluginFilePath) {
 

    try {
        if (function_exists('wpstgShouldSkipBootstrap') && wpstgShouldSkipBootstrap()) {
            return;
        }

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
    } catch (\Throwable $e) {
 
 
 
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WP STAGING could not load: ' . $e->getMessage());
        }
    }
}, 11, 0); 

register_activation_hook($pluginFilePath, function () use ($pluginFilePath) {
 

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
    } catch (\Throwable $e) {
 
 
 
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

    try {
        new WPStaging\Deactivate($pluginFilePath);
    } catch (\Throwable $e) {
 
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WP STAGING: Deactivation cleanup failed: ' . $e->getMessage());
            error_log($e->getTraceAsString());
        }
    }
});
