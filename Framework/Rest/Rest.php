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

        if ($this->hasRestRouteQueryParam()) {
            return true;
        }

        $requestUri = $this->sanitize->sanitizeUrl($_SERVER['REQUEST_URI']);

 
        if (!function_exists('rest_url')) {
            return false;
        }

        $requestPath = trim($requestUri, '/');

        $baseRestURL = get_rest_url(get_current_blog_id(), '/');
        $restPath    = $this->getApiRequestURI($baseRestURL);

 
        if (empty($restPath)) {
            return false;
        }

        return strpos($requestPath, $restPath) === 0;
    }













    private function hasRestRouteQueryParam(): bool
    {
        if (!array_key_exists('rest_route', $_GET) || !is_string($_GET['rest_route'])) {
            return false;
        }

        return $_GET['rest_route'] !== '' && $_GET['rest_route'] !== '0';
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
