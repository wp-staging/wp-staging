<?php

if (!function_exists('wpstgIsAdminActionRequest')) {




    function wpstgIsAdminActionRequest(string $requestUri): bool
    {
        $requestPath = (string)parse_url($requestUri, PHP_URL_PATH);

        return strpos($requestPath, '/wp-admin/admin-ajax.php') !== false
            || strpos($requestPath, '/wp-admin/admin-post.php') !== false;
    }
}

if (!function_exists('wpstgIsPluginAction')) {




    function wpstgIsPluginAction(string $action): bool
    {
        return strpos($action, 'wpstg') === 0 || strpos($action, 'raw_wpstg') === 0;
    }
}

if (!function_exists('wpstgGetRequestAction')) {



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




    function wpstgIsPluginRestRoute(string $route): bool
    {
        $route = ltrim($route, '/');

        return $route === 'wpstg/v1' || strpos($route, 'wpstg/v1/') === 0;
    }
}

if (!function_exists('wpstgGetPrettyRestRoute')) {




    function wpstgGetPrettyRestRoute(string $requestUri): string
    {
        $requestPath = (string)parse_url($requestUri, PHP_URL_PATH);
        if ($requestPath === '') {
            return '';
        }

 
 
        $restPrefix = '/' . trim(apply_filters('rest_url_prefix', 'wp-json'), '/') . '/';
        $prefixPosition = strpos($requestPath, $restPrefix);
        if ($prefixPosition === false) {
            return '';
        }

        return ltrim(substr($requestPath, $prefixPosition + strlen($restPrefix)), '/');
    }
}

if (!function_exists('wpstgIsRestRequest')) {




    function wpstgIsRestRequest(string $requestUri): bool
    {
        if (wpstgGetPrettyRestRoute($requestUri) !== '') {
            return true;
        }

        return !empty($_GET['rest_route']);
    }
}

if (!function_exists('wpstgIsPluginRestRequest')) {




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

if (!function_exists('wpstgIsStagingSite')) {





    function wpstgIsStagingSite(string $stagingMarkerFileName = '.wp-staging', string $stagingSiteOptionName = 'wpstg_is_staging_site'): bool
    {
        if (defined('WPSTAGING_DEV_SITE') && WPSTAGING_DEV_SITE === true) {
            return true;
        }

        if (get_option($stagingSiteOptionName) === 'true') {
            return true;
        }

        return file_exists(ABSPATH . $stagingMarkerFileName);
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

 
        if (strpos($requestUri, '/wp-login.php') !== false) {
            return false;
        }

 
        $action = wpstgGetRequestAction();
        if (
            !empty($_GET['wpstg_login']) ||
            !empty($_GET['wpstg_staging_login']) ||
            wpstgIsPluginAction($action)
        ) {
            return false;
        }

 
        if (wpstgIsStagingSite()) {
            return false;
        }

 
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
