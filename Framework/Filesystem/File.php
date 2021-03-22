<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Framework\Filesystem;

use LimitIterator;
use SplFileObject;

class File extends SplFileObject
{
    const MODE_READ = 'rb'; // read only, binary
    const MODE_WRITE = 'wb'; // write only, binary
    const MODE_APPEND = 'ab'; // append with create, binary
    const MODE_WRITE_SAFE = 'xb'; // write if exists E_WARNING & return false, binary
    const MODE_WRITE_UNSAFE = 'cb'; // append, if exists cursor to top, binary

    const AVERAGE_LINE_LENGTH = 4096;
    const MAX_LENGTH_PER_IOP = 512000; // Max Length Per Input Output Operations

    public function __construct($fullPath, $openMode = self::MODE_READ)
    {

        if (!file_exists($fullPath)) {
            (new Filesystem)->mkdir(dirname($fullPath));
        }

        parent::__construct($fullPath, $openMode);
    }

    // Not sure if we need this, if not, delete it as we already open file with binary mode.
    public function fwriteSafe($str, $length = null)
    {
        mbstring_binary_safe_encoding();
        $strLen = strlen($str);
        $writtenBytes = $length !== null ? $this->fwrite($str, $length) : $this->fwrite($str);
        reset_mbstring_encoding();

        if ($strLen !== $writtenBytes) {
            return false;
        }

        return $writtenBytes;
    }

    /**
     * @param int $lines
     * @return array
     */
    public function readBottomLines($lines)
    {
        $this->seek(PHP_INT_MAX);
        $lastLine = $this->key();
        $offset = $lastLine - $lines;
        if ($offset < 0) {
            $offset = 0;
        }

        $allLines = new LimitIterator($this, $offset, $lastLine);
        return array_reverse(array_values(iterator_to_array($allLines)));
    }

    public function totalLines()
    {
        $currentKey = $this->key();
        $this->seek(PHP_INT_MAX);
        $total = $this->key();
        $this->seek($currentKey);
        return $total;
    }
}
