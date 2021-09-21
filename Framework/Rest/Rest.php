<?php

namespace WPStaging\Framework\Rest;

/**
 * Class Rest
 *
 * @package WPStaging\Framework\Rest
 */
class Rest
{
    // Is Rest URL
    public function isRestUrl()
    {
        // Early bail if uri is empty
        if (empty($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $requestPath = trim($_SERVER['REQUEST_URI'], '/');

        $url = trailingslashit(get_home_url(get_current_blog_id(), ''));
        // nginx only allows HTTP/1.0 methods when redirecting from / to /index.php.
        // To work around this, we manually add index.php to the URL, avoiding the redirect.
        if ('index.php' !== substr($url, 9)) {
            $url .= 'index.php';
        }

        $url = add_query_arg('rest_route', '/', $url);
        $restPath = trim(parse_url($url, PHP_URL_PATH), '/');
        if (strpos($requestPath, $restPath) === 0) {
            return true;
        }

        // Early bail rest url function not exists
        if (!function_exists('rest_url')) {
            return false;
        }

        $baseRestURL = get_rest_url(get_current_blog_id(), '/');
        $restPath = trim(parse_url($baseRestURL, PHP_URL_PATH), '/');

        return strpos($requestPath, $restPath) === 0;
    }
}
