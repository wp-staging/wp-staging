<?php

namespace WPStaging\Framework\Job\Task\FileHandler;

use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\Interfaces\FileTaskInterface;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class FileHandler
{
    /** @var FileTaskInterface */
    protected $fileTask;

    /** @var LoggerInterface */
    protected $logger;

    /** @var resource|null */
    protected $handle;

    /** @var Filesystem */
    protected $filesystem;

    /** @var string */
    protected $processTitle;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function setContext(FileTaskInterface $fileTask, LoggerInterface $logger)
    {
        $this->fileTask = $fileTask;
        $this->logger = $logger;
    }

    abstract public function handle($source, $destination);

    protected function lock($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $this->handle = @fopen($filePath, is_dir($filePath) ? 'r' : 'rb+');

        if (!$this->handle) {
            $this->logger->debug(sprintf(
                __('%s: Could not open handle for locking file %s', 'wp-staging'),
                call_user_func([$this->fileTask, 'getTaskTitle']),
                $filePath
            ));

            $this->handle = null;

            return false;
        }

        $locked = flock($this->handle, LOCK_EX | LOCK_NB);

        if (!$locked) {
            $this->logger->debug(sprintf(
                __('%s: Could not open lock file %s', 'wp-staging'),
                call_user_func([$this->fileTask, 'getTaskTitle']),
                $filePath
            ));

            $this->handle = null;

            return false;
        }

        return true;
    }

    protected function unlock()
    {
        if (isset($this->handle) && $this->handle) {
            flock($this->handle, LOCK_UN);
            fclose($this->handle);
            unset($this->handle);
            $this->handle = null;
        }
    }
}
