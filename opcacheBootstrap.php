<?php















global $wp_version, $pluginFilePath;

 
if (version_compare($wp_version, '5.5', '>=')) {
    return;
}

$filename = isset($_SERVER['SCRIPT_FILENAME']) ? sanitize_text_field($_SERVER['SCRIPT_FILENAME']) : '';

 
$canInvalidate = function_exists('opcache_invalidate')
                 && (!ini_get('opcache.restrict_api') || stripos(realpath($filename), ini_get('opcache.restrict_api')) === 0);

 
if (!$canInvalidate) {
    if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
        error_log('WP STAGING: Can not clear OPCache.');
    }

    return;
}













$runtimeVersionDifferentFromBuildVersion = get_file_data($pluginFilePath, ['Version' => 'Version'])['Version'] !== '4.12.0';
$lastCheckHappenedAfterInterval          = current_time('timestamp') > (int)get_site_transient('wpstg.bootstrap.opcache.lastCleared') + 5 * MINUTE_IN_SECONDS;

$shouldClearOpCache = apply_filters('wpstg.bootstrap.opcache.shouldClear', $runtimeVersionDifferentFromBuildVersion && $lastCheckHappenedAfterInterval);

if ($shouldClearOpCache) {
    set_site_transient('wpstg.bootstrap.opcache.lastCleared', current_time('timestamp'), 1 * HOUR_IN_SECONDS);

    $start = microtime(true);

    clearstatcache(true);

    try {
        $it = new RecursiveDirectoryIterator(dirname($pluginFilePath));
    } catch (Exception $e) {
 
        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            error_log('WPSTG failed to clear OPCache because the folder does not exist or is not readable. Exception: ' . $e->getMessage());
        }

        return;
    }

    $it = new RecursiveIteratorIterator($it);

    $success  = 0;
    $failures = 0;

 
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
        echo '<p style="font-weight: bold;">' . esc_html__('WP STAGING OPCache', 'wp-staging') . '</p>';
        echo '<p>' . wp_kses_post(
            sprintf(
                __('WP STAGING detected that the OPCache was outdated and automatically cleared the OPCache for the <strong>%s</strong> folder to prevent issues. This operation took %s seconds.', 'wp-staging'),
                plugin_basename($pluginFilePath),
                number_format(microtime(true) - $start, 4)
            )
        ) . '</p>';
        echo '</div>';
    });

    if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
        error_log(sprintf('%s files were cleared from OPCache in %s seconds', $success, microtime(true) - $start));
        if (!empty($failures)) {
            error_log(sprintf('WP STAGING could not clear %s files from the OpCache cache upon activation. There may be inconsistencies.', $failures));
        }
    }
}
