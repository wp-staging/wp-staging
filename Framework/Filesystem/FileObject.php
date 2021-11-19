<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Framework\Filesystem;

use LimitIterator;
use SplFileObject;
use WPStaging\Core\WPStaging;

class FileObject extends SplFileObject
{
    const MODE_READ = 'rb'; // read only, binary
    const MODE_WRITE = 'wb'; // write only, binary
    const MODE_APPEND = 'ab'; // append with create, binary
    const MODE_APPEND_AND_READ = 'ab+'; // append with read and create if not exists, binary
    const MODE_WRITE_SAFE = 'xb'; // write if exists E_WARNING & return false, binary
    const MODE_WRITE_UNSAFE = 'cb'; // append, if exists cursor to top, binary

    const AVERAGE_LINE_LENGTH = 4096;

    private $existingMetadataPosition;

    public function __construct($fullPath, $openMode = self::MODE_READ)
    {
        $fullPath = untrailingslashit($fullPath);

        if (!file_exists($fullPath)) {
            (new Filesystem())->mkdir(dirname($fullPath));
        }

        try {
            parent::__construct($fullPath, $openMode);
        } catch (\Exception $e) {
            // If this fails, it will throw an exception.
            WPStaging::make(DiskWriteCheck::class)->testDiskIsWriteable();

            // If it didn't fail due to disk, re-throw
            throw $e;
        }
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

    /**
     * @return array The backup metadata array
     */
    public function readBackupMetadata()
    {
        $negativeOffset = 16 * KB_IN_BYTES;

        // Set the pointer to the end of the file, minus the negative offset for which to start looking for the backup metadata.
        $this->fseek(max($this->getSize() - $negativeOffset, 0), SEEK_SET);

        $backupMetadata = null;

        do {
            $this->existingMetadataPosition = $this->ftell();
            $line = trim($this->fgets());
            if (substr($line, 0, 1) === '{') {
                $backupMetadata = json_decode($line, true);
            }
        } while ($this->valid() && !is_array($backupMetadata));

        if (!is_array($backupMetadata)) {
            throw new \RuntimeException('Could not find metadata in the backup.');
        }

        return $backupMetadata;
    }

    public function getExistingMetadataPosition()
    {
        if ($this->existingMetadataPosition === null) {
            $this->readBackupMetadata();
        }

        return $this->existingMetadataPosition;
    }

    public function totalLines()
    {
        $currentKey = $this->key();
        $this->seek(PHP_INT_MAX);
        $total = $this->key();
        $this->seek($currentKey);
        return $total;
    }

    /**
     * Override SplFileObject::seek()
     * To make sure SplFileObject::seek() behaves identical in all PHP Versions. There was a major change in PHP 8.0.1.
     * @see https://bugs.php.net/bug.php?id=81551
     * This makes sure that the offset is always incremented by 1
     *
     * @param int $offset
     */
    public function seek($offset)
    {
        if (version_compare(PHP_VERSION, '8.0.1', '>=')) {
            // SplFileObject::seek() works only for INT offset, this make sure offset remains INT
            $offset = $offset === PHP_INT_MAX ? PHP_INT_MAX : $offset + 1;
            parent::seek($offset);
            return;
        }

        parent::seek($offset);
    }
}
