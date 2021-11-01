<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Utils\Cache\AbstractCache;

class ArrayStorage implements StorageInterface
{
    /** @var array|null */
    private $items;

    public function commit()
    {
        \WPStaging\functions\debug_log('ArrayStorage does not implement commit.');
    }

    /**
     * This does nothing due to nature of ArrayStorage
     * @inheritDoc
     */
    public function setKey($key)
    {
        \WPStaging\functions\debug_log('ArrayStorage does not implement setKey.');

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count((array) $this->items);
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

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->items = [];
    }

    /**
     * @return AbstractCache|null
     */
    public function getCache()
    {
        return null;
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
