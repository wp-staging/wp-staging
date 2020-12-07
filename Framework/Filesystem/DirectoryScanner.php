<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Filesystem;

use RuntimeException;
use WPStaging\Vendor\Symfony\Component\Finder\Finder;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\Queue;
use WPStaging\Framework\Queue\Storage\BufferedCacheStorage;
use WPStaging\Framework\Utils\Cache\BufferedCache;

class DirectoryScanner
{
    const DATA_CACHE_FILE = 'filesystem_scanner_directory_data';
    const QUEUE_CACHE_FILE = 'directory_scanner';

    /** @var BufferedCache */
    private $cache;

    /** @var BufferedCacheStorage */
    private $storage;

    /** @var DirectoryService */
    private $service;

    /** @var Queue|null */
    private $queue;

    /** @var array */
    private $newQueueItems;

    public function __construct(BufferedCache $cache, BufferedCacheStorage $storage, DirectoryService $service)
    {
        $this->newQueueItems = [];
        $this->cache = clone $cache;
        $this->storage = clone $storage;
        $this->service = $service;
    }

    public function __destruct()
    {
        if ($this->newQueueItems && $this->queue) {
            $this->queue->pushAsArray($this->newQueueItems);
        }
    }

    /**
     * @param string $name
     */
    public function setQueueByName($name = self::QUEUE_CACHE_FILE)
    {
        $this->queue = new Queue;
        $this->queue->setName($name);
        $this->queue->setStorage($this->storage);
    }

    /**
     * @param array|null $excluded
     * @param int $depth
     * @return Finder|null
     */
    public function scanCurrentPath(array $excluded = null, $depth = 0)
    {
        $path = $this->getPathFromQueue();
        if ($path === null) {
            throw new FinishedQueueException('Directory Scanner Queue is Finished');
        }

        $path = ABSPATH . $path;
        return $this->service->scan($path, $depth, $excluded);
    }

    /**
     * @return string|null
     */
    public function getPathFromQueue()
    {
        if ($this->queue->count() > 0) {
            return $this->queue->pop();
        }

        if ($this->newQueueItems) {
            return array_shift($this->newQueueItems);
        }

        return null;
    }

    /**
     * @param string $item
     */
    public function addToNewQueue($item)
    {
        $this->newQueueItems[] = $item;
    }

    /**
     * @return BufferedCache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        if (!$this->queue) {
            // TODO Custom Exception
            throw new RuntimeException('DirectoryScanner Queue is not set');
        }
        return $this->queue;
    }

    public function setNewQueueItems(array $items = null)
    {
        $this->newQueueItems = $items;
    }

    /**
     * @return DirectoryService
     */
    public function getService()
    {
        return $this->service;
    }
}
