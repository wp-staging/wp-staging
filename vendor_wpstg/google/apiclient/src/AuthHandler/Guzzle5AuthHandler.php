<?php

namespace WPStaging\Vendor\Google\AuthHandler;

use WPStaging\Vendor\Google\Auth\CredentialsLoader;
use WPStaging\Vendor\Google\Auth\HttpHandler\HttpHandlerFactory;
use WPStaging\Vendor\Google\Auth\FetchAuthTokenCache;
use WPStaging\Vendor\Google\Auth\Subscriber\AuthTokenSubscriber;
use WPStaging\Vendor\Google\Auth\Subscriber\ScopedAccessTokenSubscriber;
use WPStaging\Vendor\Google\Auth\Subscriber\SimpleSubscriber;
use WPStaging\Vendor\GuzzleHttp\Client;
use WPStaging\Vendor\GuzzleHttp\ClientInterface;
use WPStaging\Vendor\Psr\Cache\CacheItemPoolInterface;
/**
*
*/
class Guzzle5AuthHandler
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
        $subscriber = new \WPStaging\Vendor\Google\Auth\Subscriber\AuthTokenSubscriber($credentials, $authHttpHandler, $tokenCallback);
        $http->setDefaultOption('auth', 'google_auth');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    public function attachToken(\WPStaging\Vendor\GuzzleHttp\ClientInterface $http, array $token, array $scopes)
    {
        $tokenFunc = function ($scopes) use($token) {
            return $token['access_token'];
        };
        $subscriber = new \WPStaging\Vendor\Google\Auth\Subscriber\ScopedAccessTokenSubscriber($tokenFunc, $scopes, $this->cacheConfig, $this->cache);
        $http->setDefaultOption('auth', 'scoped');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    public function attachKey(\WPStaging\Vendor\GuzzleHttp\ClientInterface $http, $key)
    {
        $subscriber = new \WPStaging\Vendor\Google\Auth\Subscriber\SimpleSubscriber(['key' => $key]);
        $http->setDefaultOption('auth', 'simple');
        $http->getEmitter()->attach($subscriber);
        return $http;
    }
    private function createAuthHttp(\WPStaging\Vendor\GuzzleHttp\ClientInterface $http)
    {
        return new \WPStaging\Vendor\GuzzleHttp\Client(['base_url' => $http->getBaseUrl(), 'defaults' => ['exceptions' => \true, 'verify' => $http->getDefaultOption('verify'), 'proxy' => $http->getDefaultOption('proxy')]]);
    }
}
