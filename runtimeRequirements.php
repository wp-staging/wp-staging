<?php

/**
 * @var string $pluginFilePath The absolute path to the main file of this plugin.
 */

/**
 * Early bail: Activating another WPSTAGING Plugin.
 *             This is the only scenario where the plugin would be included after "plugins_loaded",
 *             therefore we need to detect earlier, from the context of the request, whether this is going to happen,
 *             to disable this plugin early and bail the bootstrap process to not conflict with the one being activated.
 *
 *             Covers both clicking on the "Activate" button and selecting the "Activate" bulk-action.
 */

if (isset($_REQUEST['action'])) {
    switch ($_REQUEST['action']) :
        case 'activate':
        case 'error_scrape':
            if (isset($_REQUEST['plugin'])) {
                $plugin = (string)wp_unslash(sanitize_text_field($_REQUEST['plugin']));

                $isActivatingWpStaging        = strpos($plugin, 'wp-staging.php') || strpos($plugin, 'wp-staging-pro.php');
                $isActivatingAnotherWpStaging = plugin_basename($plugin) !== plugin_basename($pluginFilePath);

                if ($isActivatingWpStaging && $isActivatingAnotherWpStaging && current_user_can('deactivate_plugin', plugin_basename($pluginFilePath))) {
                    throw new Exception("Activating another WPSTAGING Plugin. Plugin that bailed bootstrapping: $pluginFilePath");
                }
            }
            break;
        case 'activate-selected':
        case 'activate-multi':
            if (isset($_REQUEST['checked'])) {
                $plugins = array_map('sanitize_text_field', (array)wp_unslash($_REQUEST['checked']));

                foreach ($plugins as $i => $plugin) {
                    $isActivatingWpStaging        = strpos($plugin, 'wp-staging.php') || strpos($plugin, 'wp-staging-pro.php');
                    $isActivatingAnotherWpStaging = plugin_basename($plugin) !== plugin_basename($pluginFilePath);

                    if ($isActivatingWpStaging && $isActivatingAnotherWpStaging && current_user_can('deactivate_plugin', plugin_basename($pluginFilePath))) {
                        throw new Exception("Activating another WPSTAGING Plugin. Plugin that bailed bootstrapping: $pluginFilePath");
                    }
                }
            }
            break;
    endswitch;
}

/**
 * Early bail: Another instance of WPSTAGING active.
 */
if (
// WPSTAGING <= 2.7.5
    class_exists('\WPStaging\WPStaging') ||
    // WPSTAGING >= 2.7.6
    class_exists('\WPStaging\Core\WPStaging')
) {
    add_action(is_network_admin() ? 'network_admin_notices' : 'admin_notices', function () {
        echo '<div class="notice-warning notice is-dismissible another-wpstaging-active">';
        echo '<p style="font-weight: bold;">' . esc_html__('WP STAGING Already Active') . '</p>';
        echo '<p>' . esc_html__('Another WP STAGING is already activated, please leave only one instance of the WP STAGING plugin active at the same time.', 'wp-staging') . '</p>';
        echo '</div>';
    });

    throw new Exception("Another instance of WPSTAGING active. Plugin that bailed bootstrapping: $pluginFilePath");
}

/**
 * Early bail: Unsupported WordPress version.
 *             We check on runtime instead of activation so we can display the notice.
 */
if (!version_compare($currentWordPressVersion = get_bloginfo('version'), $minimumWordPressVersion = '4.4', '>=')) {
    add_action(is_network_admin() ? 'network_admin_notices' : 'admin_notices', function () use ($currentWordPressVersion, $minimumWordPressVersion) {
        echo '<div class="notice-warning notice is-dismissible">';
        echo '<p style="font-weight: bold;">' . esc_html__('WP STAGING') . '</p>';
        echo '<p>' . sprintf(esc_html__('WP STAGING requires at least WordPress %s to run. You have WordPress %s.', 'wp-staging'), esc_html($minimumWordPressVersion), esc_html($currentWordPressVersion)) . '</p>';
        echo '</div>';
    });

    throw new Exception("Unsupported WordPress version. Plugin that bailed bootstrapping: $pluginFilePath");
}
