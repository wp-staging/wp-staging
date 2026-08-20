<?php

namespace WPStaging\Framework\Logger;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Utils\Cache\Cache;






class SseEventCache
{



    const EVENT_TYPE_TASK = 'task';




    const EVENT_TYPE_MEMORY_EXHAUST = 'memory_exhaust';




    const EVENT_TYPE_FATAL_ERROR = 'fatal_error';




    const EVENT_TYPE_COMPLETE = 'complete';




    protected $cacheDirectory = '';




    protected $count = 0;




    protected $events = [];




    protected $cache;

    public function __construct(Cache $cache, Directory $directory)
    {
        $this->cacheDirectory = $directory->getSseCacheDirectory();
        $this->cache          = $cache;
        $this->cache->setPath($this->cacheDirectory);
    }




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
 
        $this->load();

        $this->events[] = $log;

        $this->count++;
        $this->cache->save($this->events);
    }

    public function load()
    {
 
        $filePath = $this->cache->getFilePath();
        if ($filePath !== '') {
            clearstatcache(true, $filePath);
        }

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
