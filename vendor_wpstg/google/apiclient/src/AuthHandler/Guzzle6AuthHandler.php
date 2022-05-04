<?php

namespace WPStaging\Vendor\Google\AuthHandler;

use WPStaging\Vendor\Google\Auth\CredentialsLoader;
use WPStaging\Vendor\Google\Auth\HttpHandler\HttpHandlerFactory;
use WPStaging\Vendor\Google\Auth\FetchAuthTokenCache;
use WPStaging\Vendor\Google\Auth\Middleware\AuthTokenMiddleware;
use WPStaging\Vendor\Google\Auth\Middleware\ScopedAccessTokenMiddleware;
use WPStaging\Vendor\Google\Auth\Middleware\SimpleMiddleware;
use WPStaging\Vendor\GuzzleHttp\Client;
use WPStaging\Vendor\GuzzleHttp\ClientInterface;
use WPStaging\Vendor\Psr\Cache\CacheItemPoolInterface;
/**
* This supports Guzzle 6
*/
class Guzzle6AuthHandler
{
    protected $cache;
    protected $cacheConfig;
    public function __construct(\WPStaging\Vendor\Psr\Cache\CacheItemPoolInterface $cache = null, array $cacheConfig = [])
    {
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
    }
    public function attachCredentials(\WPStaging\Vendor\GuzzleHttp\ClientInterface $http, \WPStaging\Vendor\Google\Auth\CredentialsLoader $credentials, callable $tokenCallback = null)
    {
        // use the provided cache
        if ($this->cache) {
            $credentials = new \WPStaging\Vendor\Google\Auth\FetchAuthTokenCache($credentials, $this->cacheConfig, $this->cache);
        }
        return $this->attachCredentialsCache($http, $credentials, $tokenCallback);
    }
    public function attachCredentialsCache(\WPStaging\Vendor\GuzzleHttp\ClientInterface $http, \WPStaging\Vendor\Google\Auth\FetchAuthTokenCache $credentials, callable $tokenCallback = null)
    {
        // if we end up needing to make an HTTP request to retrieve credentials, we
        // can use our existing one, but we need to throw exceptions so the error
        // bubbles up.
        $authHttp = $this->createAuthHttp($http);
        $authHttpHandler = \WPStaging\Vendor\Google\Auth\HttpHandler\HttpHandlerFactory::build($authHttp);
        $middleware = new \WPStaging\Vendor\Google\Auth\Middleware\AuthTokenMiddleware($credentials, $authHttpHandler, $tokenCallback);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'google_auth';
        $http = new \WPStaging\Vendor\GuzzleHttp\Client($config);
        return $http;
    }
    public function attachToken(\WPStaging\Vendor\GuzzleHttp\ClientInterface $http, array $token, array $scopes)
    {
        $tokenFunc = function ($scopes) use($token) {
            return $token['access_token'];
        };
        $middleware = new \WPStaging\Vendor\Google\Auth\Middleware\ScopedAccessTokenMiddleware($tokenFunc, $scopes, $this->cacheConfig, $this->cache);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'scoped';
        $http = new \WPStaging\Vendor\GuzzleHttp\Client($config);
        return $http;
    }
    public function attachKey(\WPStaging\Vendor\GuzzleHttp\ClientInterface $http, $key)
    {
        $middleware = new \WPStaging\Vendor\Google\Auth\Middleware\SimpleMiddleware(['key' => $key]);
        $config = $http->getConfig();
        $config['handler']->remove('google_auth');
        $config['handler']->push($middleware, 'google_auth');
        $config['auth'] = 'simple';
        $http = new \WPStaging\Vendor\GuzzleHttp\Client($config);
        return $http;
    }
    private function createAuthHttp(\WPStaging\Vendor\GuzzleHttp\ClientInterface $http)
    {
        return new \WPStaging\Vendor\GuzzleHttp\Client(['base_uri' => $http->getConfig('base_uri'), 'http_errors' => \true, 'verify' => $http->getConfig('verify'), 'proxy' => $http->getConfig('proxy')]);
    }
}
