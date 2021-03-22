<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Queue\Storage;

use WPStaging\Framework\Utils\Cache\AbstractCache;

interface StorageInterface
{
    /**
     * Set queue key to be used within other methods
     *
     * @param string $key
     *
     * @return self
     */
    public function setKey($key);

    /**
     * Count all items in the given queue
     * @return int
     */
    public function count();

    /**
     * Appends item to the queue
     *
     * @param mixed $value
     *
     * @return void
     */
    public function append($value);

    /**
     * Prepends item to the queue
     *
     * @param mixed $value
     *
     * @return void
     */
    public function prepend($value);

    /**
     * Removes and returns the first item from the queue
     * @return mixed
     */
    public function first();

    /**
     * Removes and returns the last item from the queue
     * @return mixed
     */
    public function last();

    /**
     * Removes all the items from the queue
     */
    public function reset();

    /**
     * @return AbstractCache|null
     */
    public function getCache();
}
