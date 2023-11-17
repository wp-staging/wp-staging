<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Security\Auth;
use WPStaging\Backup\BackupProcessLock;

abstract class PrepareJob
{
    protected $auth;
    protected $filesystem;
    protected $directory;
    protected $processLock;

    public function __construct(Filesystem $filesystem, Directory $directory, Auth $auth, BackupProcessLock $processLock)
    {
        $this->directory = $directory;
        $this->filesystem = $filesystem;
        $this->auth = $auth;
        $this->processLock = $processLock;
    }

    abstract public function prepare($data = null);

    abstract public function ajaxPrepare($data);

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
