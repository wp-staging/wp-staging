<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Framework\Filesystem;

use RuntimeException;
use WPStaging\Component\Task\Filesystem\DirectoryScannerTask;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\Queue;
use WPStaging\Framework\Queue\Storage\BufferedCacheStorage;
use WPStaging\Framework\Utils\Cache\BufferedCache;

/**
 * Class DirectoryScannerControl
 *
 * @see DirectoryScannerTask
 *
 * @package WPStaging\Framework\Filesystem
 */
class DirectoryScannerControl
{
    const DATA_CACHE_FILE = 'filesystem_scanner_directory_data';
    const QUEUE_CACHE_FILE = 'directory_scanner';

    /** @var BufferedCache */
    private $cache;

    /** @var BufferedCacheStorage */
    private $storage;

    /** @var DirectoryScanner */
    private $scanner;

    /** @var Queue|null */
    private $queue;

    /** @var array */
    private $newQueueItems;

    /** @var Directory */
    private $directory;

    public function __construct(BufferedCache $cache, BufferedCacheStorage $storage, DirectoryScanner $scanner, Directory $directory)
    {
        $this->newQueueItems = [];
        $this->cache         = clone $cache;
        $this->storage       = clone $storage;
        $this->scanner       = $scanner;
        $this->directory     = $directory;
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
     * @param array $excluded
     *
     * @return array
     */
    public function scanCurrentPath(array $excluded = [])
    {
        $path = $this->getPathFromQueue();
        if ($path === null) {
            throw new FinishedQueueException('Directory Scanner Queue is Finished');
        }

        $path = ABSPATH . $path;

        return $this->scanner->scan($path, $excluded);
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
            throw new RuntimeException('DirectoryScannerControl Queue is not set');
        }
        return $this->queue;
    }

    public function setNewQueueItems(array $items = null)
    {
        $this->newQueueItems = $items;
    }

    /**
     * @return Directory
     */
    public function getDirectory()
    {
        return $this->directory;
    }
}
