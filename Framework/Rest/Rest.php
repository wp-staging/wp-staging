<?php

namespace WPStaging\Framework\Rest;

use WPStaging\Framework\Utils\Sanitize;

/**
 * Class Rest
 *
 * @package WPStaging\Framework\Rest
 *
 * @todo merge into WPAdapter class?
 */
class Rest
{
    /** @var Sanitize */
    private $sanitize;

    public function __construct(Sanitize $sanitize)
    {
        $this->sanitize = $sanitize;
    }

    // Is Rest URL
    public function isRestUrl()
    {
        // Early bail if uri is empty
        if (empty($_SERVER['REQUEST_URI'])) {
            return false;
        }

        $requestPath = trim($this->sanitize->sanitizeUrl($_SERVER['REQUEST_URI']), '/');

        $url = trailingslashit(get_home_url(get_current_blog_id(), ''));
        // nginx only allows HTTP/1.0 methods when redirecting from / to /index.php.
        // To work around this, we manually add index.php to the URL, avoiding the redirect.
        if ('index.php/' !== substr($url, -10)) {
            $url .= 'index.php';
        }

        $url      = add_query_arg('rest_route', '/', $url);
        $restPath = $this->getApiRequestURI($url);
        if (!empty($restPath) && strpos($requestPath, $restPath) === 0) {
            return true;
        }

        // Early bail rest url function not exists
        if (!function_exists('rest_url')) {
            return false;
        }

        $baseRestURL = get_rest_url(get_current_blog_id(), '/');
        $restPath    = $this->getApiRequestURI($baseRestURL);

        // Early bail if rest path is empty
        if (empty($restPath)) {
            return false;
        }

        return strpos($requestPath, $restPath) === 0;
    }

    private function getApiRequestURI($url)
    {
        if (empty($url)) {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        $path = empty($path) ? '' : trim($path, '/');

        $query = parse_url($url, PHP_URL_QUERY);
        $query = empty($query) ? '' : trim($query, '/');

        return $query === '' ? $path : $path . '?' . $query;
    }
}
