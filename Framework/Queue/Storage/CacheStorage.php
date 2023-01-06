<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Utils\Cache\AbstractCache;
use WPStaging\Framework\Utils\Cache\Cache;

/**
 * @todo this class in not used anymore. 14.12.2022 We will delete this class in version 4.4.2
 */
class CacheStorage implements StorageInterface, ShutdownableInterface
{
    /** @var string */
    private $key;

    /** @var Cache */
    private $cache;

    /** @var array|null */
    private $items;

    private $commited = false;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function onWpShutdown()
    {
        if (!$this->commited) {
            $this->commit();
        }
    }

    public function commit()
    {
        $this->commited = true;

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
        return count((array)$this->items);
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
    public function current()
    {
        return current($this->items);
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

    public function reverse()
    {
        $this->items = array_reverse($this->items);

        return $this->items;
    }
}
