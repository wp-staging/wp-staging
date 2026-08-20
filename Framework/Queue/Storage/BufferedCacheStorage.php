<?php

 
 

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Utils\Cache\AbstractCache;
use WPStaging\Framework\Utils\Cache\BufferedCache;

 
 
 
class BufferedCacheStorage implements StorageInterface, ShutdownableInterface
{
    const FILE_PREFIX = 'queue_';

 
    private $key;

 
    private $cache;

 
    private $items;

 
    private $isUsePrefix;

    private $commited = false;

    public function __construct(BufferedCache $cache)
    {
        $this->isUsePrefix = true;
        $this->cache = clone $cache;
        $this->items = [];
    }

    public function onWpShutdown()
    {
        if (!$this->commited) {
            $this->commit();
        }
    }

    public function commit()
    {
        if (!$this->key) {
            return;
        }

        if ($this->items) {
            $this->cache->append($this->items);
            return;
        }

        if ($this->size() === 0) {
 
        }
    }




    public function setIsUsePrefix($isUsePrefix)
    {
        $this->isUsePrefix = $isUsePrefix;
    }




    public function setKey($key)
    {
        $this->key = $key;

        $fileName = $key;
        if ($this->isUsePrefix) {
            $fileName = self::FILE_PREFIX . $fileName;
        }

        $this->cache->setFilename($fileName);

        return $this;
    }






    public function count()
    {
        return $this->size() > 0 ? 1 : 0;
    }




    public function realCount()
    {
        return $this->cache->countLines();
    }




    public function append($value)
    {
        $this->cache->append($value);
    }








    public function prepend($value)
    {
        $this->cache->prepend($value);
    }




    public function first()
    {
        return $this->cache->first();
    }




    public function last()
    {
        $item = $this->cache->readLines(1, null, BufferedCache::POSITION_BOTTOM);
        if (!$item) {
            return null;
        }

        $item = isset($item[1]) ? $item[1] : $item[0];
        $this->cache->deleteBottomBytes(strlen($item));
        return $item;
    }




    public function reset()
    {
        $this->items = [];
        $this->cache->delete();
    }




    private function size()
    {
        if (!file_exists($this->cache->getFilePath())) {
            return 0;
        }

        clearstatcache();
        return (int) filesize($this->cache->getFilePath());
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

    public function current()
    {
        return current($this->items);
    }
}
