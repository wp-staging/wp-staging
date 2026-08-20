<?php

namespace WPStaging\Framework\Job\Ajax;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Job\ProcessLock;
use WPStaging\Framework\Logger\SseEventCache;
use WPStaging\Framework\Security\Auth;

abstract class PrepareJob
{
 
    protected $auth;

 
    protected $filesystem;

 
    protected $directory;

 
    protected $processLock;

 
    protected $queueId = '';

    public function __construct(Filesystem $filesystem, Directory $directory, Auth $auth, ProcessLock $processLock)
    {
        $this->directory   = $directory;
        $this->filesystem  = $filesystem;
        $this->auth        = $auth;
        $this->processLock = $processLock;
    }

    abstract public function prepare($data = null);

    abstract public function ajaxPrepare($data);

    abstract public function persist(): bool;

    abstract public function getJob();

    abstract public function validateAndSanitizeData($data): array;



















    protected function setupInitialJob(...$args): array
    {
        $sanitizedData = $this->setupInitialData(...$args);
        $this->persist();

        return $sanitizedData;
    }

    protected function clearCacheFolder()
    {
        $this->filesystem->setExcludePaths(['*.*', '!*.cache.php', '!*.cache', '!*.wpstg']);
        $this->filesystem->delete($this->directory->getCacheDirectory(), $deleteSelf = false);
        $this->filesystem->setExcludePaths([]);
        $this->filesystem->mkdir($this->directory->getCacheDirectory(), true);
    }




    protected function deleteSseCacheFiles()
    {
 
        $sseCacheEvents = WPStaging::make(SseEventCache::class);
        $sseCacheEvents->deleteSseCacheFiles();
    }

    public function setQueueId(string $queueId)
    {
        $this->queueId = $queueId;
    }






    protected function jsBoolean($value)
    {
        return $value === 'true' || $value === true;
    }





    abstract protected function setupInitialData($sanitizedData): array;
}
