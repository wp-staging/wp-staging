<?php

namespace WPStaging\Framework\Rest;

use WPStaging\Framework\Utils\Sanitize;








class Rest
{
 
    const WPSTG_ROUTE_NAMESPACE_V1 = 'wpstg/v1';

 
    const REQUEST_TIMEOUT = 30;

 
    private $sanitize;

    public function __construct(Sanitize $sanitize)
    {
        $this->sanitize = $sanitize;
    }

 
    public function isRestUrl()
    {
 
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

 
 
        if ('index.php/' !== substr($originalUrl, -10)) {
            $urlWithIndex      = add_query_arg('rest_route', '/', $originalUrl . 'index.php');
            $restPathWithIndex = $this->getApiRequestURI($urlWithIndex);
            if (!empty($restPathWithIndex) && strpos($requestPathApiURI, $restPathWithIndex) === 0) {
                return true;
            }
        }

 
        if (!function_exists('rest_url')) {
            return false;
        }

        $baseRestURL = get_rest_url(get_current_blog_id(), '/');
        $restPath    = $this->getApiRequestURI($baseRestURL);

 
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
