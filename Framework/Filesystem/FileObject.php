<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Framework\Filesystem;

use Exception;
use LimitIterator;
use RuntimeException;
use SplFileObject;
use WPStaging\Core\WPStaging;
use WPStaging\functions;
use WPStaging\Backup\Exceptions\DiskNotWritableException;

use function tad\WPBrowser\debug;
use function WPStaging\functions\debug_log;

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

    /** @var bool */
    private $fgetsUsedOnKey0 = false;

    /** @var bool */
    private $fseekUsed = false;

    /**
     * Lock File Handle for Windows
     * @var resource
     */
    private $lockHandle = null;

    /**
     * @throws DiskNotWritableException
     * @throws FilesystemExceptions
     */
    public function __construct($fullPath, $openMode = self::MODE_READ)
    {
        $fullPath = untrailingslashit($fullPath);

        if (!file_exists($fullPath)) {
            WPStaging::make(Filesystem::class)->mkdir(dirname($fullPath), true);
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

    /**
     * @param $str
     * @param $length
     * @return false|int False on error, number of bytes written on success
     */
    public function fwriteSafe($str, $length = null)
    {
        // Not sure if we need mbstring_binary_safe_encoding. If not, delete it as we already open file with binary mode.
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
     *
     * @throws Exception
     * @todo DRY /Framework/Utils/Cache/BufferedCache.php
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
     * @throws RuntimeException
     */
    public function readBackupMetadata()
    {
        // Default max size 128KB for backup metadata
        $maxBackupMetadataSize = apply_filters('wpstg_max_backup_metadata_size', 128 * KB_IN_BYTES);
        // Make sure the max size is never above 1MB
        $negativeOffset = min($maxBackupMetadataSize, 1 * MB_IN_BYTES);
        // Make sure the max size is never below 32KB
        $negativeOffset = max($negativeOffset, 32 * KB_IN_BYTES);

        // Set the pointer to the end of the file, minus the negative offset for which to start looking for the backup metadata.
        $this->fseek(max($this->getSize() - $negativeOffset, 0), SEEK_SET);

        $backupMetadata = null;

        do {
            $this->existingMetadataPosition = $this->ftell();
            $line = trim($this->readAndMoveNext());
            if ($this->isValidMetadata($line)) {
                $backupMetadata = $this->extractMetadata($line);
            }
        } while ($this->valid() && !is_array($backupMetadata));

        if (!is_array($backupMetadata)) {
            $error = sprintf('Could not find metadata in the backup file %s - This file could be corrupt.', $this->getFilename());
            throw new RuntimeException($error);
        }

        return $backupMetadata;
    }

    public function extractMetadata($line)
    {
        if (!$this->isSqlFile()) {
            return json_decode($line, true);
        }

        return json_decode(substr($line, 3), true);
    }

    /**
     * @param string $line
     * @return bool
     *
     * @todo Move all metadata related function out of FileObject
     */
    public function isValidMetadata($line)
    {
        if ($this->isSqlFile() && substr($line, 3, 1) !== '{') {
            return false;
        } elseif (!$this->isSqlFile() && substr($line, 0, 1) !== '{') {
            return false;
        }

        $maybeMetadata = $this->extractMetadata($line);

        if (!is_array($maybeMetadata) || !array_key_exists('networks', $maybeMetadata) || !is_array($maybeMetadata['networks'])) {
            return false;
        }

        $network = $maybeMetadata['networks']['1'];
        if (!is_array($network) || !array_key_exists('blogs', $network) || !is_array($network['blogs'])) {
            return false;
        }

        return true;
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

        $this->fseekUsed = false;
        $this->fgetsUsedOnKey0 = false;
        if ($offset === 0 || version_compare(PHP_VERSION, '8.0.1', '<')) {
            parent::seek($offset);
            return;
        }

        $offset -= 1;

        if ($this->totalLines !== null && $offset >= $this->totalLines) {
            $offset += 1;
        }

        $originalFlags = $this->getFlags();
        $newFlags = $originalFlags & ~self::READ_AHEAD;
        $this->setFlags($newFlags);

        parent::seek($offset);

        if ($this->eof()) {
            $this->current();
            $this->totalLines = $this->key();
            return;
        }

        $this->current();
        $this->next();
        $this->current();

        $this->setFlags($originalFlags);
    }

    /**
     * SplFileObject::fgets() is not consistent after SplFileObject::fseek() between php 5.x/7.x and php 8.0.1.
     * We could either make fgets consistent after SplFileObject::seek() or SplFileObject::fseek()
     * This implementation makes it consistent after SplFileObject::seek across all PHP versions up to 8.0.1.
     * Use readAndMoveNext() instead if you want to achieve consistent behavior of SplFileObject::fgets after SplFileObject::fseek.
     *
     * @deprecated 4.2.13 Use readAndMoveNext instead as it is hard to make fgets against multiple php version after seek(0)
     *
     * @return string
     */
    #[\ReturnTypeWillChange]
    public function fgets()
    {
        if ($this->key() === 0 || version_compare(PHP_VERSION, '8.0.1', '<')) {
            $this->fgetsUsedOnKey0 = true;
            return parent::fgets();
        }

        $originalFlags = $this->getFlags();
        $newFlags = $originalFlags & ~self::READ_AHEAD;
        $this->setFlags($newFlags);

        $line = $this->current();
        $this->next();

        if (version_compare(PHP_VERSION, '8.0.19', '<')) {
            $line = $this->current();
        }

        if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
            $line = $this->current();
        }

        if (!$this->fseekUsed) {
            $line = $this->current();
        }

        $this->setFlags($originalFlags);
        return $line;
    }

    /**
     * Gets the current line number
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        if (!$this->fgetsUsedOnKey0 || version_compare(PHP_VERSION, '8.0.19', '<')) {
            return parent::key();
        }

        if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
            return parent::key();
        }

        return parent::key() - 1;
    }

    /**
     * Seek to a position
     *
     * @param int $offset The value to start from added to the $whence
     * @param int $whence values are:
     * SEEK_SET - Set position equal to offset bytes.
     * SEEK_CUR - Set position to current location plus offset.
     * SEEK_END - Set position to end-of-file plus offset.
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function fseek($offset, $whence = SEEK_SET)
    {
        if (version_compare(PHP_VERSION, '8.0.19', '<')) {
            return parent::fseek($offset, $whence);
        }

        if (version_compare(PHP_VERSION, '8.1', '>') && version_compare(PHP_VERSION, '8.1.6', '<')) {
            return parent::fseek($offset, $whence);
        }

        // After calling parent::fseek() and $this->>fgets() two or three times it starts to act different on PHP >= 8.0.19, PHP >= 8.1.6 and PHP >= 8.2.
        // Calling it three times helps to write a consistent fseek() for the above mentioned PHP versions.
        for ($i = 0; $i < 3; $i++) {
            parent::fseek(0);
            $this->fgets();
        }

        $this->fseekUsed = true;
        return parent::fseek($offset, $whence);
    }


    /**
     * SplFileObject::fgets() is not consistent after SplFileObject::fseek() between php 5.x/7.x and php 8.0.1.
     * Use this method instead if you want to achieve consistent behavior of SplFileObject::fgets after SplFileObject::fseek across all PHP versions up to PHP 8.0.1.
     * READ_AHEAD flag will not have any affect on this method. It's disabled.
     *
     * @var bool $useFgets default false. Setting this to true will use fgets on PHP < 8.0.1
     *
     * @return string
     */
    public function readAndMoveNext($useFgets = false)
    {
        if ($useFgets && version_compare(PHP_VERSION, '8.0.1', '<')) {
            return parent::fgets();
        }

        $originalFlags = $this->getFlags();
        $newFlags = $originalFlags & ~self::READ_AHEAD;
        $this->setFlags($newFlags);

        $line = $this->current();
        $this->next();

        $this->setFlags($originalFlags);
        return $line;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function flock($operation, &$wouldBlock = null)
    {
        if (!WPStaging::isWindowsOs()) {
            if (!is_callable('parent::flock')) {
                return false;
            }

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

    public function isSqlFile()
    {
        return $this->getExtension() === 'sql';
    }
}
