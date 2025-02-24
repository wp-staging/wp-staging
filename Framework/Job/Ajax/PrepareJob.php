<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Security\Auth;

abstract class PrepareJob
{
    protected $auth;
    protected $filesystem;
    protected $directory;
    protected $processLock;

    public function __construct(Filesystem $filesystem, Directory $directory, Auth $auth, ProcessLock $processLock)
    {
        $this->directory = $directory;
        $this->filesystem = $filesystem;
        $this->auth = $auth;
        $this->processLock = $processLock;
    }

    abstract public function prepare($data = null);

    abstract public function ajaxPrepare($data);

    abstract public function persist(): bool;

    abstract public function getJob();

    abstract public function validateAndSanitizeData($data): array;

    protected function clearCacheFolder()
    {
        $this->filesystem->setExcludePaths(['*.*', '!*.cache.php', '!*.cache', '!*.wpstg']);
        $this->filesystem->delete($this->directory->getCacheDirectory(), $deleteSelf = false);
        $this->filesystem->setExcludePaths([]);
        $this->filesystem->mkdir($this->directory->getCacheDirectory(), true);
    }

    /**
     * @param mixed $value A value that we want to detect if it's true or false.
     *
     * @return bool A PHP boolean interpretation of this value.
     */
    protected function jsBoolean($value)
    {
        return $value === 'true' || $value === true;
    }
}
