<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Utils\Cache\AbstractCache;
use WPStaging\Framework\Utils\Cache\Cache;

class CacheStorage implements StorageInterface
{
    /** @var string */
    private $key;

    /** @var Cache */
    private $cache;

    /** @var array|null */
    private $items;

    public function __construct(Cache $cache)
    {
        $this->cache = clone $cache;
    }

    public function __destruct()
    {
        if (!$this->key) {
            return;
        }

        if (!$this->items) {
            $this->cache->delete();
            return;
        }

        $this->cache->save($this->items);
    }

    /**
     * @inheritDoc
     */
    public function setKey($key)
    {
        $this->key = $key;
        $this->init();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count((array) $this->items);
    }

    /**
     * @inheritDoc
     */
    public function append($value)
    {
        $this->items[] = $value;
    }

    /**
     * @inheritDoc
     */
    public function prepend($value)
    {
        array_unshift($this->items, $value);
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        return array_shift($this->items);
    }

    /**
     * @inheritDoc
     */
    public function last()
    {
        return array_pop($this->items);
    }

    protected function init()
    {
        $this->cache->setFilename('queue_' . $this->key);
        $this->items = $this->cache->get([]);
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->items = [];
    }

    /**
     * @return AbstractCache|Cache|null
     */
    public function getCache()
    {
        return $this->cache;
    }
}
