<?php

namespace WPStaging\Framework\Queue;

use SplFileObject;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;

class FileSeekableQueue implements SeekableQueueInterface, \SeekableIterator
{
    /** @var string The string identifier of this task */
    protected $taskName;

    /** @var SplFileObject The file resource that persists this queue */
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
        $this->directory = $directory;
        $this->filesystem = $filesystem;
    }

    public function __destruct()
    {
        if ($this->needsUnlock && $this->handle instanceof \SplFileObject) {
            try {
                $this->handle->flock(LOCK_UN);
            } catch (\Exception $e) {
                // no-op
            }
        }
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

        if (!file_exists($path)) {
            touch($path);
        }

        // Developer exception
        if ($queueMode !== SeekableQueueInterface::MODE_WRITE && $queueMode !== SeekableQueueInterface::MODE_READ_WRITE) {
            throw new \BadMethodCallException();
        }

        $this->handle = new SplFileObject($path, $queueMode);
        $this->handle->setFlags(SplFileObject::DROP_NEW_LINE);
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
                        throw new \RuntimeException('Could not acquire exclusive lock for writing to Queue file.');
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
            yield $this->handle->fgets();
        }
    }

    public function current()
    {
        return $this->fileGenerator->current();
    }

    public function next()
    {
        $this->fileGenerator->next();
    }

    public function key()
    {
        return $this->fileGenerator->key();
    }

    public function valid()
    {
        return $this->fileGenerator->valid();
    }

    public function rewind()
    {
        $this->handle->fseek(0);
    }

    public function seek($offset)
    {
        $this->handle->fseek($offset);
    }

    public function isFinished()
    {
        return $this->handle->eof();
    }

    public function retry($dequeue = true)
    {
        $this->seek($this->offsetBefore);

        if ($dequeue) {
            return $this->dequeue();
        }
    }

    public function enqueue($data)
    {
        // Early bail: Write-only optimization
        if ($this->isWriteOnly) {
            $this->handle->fwrite(trim($data) . PHP_EOL);

            return $this->handle->ftell();
        }

        $this->handle->fseek(0, SEEK_END);
        $this->handle->flock(LOCK_EX);
        $this->handle->fwrite(trim($data) . PHP_EOL);
        $this->handle->flock(LOCK_UN);

        return $this->handle->ftell();
    }

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

    public function getOffset()
    {
        return $this->handle->ftell();
    }
}
