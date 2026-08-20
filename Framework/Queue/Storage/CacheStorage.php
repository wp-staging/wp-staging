<?php

 
 

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Utils\Cache\AbstractCache;
use WPStaging\Framework\Utils\Cache\Cache;




class CacheStorage implements StorageInterface, ShutdownableInterface
{
 
    private $key;

 
    private $cache;

 
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




    public function setKey($key)
    {
        $this->key = $key;
        $this->init();

        return $this;
    }




    public function count()
    {
        return count((array)$this->items);
    }




    public function append($value)
    {
        $this->items[] = $value;
    }




    public function prepend($value)
    {
        array_unshift($this->items, $value);
    }




    public function current()
    {
        return current($this->items);
    }




    public function first()
    {
        return array_shift($this->items);
    }




    public function last()
    {
        return array_pop($this->items);
    }

    protected function init()
    {
        $this->cache->setFilename('queue_' . $this->key);
        $this->items = $this->cache->get([]);
    }




    public function reset()
    {
        $this->items = [];
    }




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
