<?php

/**
 * WordPress installations with OPCache enabled might bootstrap in an
 * incoherent state from PHP and the Filesystem, causing fatal errors if
 * upgrading to a newer version where a file has been removed.
 *
 * This file clears OPCache in this plugin if needed.
 *
 * WordPress 5.5+ handles OPCache invalidation depending on
 * whether \WP_Upgrader::run is called with the "clear_destination"
 * parameter set to true.
 *
 * @var string $pluginFilePath
 */
global $wp_version;

// Early bail: WordPress 5.5+ already handles OPCache invalidation on plugin updates.
if (version_compare($wp_version, '5.5', '>=')) {
    return;
}

$filename = isset($_SERVER['SCRIPT_FILENAME']) ? sanitize_text_field($_SERVER['SCRIPT_FILENAME']) : '';

// Ported from WordPress 5.5 wp_opcache_invalidate
$canInvalidate = function_exists('opcache_invalidate')
                 && (!ini_get('opcache.restrict_api') || stripos(realpath($filename), ini_get('opcache.restrict_api')) === 0);

// Early bail: OPCache not enabled, or we can't clear it.
if (!$canInvalidate) {
    if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
        error_log('WP STAGING: Can not clear OPCache.');
    }

    return;
}

/*
 * When a site has OPCache enabled, it will cache the compiled "opcode" of this PHP file and all other PHP files.
 *
 * It doesn't cache, however, the result returned by the functions.
 *
 * We leverage this to run a runtime check between the version in this PHP file and
 * the filesystem. The only possible scenario they can be different, on a regular
 * distributed plugin, is if the variable in PHP is opcached to a different version
 * from what's in the filesystem.
 *
 * We use the "Version" from the headers of the main file of the plugin to compare.
 */
$runtimeVersionDifferentFromBuildVersion = get_file_data($pluginFilePath, ['Version' => 'Version'])['Version'] !== '2.14.1';
$lastCheckHappenedAfterInterval          = current_time('timestamp') > (int)get_site_transient('wpstg.bootstrap.opcache.lastCleared') + 5 * MINUTE_IN_SECONDS;

$shouldClearOpCache = apply_filters('wpstg.bootstrap.opcache.shouldClear', $runtimeVersionDifferentFromBuildVersion && $lastCheckHappenedAfterInterval);

if ($shouldClearOpCache) {
    set_site_transient('wpstg.bootstrap.opcache.lastCleared', current_time('timestamp'), 1 * HOUR_IN_SECONDS);

    $start = microtime(true);

    clearstatcache(true);

    try {
        $it = new RecursiveDirectoryIterator(dirname($pluginFilePath));
    } catch (Exception $e) {
        // DirectoryIterator will throw if this plugin folder is not readable.
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WPSTG failed to clear OPCache because the folder does not exist or is not readable. Exception: ' . $e->getMessage());
        }

        return;
    }

    $it = new RecursiveIteratorIterator($it);

    $success  = 0;
    $failures = 0;

    /** @var SplFileInfo $fileInfo */
    foreach ($it as $fileInfo) {
        if (
            $fileInfo->isFile()
            && !$fileInfo->isLink()
            && $fileInfo->getExtension() === 'php'
        ) {
            if (opcache_invalidate($fileInfo->getRealPath(), false)) {
                $success++;
            } else {
                $failures++;
            }
        }
    }

    add_action('admin_notices', function () use ($pluginFilePath, $start) {
        echo '<div class="notice-warning notice is-dismissible">';
        echo '<p style="font-weight: bold;">' . esc_html__('WP STAGING OPCache') . '</p>';
        echo '<p>' . wp_kses_post(__(sprintf('WP STAGING detected that the OPCache was outdated and automatically cleared the OPCache for the <strong>%s</strong> folder to prevent issues. This operation took %s seconds.', plugin_basename($pluginFilePath), number_format(microtime(true) - $start, 4)))) . '</p>';
        echo '</div>';
    });

    if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
        error_log(sprintf('%s files were cleared from OPCache in %s seconds', $success, microtime(true) - $start));
        if (!empty($failures)) {
            error_log(sprintf('WP STAGING could not clear %s files from the OpCache cache upon activation. There may be inconsistencies.', $failures));
        }
    }
}
