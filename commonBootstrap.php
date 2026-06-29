<?php

if (!function_exists('wpstgIsAdminActionRequest')) {
    /**
     * @param string $requestUri
     * @return bool
     */
    function wpstgIsAdminActionRequest(string $requestUri): bool
    {
        $requestPath = (string)parse_url($requestUri, PHP_URL_PATH);

        return strpos($requestPath, '/wp-admin/admin-ajax.php') !== false
            || strpos($requestPath, '/wp-admin/admin-post.php') !== false;
    }
}

if (!function_exists('wpstgIsPluginAction')) {
    /**
     * @param string $action
     * @return bool
     */
    function wpstgIsPluginAction(string $action): bool
    {
        return strpos($action, 'wpstg') === 0 || strpos($action, 'raw_wpstg') === 0;
    }
}

if (!function_exists('wpstgGetRequestAction')) {
    /**
     * @return string
     */
    function wpstgGetRequestAction(): string
    {
        if (isset($_GET['action'])) {
            $action = sanitize_key($_GET['action']);
        } elseif (isset($_POST['action'])) {
            $action = sanitize_key($_POST['action']);
        } elseif (isset($_REQUEST['action'])) {
            $action = sanitize_key($_REQUEST['action']);
        } else {
            $action = '';
        }

        if (!is_scalar($action)) {
            return '';
        }

        return sanitize_key(wp_unslash((string) $action));
    }
}

if (!function_exists('wpstgIsPluginRestRoute')) {
    /**
     * @param string $route
     * @return bool
     */
    function wpstgIsPluginRestRoute(string $route): bool
    {
        $route = ltrim($route, '/');

        return $route === 'wpstg/v1' || strpos($route, 'wpstg/v1/') === 0;
    }
}

if (!function_exists('wpstgGetPrettyRestRoute')) {
    /**
     * @param string $requestUri
     * @return string
     */
    function wpstgGetPrettyRestRoute(string $requestUri): string
    {
        $requestPath = (string)parse_url($requestUri, PHP_URL_PATH);
        if ($requestPath === '') {
            return '';
        }

        // REST_REQUEST isn't defined at plugins_loaded; resolve prefix dynamically
        // so custom rest_url_prefix filters are respected.
        $restPrefix = '/' . trim(apply_filters('rest_url_prefix', 'wp-json'), '/') . '/';
        $prefixPosition = strpos($requestPath, $restPrefix);
        if ($prefixPosition === false) {
            return '';
        }

        return ltrim(substr($requestPath, $prefixPosition + strlen($restPrefix)), '/');
    }
}

if (!function_exists('wpstgIsRestRequest')) {
    /**
     * @param string $requestUri
     * @return bool
     */
    function wpstgIsRestRequest(string $requestUri): bool
    {
        if (wpstgGetPrettyRestRoute($requestUri) !== '') {
            return true;
        }

        return !empty($_GET['rest_route']);
    }
}

if (!function_exists('wpstgIsPluginRestRequest')) {
    /**
     * @param string $requestUri
     * @return bool
     */
    function wpstgIsPluginRestRequest(string $requestUri): bool
    {
        $prettyRoute = wpstgGetPrettyRestRoute($requestUri);
        if ($prettyRoute !== '') {
            return wpstgIsPluginRestRoute($prettyRoute);
        }

        if (empty($_GET['rest_route'])) {
            return false;
        }

        $restRoute = sanitize_text_field(wp_unslash($_GET['rest_route']));

        return wpstgIsPluginRestRoute($restRoute);
    }
}

if (!function_exists('wpstgShouldSkipBootstrap')) {
    function wpstgShouldSkipBootstrap(): bool
    {
        if (defined('WP_INSTALLING') && WP_INSTALLING) {
            return false;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }

        if (defined('WP_CLI') && WP_CLI) {
            return false;
        }

        $requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

        // WordPress login page: needed for post-restore login prompt and other login hooks.
        if (strpos($requestUri, '/wp-login.php') !== false) {
            return false;
        }

        // Temporary/auto login links (?wpstg_login=, ?wpstg_staging_login=, ?action=wpstg_*).
        $action = wpstgGetRequestAction();
        if (
            !empty($_GET['wpstg_login']) ||
            !empty($_GET['wpstg_staging_login']) ||
            wpstgIsPluginAction($action)
        ) {
            return false;
        }

        // Staging sites need full bootstrap: login gate, permission checks, admin bar CSS.
        if (defined('WPSTAGING_DEV_SITE') && WPSTAGING_DEV_SITE === true) {
            return false;
        }

        if (get_option('wpstg_is_staging_site') === 'true') {
            return false;
        }

        if (file_exists(ABSPATH . '.wp-staging')) {
            return false;
        }

        // Non-WP-CLI PHP processes (test runners, deploy tools) also need full bootstrap.
        if (php_sapi_name() === 'cli') {
            return false;
        }

        if (wpstgIsAdminActionRequest($requestUri)) {
            return !wpstgIsPluginAction($action);
        }

        if (is_admin()) {
            return false;
        }

        if (wpstgIsRestRequest($requestUri)) {
            return !wpstgIsPluginRestRequest($requestUri);
        }

        return true;
    }
}
