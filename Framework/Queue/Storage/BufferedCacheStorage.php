<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Utils\Cache\AbstractCache;
use WPStaging\Framework\Utils\Cache\BufferedCache;

// This does not use $items like other cache
// $items are to append to the end of the cache when it is destructed
// Buffered Cache does not read entire file, it read the file partially
class BufferedCacheStorage implements StorageInterface, ShutdownableInterface
{
    const FILE_PREFIX = 'queue_';

    /** @var string */
    private $key;

    /** @var BufferedCache */
    private $cache;

    /** @var array|null */
    private $items;

    /** @var bool */
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
            #$this->cache->delete();
        }
    }

    /**
     * @param bool $isUsePrefix
     */
    public function setIsUsePrefix($isUsePrefix)
    {
        $this->isUsePrefix = $isUsePrefix;
    }

    /**
     * @inheritDoc
     */
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

    /**
     * Since this is buffered, we don't read whole cache file
     * Instead if there is still data in the file, we always return 1 if no data, than 0
     * @return int
     */
    public function count()
    {
        return $this->size() > 0 ? 1 : 0;
    }

    /**
     * Considering we are using 1 item per line, counting total lines of the file gives us the real count
     */
    public function realCount()
    {
        return $this->cache->countLines();
    }

    /**
     * @inheritDoc
     */
    public function append($value)
    {
        $this->cache->append($value);
    }

    /**
     * Due to nature of Buffered Cache, we save them to the buffered cache
     * but not add them to current items / queue, it will be visible next queue load
     * that's because __destruct() would also add it to bottom of the queue.
     * TODO introduce additional logic to also add it to current queue should we need it
     * @param mixed $value
     */
    public function prepend($value)
    {
        $this->cache->prepend($value);
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        return $this->cache->first();
    }

    /**
     * @inheritDoc
     */
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

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->items = [];
        $this->cache->delete();
    }

    /**
     * @return int
     */
    private function size()
    {
        if (!file_exists($this->cache->getFilePath())) {
            return 0;
        }
        clearstatcache();
        return (int) filesize($this->cache->getFilePath());
    }

    /**
     * @return AbstractCache|BufferedCache|null
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

    public function current()
    {
        return current($this->items);
    }
}
