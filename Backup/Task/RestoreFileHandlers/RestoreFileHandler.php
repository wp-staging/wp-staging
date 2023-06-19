<?php

namespace WPStaging\Backup\Task\RestoreFileHandlers;

use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class RestoreFileHandler
{
    /** @var FileRestoreTask */
    protected $fileRestoreTask;

    /** @var LoggerInterface */
    protected $logger;

    /** @var resource */
    protected $handle;

    /** @var Filesystem */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function setContext(FileRestoreTask $fileRestoreTask, LoggerInterface $logger)
    {
        $this->fileRestoreTask = $fileRestoreTask;
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
                call_user_func([$this->fileRestoreTask, 'getTaskTitle']),
                $filePath
            ));

            return false;
        }

        $locked = flock($this->handle, LOCK_EX | LOCK_NB);

        if (!$locked) {
            $this->logger->debug(sprintf(
                __('%s: Could not open lock file %s', 'wp-staging'),
                call_user_func([$this->fileRestoreTask, 'getTaskTitle']),
                $filePath
            ));

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
        }
    }
}
