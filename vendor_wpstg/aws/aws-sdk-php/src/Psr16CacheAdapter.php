<?php

namespace WPStaging\Vendor\Aws;

use WPStaging\Vendor\Psr\SimpleCache\CacheInterface as SimpleCacheInterface;
class Psr16CacheAdapter implements \WPStaging\Vendor\Aws\CacheInterface
{
    /** @var SimpleCacheInterface */
    private $cache;
    public function __construct(\WPStaging\Vendor\Psr\SimpleCache\CacheInterface $cache)
    {
        $this->cache = $cache;
    }
    public function get($key)
    {
        return $this->cache->get($key);
    }
    public function set($key, $value, $ttl = 0)
    {
        $this->cache->set($key, $value, $ttl);
    }
    public function remove($key)
    {
        $this->cache->delete($key);
    }
}
