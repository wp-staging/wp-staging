<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Queue;

use WPStaging\Framework\Queue\Storage\StorageInterface;

interface QueueInterface
{
    /**
     * @param string $name
     *
     * @return void
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param StorageInterface $storage
     *
     * @return self
     */
    public function setStorage(StorageInterface $storage);

    /**
     * @return StorageInterface
     */
    public function getStorage();

    /**
     * Count all items in the queue
     *
     * @return int
     */
    public function count();

    /**
     * Remove and get the first element from the queue
     * @return mixed
     */
    public function pop();

    /**
     * Append item to the end of the queue
     *
     * @param mixed $value
     */
    public function push($value);

    /**
     * Push array of items to the end of the queue
     * @param array $value
     */
    public function pushAsArray(array $value = []);

    /**
     * Add item to the beginning of the queue
     *
     * @param mixed $value
     */
    public function prepend($value);

    /**
     * Removes all the items from the queue
     */
    public function reset();

    /**
     * Save items in the queue
     */
    public function save();
}
