<?php

if (!function_exists('wpstgShouldSkipBootstrap')) {
    function wpstgShouldSkipBootstrap(): bool
    {
        if (defined('WP_INSTALLING') && WP_INSTALLING) {
            return false;
        }

        if (is_admin()) {
            return false;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }

        // WordPress login page: needed for post-restore login prompt and other login hooks.
        $requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        if (strpos($requestUri, '/wp-login.php') !== false) {
            return false;
        }

        // Temporary/auto login links (?wpstg_login=, ?wpstg_staging_login=, ?action=wpstg_*).
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : '';
        if (
            !empty($_GET['wpstg_login']) ||
            !empty($_GET['wpstg_staging_login']) ||
            strpos($action, 'wpstg_') === 0
        ) {
            return false;
        }

        // REST_REQUEST isn't defined at plugins_loaded; resolve prefix dynamically
        // so custom rest_url_prefix filters are respected.
        $restPrefix = '/' . trim(apply_filters('rest_url_prefix', 'wp-json'), '/') . '/';
        if (strpos($requestUri, $restPrefix) !== false) {
            return false;
        }

        if (!empty($_GET['rest_route'])) {
            return false;
        }

        // Non-WP-CLI PHP processes (test runners, deploy tools) also need full bootstrap.
        if (php_sapi_name() === 'cli') {
            return false;
        }

        // Staging sites need full bootstrap: login gate, permission checks, admin bar CSS
        if (get_option('wpstg_is_staging_site') === 'true') {
            return false;
        }

        if (file_exists(ABSPATH . '.wp-staging')) {
            return false;
        }

        return true;
    }
}
