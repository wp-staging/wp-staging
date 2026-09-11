<?php

namespace WPStaging\Backup\Exceptions;

 
class UploadThrottledException extends StorageException
{
    private $retryAfter;

    public function __construct(string $retryAfter)
    {
        parent::__construct('The storage provider has rate limited the upload.');
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): string
    {
        return $this->retryAfter;
    }
}
