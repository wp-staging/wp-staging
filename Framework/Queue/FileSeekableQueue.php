<?php

namespace WPStaging\Framework\Queue;

use Error;
use Exception;
use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Filesystem\Filesystem;

use function WPStaging\functions\debug_log;

class FileSeekableQueue implements SeekableQueueInterface, \SeekableIterator
{
    /** @var string The string identifier of this task */
    protected $taskName;

    /** @var FileObject The file resource that persists this queue */
    protected $handle;

    /** @var \Generator */
    protected $fileGenerator;

    /** @var Directory */
    protected $directory;

    /** @var Filesystem */
    protected $filesystem;

    /** @var int */
    protected $offsetBefore;

    /** @var bool */
    protected $needsUnlock = false;

    /** @var bool Whether the Queue is in write-only mode. */
    protected $isWriteOnly;

    public function __construct(Directory $directory, Filesystem $filesystem)
    {
        $this->directory  = $directory;
        $this->filesystem = $filesystem;
    }

    public function __destruct()
    {
        $this->shutdown();
    }

    /**
     * @param        $taskName
     * @param string $queueMode Either opens the Queue for read and write, or optimized to write-only.
     */
    public function setup($taskName, $queueMode = SeekableQueueInterface::MODE_READ_WRITE)
    {
        $this->taskName = $taskName;

        $path = "{$this->directory->getCacheDirectory()}$taskName.cache";

        $this->filesystem->mkdir(dirname($path), true);

        if (!file_exists($path) && !touch($path)) {
            debug_log("Check if there is enough free space and the file permissions are 644 or 755. Could not create file: $path");
            throw new \RuntimeException(sprintf(esc_html__("Check if there is enough free space and the file permissions are 644 or 755. Could not create file: %s", 'wp-staging'), $path));
        }

        // Developer exception
        if ($queueMode !== SeekableQueueInterface::MODE_WRITE && $queueMode !== SeekableQueueInterface::MODE_READ_WRITE) {
            throw new \BadMethodCallException();
        }
        $this->handle = new FileObject($path, $queueMode);
        $this->handle->setFlags(FileObject::DROP_NEW_LINE);
        $this->fileGenerator = $this->initializeGenerator();

        $this->isWriteOnly = $queueMode === SeekableQueueInterface::MODE_WRITE;

        if ($this->isWriteOnly) {
            $waitedTimes = 0;
            do {
                $wouldBlock = false;

                /*
                 * Windows does not support LOCK_NB (Advisory locking), so we read from the return of flock.
                 * Unix supports LOCK_NB, so we read from the second parameter of flock.
                 */
                $locked = $this->handle->flock(LOCK_EX | LOCK_NB, $wouldBlock) || (bool)!$wouldBlock;

                if (!$locked) {
                    usleep(250000); // 0.25s
                    $waitedTimes++;
                    if ($waitedTimes > 5) {
                        throw new \RuntimeException(esc_html__('Could not acquire exclusive lock for writing to Queue file: ' . $this->taskName . '.task'));
                    }
                }
            } while (!$locked);

            $this->needsUnlock = true;
        }
    }

    protected function initializeGenerator()
    {
        while ($this->handle->valid()) {
            $this->offsetBefore = $this->handle->ftell();
            yield $this->handle->readAndMoveNext();
        }
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->fileGenerator->current();
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $this->fileGenerator->next();
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->fileGenerator->key();
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->fileGenerator->valid();
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->handle->fseek(0);
    }

    #[\ReturnTypeWillChange]
    public function seek($offset)
    {
        $this->handle->fseek($offset);
    }

    public function isFinished()
    {
        return $this->handle->eof();
    }

    /**
     * @param $dequeue
     * @return mixed|void
     */
    public function retry($dequeue = true)
    {
        $this->seek($this->offsetBefore);

        if ($dequeue) {
            return $this->dequeue();
        }
    }

    /**
     * @param $data
     * @return false|int
     */
    public function enqueue($data)
    {
        // Early bail: Write-only optimization
        if ($this->isWriteOnly) {
            $this->handle->fwrite(trim($data) . PHP_EOL);

            return $this->handle->ftell();
        }

        $currentOffset = $this->handle->ftell();

        $this->handle->fseek(0, SEEK_END);
        $this->handle->flock(LOCK_EX);
        $this->handle->fwrite(trim($data) . PHP_EOL);
        $this->handle->flock(LOCK_UN);

        $offsetEndOfQueue = $this->handle->ftell();
        $this->handle->fseek($currentOffset);

        return $offsetEndOfQueue;
    }

    /**
     * @return mixed
     */
    public function dequeue()
    {
        if ($this->isWriteOnly) {
            throw new \BadMethodCallException('Trying to read from read-only Queue');
        }

        $first = is_null($this->offsetBefore);

        if (!$first) {
            $this->next();
        }

        return $this->current();
    }

    /**
     * @param array $data
     * @return false|int
     */
    public function enqueueMany(array $data = [])
    {
        foreach ($data as $item) {
            if (is_scalar($item)) {
                $this->enqueue((string)$item);
            }
        }

        return $this->handle->ftell();
    }

    public function reset()
    {
        $this->handle->ftruncate(0);
    }

    /**
     * @return false|int
     */
    public function getOffset()
    {
        if (!isset($this->handle) || !$this->handle instanceof FileObject) {
            return false;
        }

        return $this->handle->ftell();
    }

    /**
     * @return void
     */
    public function shutdown()
    {
        if ($this->needsUnlock && $this->handle instanceof FileObject) {
            $this->unlockObject();
            return;
        }
    }

    protected function unlockObject()
    {
        try {
            $this->handle->flock(LOCK_UN);
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($message !== 'Object not initialized') {
                debug_log("Unable to unlock handle " . $this->taskName . '.task : ' . $message, Logger::TYPE_DEBUG);
            }
        } catch (Error $e) {
            $message = $e->getMessage();
            if ($message !== 'Object not initialized') {
                debug_log("Unable to unlock handle " . $this->taskName . '.task : ' . $message, Logger::TYPE_DEBUG);
            }
        }
    }
}
