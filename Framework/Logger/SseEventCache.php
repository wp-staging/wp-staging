<?php

namespace WPStaging\Framework\Logger;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Utils\Cache\Cache;

/**
 * This class is used to cache the events for the SSE (Server-Sent Events) stream.
 * It stores the events in a cache file and allows to push new events, load existing events,
 * It is used by BackgroundLogger to push the events to the SSE stream.
 */
class SseEventCache
{
    /**
     * @var string
     */
    const EVENT_TYPE_TASK = 'task';

    /**
     * @var string
     */
    const EVENT_TYPE_MEMORY_EXHAUST = 'memory_exhaust';

    /**
     * @var string
     */
    const EVENT_TYPE_COMPLETE = 'complete';

    /**
     * @var string
     */
    protected $cacheDirectory = '';

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var Cache
     */
    protected $cache;

    public function __construct(Cache $cache, Directory $directory)
    {
        $this->cacheDirectory = $directory->getSseCacheDirectory();
        $this->cache          = $cache;
        $this->cache->setPath($this->cacheDirectory);
    }

    /**
     * @return void
     */
    public function deleteSseCacheFiles()
    {
        if (!file_exists($this->cacheDirectory)) {
            return;
        }

        $iterator = new \DirectoryIterator($this->cacheDirectory);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && strpos($fileInfo->getFilename(), 'sse.cache.php') !== false) {
                unlink($fileInfo->getPathname());
            }
        }
    }

    public function setJobId(string $jobId, bool $checkIfExist = false)
    {
        $this->cache->setFilename($jobId . '.sse');
        if ($checkIfExist && !$this->cache->isValid(false)) {
            return false;
        }

        return true;
    }

    public function push(array $log)
    {
        $this->events[] = $log;

        $this->count++;
        $this->cache->save($this->events);
    }

    public function load()
    {
        if (!$this->cache->isValid()) {
            return;
        }

        $this->events = $this->cache->get([]);
        if (!is_array($this->events)) {
            $this->events = [];
        }

        $this->count = count($this->events);
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getEvents(int $offset = 0)
    {
        if ($offset >= $this->count) {
            return [];
        }

        return array_slice($this->events, $offset);
    }
}
