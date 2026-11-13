<?php

namespace WPStaging\Backup\Exceptions;

 
class UploadThrottledException extends StorageException
{
    private $retryAfter;

    public function __construct(string $retryAfter, string $reason = 'The storage provider has rate limited the upload.')
    {
        parent::__construct($reason);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): string
    {
        return $this->retryAfter;
    }
}
