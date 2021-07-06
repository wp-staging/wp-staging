<?php

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Framework\Queue\Storage\SPL\JsonDoublyLinkedList;
use WPStaging\Framework\Traits\ResourceTrait;

class ObjectCacheStorage implements StorageInterface, ShutdownableInterface
{
    use ResourceTrait;

    /**
     * @var int When this value is hit, a new cache rotation starts,
     *          to make sure we don't try to bite more than we can chew.
     */
    protected $maxCacheSize;

    /**
     * @var int The current cache size, that should be smaller than max.
     */
    protected $currentCacheSize = 0;

    /**
     * @var string The identifier of this cache instance.
     */
    protected $key;

    /**
     * Everytime the Cache gets bigger than maxCacheSize,
     * this value increases by 1.
     *
     * @var int
     */
    protected $cacheRotation = 0;

    /**
     * The total items across all cache rotations.
     *
     * @var int
     */
    protected $totalCount = 0;

    /**
     * @var array The current data this storage stores.
     */
    protected $data;

    const GROUP = 'wpstg.objectCacheStorage';

    /**
     * @var JsonDoublyLinkedList
     */
    protected $linkedList;

    public function __construct()
    {
        // 1MB, or 10% of available memory
        $this->maxCacheSize = 1 * MB_IN_BYTES;
    }

    public function onWpShutdown()
    {
        if (!$this->linkedList->isEmpty()) {
            $this->commit();
        }
    }

    protected function baseCacheKey()
    {
        return "wpstg.storage.objectCache.{$this->key}";
    }

    protected function cacheKeyThisRotation()
    {
        return "{$this->baseCacheKey()}.{$this->cacheRotation}";
    }

    protected function cacheKeyPreviousRotation()
    {
        $previousCacheRotation = $this->cacheRotation - 1;

        return "{$this->baseCacheKey()}.{$previousCacheRotation}";
    }

    public function commit()
    {
        // json_encode removes items from the linkedList
        $count = $this->linkedList->count();

        wp_cache_add($this->cacheKeyThisRotation(), json_encode($this->linkedList), self::GROUP, 1 * DAY_IN_SECONDS);
        $this->currentCacheSize = 0;
        $this->updateCacheInfo($this->cacheRotation + 1);
    }

    /**
     * This is the entry-point of this cache.
     * It must be set.
     *
     * @param string $key
     *
     * @return void|StorageInterface
     */
    public function setKey($key)
    {
        if (!is_null($this->key)) {
            throw new \BadMethodCallException();
        }

        $this->key = $key;
        $this->initializeLinkedList();
        $this->updateCacheInfo(1);
        $this->linkedList->hydrate(wp_cache_get($this->cacheKeyThisRotation(), self::GROUP) ?: '[]');
    }

    protected function getCacheInfo()
    {
        if (!$cacheInfo = wp_cache_get($this->baseCacheKey(), self::GROUP)) {
            return [
                'currentRotation' => 1,
                'totalCount' => 0,
                'highestRotation' => 0,
            ];
        }

        return json_decode($cacheInfo, true);
    }

    protected function updateCacheInfo($newCacheRotation)
    {
        $this->cacheRotation = $newCacheRotation;

        $cacheInfo = [
            'currentRotation' => $newCacheRotation,
            'totalCount' => $this->totalCount,
            'highestRotation' => max($this->cacheRotation, $this->getCacheInfo()['highestRotation']),
        ];

        wp_cache_add($this->baseCacheKey(), json_encode($cacheInfo), self::GROUP, 1 * DAY_IN_SECONDS);
    }

    public function count()
    {
        return $this->totalCount;
    }

    public function append($value)
    {
        if (empty($this->key)) {
            throw new \BadMethodCallException();
        }

        if (!is_scalar($value)) {
            throw new \BadMethodCallException();
        }

        $this->totalCount++;
        $this->currentCacheSize += strlen(strval($value));
        $this->linkedList->push($value);

        if ($this->currentCacheSize >= $this->maxCacheSize) {
            $this->commit();
        }
    }

    public function prepend($value)
    {
        if (empty($this->key)) {
            throw new \BadMethodCallException();
        }

        if (!is_scalar($value)) {
            throw new \BadMethodCallException();
        }

        $this->totalCount++;
        $this->currentCacheSize += strlen(strval($value));
        $this->linkedList->unshift($value);

        if ($this->currentCacheSize >= $this->maxCacheSize) {
            $this->commit();
        }
    }

    public function first()
    {
        if (empty($this->key)) {
            throw new \BadMethodCallException();
        }

        try {
            $this->maybeDecreaseCacheRotation();
        } catch (\OutOfBoundsException $e) {
            return null;
        }

        $data = $this->linkedList->shift();
        $this->currentCacheSize -= max(0, strlen(strval($data)));
        $this->totalCount--;

        return $data;
    }

    public function last()
    {
        if (empty($this->key)) {
            throw new \BadMethodCallException();
        }

        try {
            $this->maybeDecreaseCacheRotation();
        } catch (\OutOfBoundsException $e) {
            return null;
        }

        $data = $this->linkedList->pop();
        $this->currentCacheSize -= max(0, strlen(strval($data)));
        $this->totalCount--;

        return $data;
    }

    public function current()
    {
        return $this->linkedList->current();
    }

    protected function maybeDecreaseCacheRotation()
    {
        if ($this->linkedList->isEmpty()) {
            try {
                $data = wp_cache_get($this->cacheKeyPreviousRotation(), self::GROUP);

                if (empty($data)) {
                    throw new \OutOfBoundsException();
                }

                $this->updateCacheInfo($this->cacheRotation - 1);
                $this->linkedList->hydrate($data);
            } catch (\OutOfBoundsException $e) {
                throw $e;
            }
        }
    }

    public function reset()
    {
        if (empty($this->key)) {
            throw new \BadMethodCallException();
        }

        for ($i = $this->getCacheInfo()['highestRotation']; $i > 0; $i--) {
            wp_cache_delete($this->cacheKeyThisRotation());
        }

        wp_cache_delete($this->baseCacheKey());
        $this->initializeLinkedList();
    }

    protected function initializeLinkedList()
    {
        $this->linkedList = new JsonDoublyLinkedList();

        // Free up memory as it reads through the contents.
        $this->linkedList->setIteratorMode(JsonDoublyLinkedList::IT_MODE_DELETE);
    }

    public function getCache()
    {
        // no-op
    }

    public function reverse()
    {
        // no-op
    }
}
