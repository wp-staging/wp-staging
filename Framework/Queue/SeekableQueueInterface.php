<?php

namespace WPStaging\Framework\Queue;

interface SeekableQueueInterface
{
    /** @var string Write-only queue. Optimized for writing. */
    const MODE_WRITE = 'ab';

    /** @var string Read/write queue. Versatile, but a little bit slower. */
    const MODE_READ_WRITE = 'rb+';

    public function setup($queueName, $queueMode = SeekableQueueInterface::MODE_READ_WRITE);

    /**
     * Whether the Queue has more items.
     *
     * @return bool
     */
    public function isFinished();

    /**
     * Returns the current element in the Queue and move the pointer to the next.
     *
     * @return mixed
     */
    public function dequeue();

    /**
     * Append item to the end of the queue
     *
     * @param mixed $data
     */
    public function enqueue($data);

    /**
     * Push array of items to the end of the queue
     *
     * @param array $data
     */
    public function enqueueMany(array $data = []);

    /**
     * Rollback the pointer to repeat the item that was just returned.
     *
     * @return mixed
     */
    public function retry($dequeue = true);

    /**
     * Removes all the items from the queue
     */
    public function reset();

    /**
     * Seek the Queue to given offset
     */
    public function seek($offset);

    /**
     * Returns the Queue offset
     */
    public function getOffset();

    /**
     * Close all connection to the queue
     */
    public function shutdown();
}
