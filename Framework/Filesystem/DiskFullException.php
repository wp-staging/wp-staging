<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Framework\Filesystem;

use RuntimeException;

class DiskFullException extends RuntimeException
{
    public function __construct($message = 'Failed to write to disk. Disk probably is full')
    {
        parent::__construct($message);
    }
}
