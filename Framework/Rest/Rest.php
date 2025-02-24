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
    /** @var string */
    const WPSTG_ROUTE_NAMESPACE_V1 = 'wpstg-routes/v1';

    /** @var int */
    const REQUEST_TIMEOUT = 30;

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

        $originalUrl = trailingslashit(get_home_url(get_current_blog_id(), ''));

        $url               = add_query_arg('rest_route', '/', $originalUrl);
        $restPath          = $this->getApiRequestURI($url);
        $requestPathApiURI = $this->getApiRequestURI($requestPath);
        if (!empty($restPath) && strpos($requestPathApiURI, $restPath) === 0) {
            return true;
        }

        // nginx only allows HTTP/1.0 methods when redirecting from / to /index.php.
        // To work around this, we manually add index.php to the URL, avoiding the redirect.
        if ('index.php/' !== substr($originalUrl, -10)) {
            $urlWithIndex = $originalUrl . 'index.php';
        }

        if (!empty($urlWithIndex)) {
            $urlWithIndex      = add_query_arg('rest_route', '/', $urlWithIndex);
            $restPathWithIndex = $this->getApiRequestURI($urlWithIndex);
            $requestPathApiURI = $this->getApiRequestURI($requestPath);
            if (!empty($restPathWithIndex) && strpos($requestPathApiURI, $restPathWithIndex) === 0) {
                return true;
            }
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
