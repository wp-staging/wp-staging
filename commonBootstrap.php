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

if (!function_exists('wpstgHasQueuedElementorCssClear')) {






    function wpstgHasQueuedElementorCssClear(string $queuedClearOptionName = 'wpstg_clear_elementor_css_cache'): bool
    {
        $autoloadedOptions = wp_load_alloptions();

        return !empty($autoloadedOptions[$queuedClearOptionName]);
    }
}

if (!function_exists('wpstgShouldSkipBootstrap')) {




    function wpstgShouldSkipBootstrap(string $phpSapiName = PHP_SAPI): bool
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

        if (wpstgHasQueuedElementorCssClear()) {
            return false;
        }

 
        if ($phpSapiName === 'cli') {
            return false;
        }

        if (wpstgIsAdminActionRequest($requestUri)) {
            return !in_array($action, ['update-plugin', 'install-plugin', 'update-theme', 'install-theme'], true);
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

if (!function_exists('wpstgOptionsTableOverwritesOptionsOnInsert')) {








    function wpstgOptionsTableOverwritesOptionsOnInsert(string $optionsTable): bool
    {
 
        global $wpdb;
        if (empty($wpdb->is_mysql)) {
            return false;
        }

        $optionIdColumn                 = $wpdb->get_row("SHOW COLUMNS FROM `{$optionsTable}` LIKE 'option_id'", ARRAY_A);
        $takesTheSameValueOnEveryInsert = !empty($optionIdColumn)
            && stripos((string)$optionIdColumn['Extra'], 'auto_increment') === false
            && (is_numeric($optionIdColumn['Default']) || ($optionIdColumn['Default'] === null && $optionIdColumn['Null'] === 'NO'));
        if (!$takesTheSameValueOnEveryInsert) {
            return false;
        }

        $uniqueIndexColumns = [];
        foreach ((array)$wpdb->get_results("SHOW INDEX FROM `{$optionsTable}` WHERE Non_unique = 0", ARRAY_A) as $indexColumn) {
            $uniqueIndexColumns[$indexColumn['Key_name']][] = $indexColumn['Column_name'];
        }

        return in_array(['option_id'], $uniqueIndexColumns, true);
    }
}

if (!function_exists('wpstgStopActivationIfOptionsTableOverwritesOptions')) {








    function wpstgStopActivationIfOptionsTableOverwritesOptions(bool $isNetworkWide = false)
    {
 
        global $wpdb;
        $optionsTables = [$wpdb->options];
        if ($isNetworkWide && is_multisite()) {
            $optionsTables = array_map(function ($siteId) use ($wpdb) {
                return $wpdb->get_blog_prefix((int)$siteId) . 'options';
            }, $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = %d ORDER BY blog_id", $wpdb->siteid)));
        }

        $brokenOptionsTables = array_filter($optionsTables, 'wpstgOptionsTableOverwritesOptionsOnInsert');
        if (empty($brokenOptionsTables)) {
            return;
        }

        $docsUrl = 'https://wp-staging.com/docs/missing-primary-key-in-table-wp-options';
        $reason  = sprintf(
            /* translators: %s: names of the WordPress options tables, separated by commas */
            _n(
                'WP STAGING has not been activated because the column option_id of the table %s has no autoincrement flag. While the table is in this state, every new setting saved on the site overwrites another one, so activating any plugin can delete one of your settings. Repair the table first, then activate WP STAGING again.',
                'WP STAGING has not been activated because the column option_id of the tables %s has no autoincrement flag. While the tables are in this state, every new setting saved on their sites overwrites another one, so activating any plugin can delete one of your settings. Repair the tables first, then activate WP STAGING again.',
                count($brokenOptionsTables),
                'wp-staging'
            ),
            implode(', ', $brokenOptionsTables)
        );
        if (function_exists('wp_doing_ajax') ? wp_doing_ajax() : (defined('DOING_AJAX') && DOING_AJAX)) {
            wp_send_json_error($reason . ' ' . __('How to fix this.', 'wp-staging') . ' ' . $docsUrl);
        }

        wp_die(
            esc_html($reason) . ' ' . esc_html__('How to fix this.', 'wp-staging') . ' <a href="' . esc_url($docsUrl) . '" target="_blank">' . esc_html($docsUrl) . '</a>',
            esc_html__('WP STAGING', 'wp-staging'),
            ['back_link' => true]
        );
    }
}
