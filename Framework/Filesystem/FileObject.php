<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Framework\Filesystem;

use Exception;
use LimitIterator;
use RuntimeException;
use SplFileObject;
use WPStaging\Core\WPStaging;
use WPStaging\Pro\Backup\Exceptions\DiskNotWritableException;

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

    /** @var int */
    private $totalLines = null;

    /**
     * Lock File Handle for Windows
     * @var resource
     */
    private $lockHandle = null;

    /**
     * @throws DiskNotWritableException
     */
    public function __construct($fullPath, $openMode = self::MODE_READ)
    {
        $fullPath = untrailingslashit($fullPath);

        if (!file_exists($fullPath)) {
            (new Filesystem())->mkdir(dirname($fullPath));
        }

        try {
            parent::__construct($fullPath, $openMode);
        } catch (Exception $e) {
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
     * @throws Exception
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
            throw new RuntimeException('Could not find metadata in the backup.');
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

    /**
     * @return mixed int|null
     * @throws Exception
     */
    public function totalLines()
    {
        if ($this->totalLines !== null) {
            return $this->totalLines;
        }

        $currentKey = $this->key();
        $this->seek(PHP_INT_MAX);
        $this->totalLines = $this->key();
        $this->seek($currentKey);
        return $this->totalLines;
    }

    /**
     * Override SplFileObject::seek()
     * Alternative function for SplFileObject::seek() that behaves identical in all PHP Versions.
     *
     * There was a major change in PHP 8.0.1 where after using `SplFileObject::seek($line)`, the first subsequent
     * call to `SplFileObject::fgets()` does not increase the line pointer anymore as it did in earlier version since PHP 5.x
     * @see https://bugs.php.net/bug.php?id=81551
     *
     * Note: This will remove READ_AHEAD flag while execution to deliver reliable and identical results as READ_AHEAD tells
     * SplFileObject to read on next() and rewind() too which our custom seek() makes use of.
     * This would disturb this seek() implementation and would lead to fatal errors if 'cpu load' setting is 'medium' or 'high'
     *
     *
     * @param int $offset The zero-based line number to seek to.
     * @throws Exception
     */
    #[\ReturnTypeWillChange]
    public function seek($offset)
    {
        if ($offset < 0) {
            throw new Exception("Can't seek file: " . $this->getPathname() . " to negative offset: $offset");
        }

        if ($offset === 0) {
            parent::seek(0);
            return;
        }

        if ($offset !== PHP_INT_MAX) {
            $offset += 1;
        }

        if ($this->totalLines !== null && $offset >= $this->totalLines) {
            $offset -= 1;
        }

        $originalFlags = $this->getFlags();
        $newFlags = $originalFlags & ~self::READ_AHEAD;
        $this->setFlags($newFlags);

        $this->rewind();
        for ($i = 0; $i < $offset; $i++) {
            $this->next();
            $this->fgets();
            if ($this->eof()) {
                $this->totalLines = $this->key();
                break;
            }
        }

        $this->setFlags($originalFlags);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function flock($operation, &$wouldBlock = null)
    {
        if (!WPStaging::isWindowsOs()) {
            return parent::flock($operation, $wouldBlock);
        }

        // create a lock file for Windows
        $lockFileName = untrailingslashit($this->getPathname()) . '.lock';
        $this->lockHandle = fopen($lockFileName, 'c');

        if (!is_resource($this->lockHandle)) {
            throw new RuntimeException("Could not open lock file {$this->getPathname()}");
        }

        return flock($this->lockHandle, $operation, $wouldBlock);
    }

    /**
     * Release Lock if Windows OS
     */
    public function releaseLock()
    {
        if (!WPStaging::isWindowsOs() || $this->lockHandle === null) {
            return;
        }

        $lockFileName = untrailingslashit($this->getPathname()) . '.lock';
        if (is_file($lockFileName)) {
            unlink($lockFileName);
        }

        flock($this->lockHandle, LOCK_UN);
        fclose($this->lockHandle);
        $this->lockHandle = null;
    }

    public function __destruct()
    {
        $this->releaseLock();
    }
}
