<?php

namespace WPStaging\Framework\Rest;








class Rest
{
 
    const WPSTG_ROUTE_NAMESPACE_V1 = 'wpstg/v1';

 
    const REQUEST_TIMEOUT = 30;

 
    public function isRestUrl()
    {
 
        if (empty($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $restRoute = isset($_POST['rest_route']) ? $_POST['rest_route'] : (isset($_GET['rest_route']) ? $_GET['rest_route'] : null); // phpcs:ignore WPStagingCS.Security.SanitizeInput.InputNotSanitized -- compared only, never output or stored
        if ($restRoute !== null) {
            return is_string($restRoute) && $restRoute !== '' && $restRoute !== '0';
        }

 
        if (!function_exists('rest_url')) {
            return false;
        }

        $baseRestURL = get_rest_url(get_current_blog_id(), '/');

 
        if (parse_url($baseRestURL, PHP_URL_QUERY)) {
            return false;
        }

        $restPath = trim((string)parse_url($baseRestURL, PHP_URL_PATH), '/');

 
        if (empty($restPath)) {
            return false;
        }

        $requestPath = trim(explode('?', $_SERVER['REQUEST_URI'], 2)[0], '/'); // phpcs:ignore WPStagingCS.Security.SanitizeInput.InputNotSanitized -- compared only, never output or stored

        return $requestPath === $restPath || strpos($requestPath, $restPath . '/') === 0;
    }
}
