<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Queue;

use WPStaging\Framework\Queue\Storage\CacheStorage;
use WPStaging\Framework\Queue\Storage\StorageInterface;
use WPStaging\Backup\Task\AbstractTask;

/**
 * @todo this class in not used anymore. 14.12.2022 We will delete this class in version 4.4.2
 */

class Queue implements QueueInterface
{
    /** @var string */
    private $name;

    /** @var StorageInterface|CacheStorage */
    private $storage;

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->init();
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
        $this->init();

        return $this;
    }

    /**
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return $this->storage->count();
    }

    /**
     * Returns the first element of the storage, without advancing the pointer.
     *
     * @return mixed|AbstractTask|null
     */
    public function current()
    {
        return $this->storage->current();
    }

    /**
     * @inheritDoc
     */
    public function pop()
    {
        return $this->storage->first();
    }

    public function last()
    {
        return $this->storage->last();
    }

    /**
     * @inheritDoc
     */
    public function push($value)
    {
        $this->storage->append($value);
    }

    /**
     * @inheritDoc
     */
    public function pushAsArray(array $value = [])
    {
        foreach ($value as $item) {
            $this->storage->append($item);
        }
    }

    /**
     * @inheritDoc
     */
    public function prepend($value)
    {
        $this->storage->prepend($value);
    }

    protected function init()
    {
        if (!$this->name || !$this->storage) {
            return;
        }
        $this->storage->setKey($this->name);
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->storage->reset();
    }

    /**
     * @inheritDoc
     */
    public function reverse()
    {
        $this->storage->reverse();
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $this->storage->commit();
    }
}
