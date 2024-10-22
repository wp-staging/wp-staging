<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;

/**
 * Class FileObject
 *
 * For making codebase memory efficient and making sure on Windows OS, we don't run into permission issue due to file lock,
 * after working on the instance of this class, assign null to it.
 */
class FileObject extends AbstractFileObject
{
    const AVERAGE_LINE_LENGTH = 4096;

    /**
     * @throws DiskNotWritableException
     * @throws FilesystemExceptions
     */
    public function __construct($fullPath, $openMode = self::MODE_READ)
    {
        $fullPath = untrailingslashit($fullPath);

        if (empty($fullPath)) {
            throw new DiskNotWritableException("Empty path given. Please contact support@wp-staging.com");
        }

        if (!file_exists($fullPath)) {
            WPStaging::make(Filesystem::class)->mkdir(dirname($fullPath), true);
        }

        try {
            parent::__construct($fullPath, $openMode);
        } catch (\Exception $e) {
            // If this fails, it will throw an exception.
            WPStaging::make(DiskWriteCheck::class)->testDiskIsWriteable();

            // If it didn't fail due to disk write check, re-throw
            throw $e;
        }
    }

    /**
     * @param string $str
     * @param int|null $length
     * @return false|int False on error, number of bytes written on success
     */
    public function fwriteSafe(string $str, $length = null)
    {
        // Not sure if we need mbstring_binary_safe_encoding. If not, delete it as we already open file with binary mode.
        mbstring_binary_safe_encoding();

        $strLen       = strlen($str);
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
    public function readBottomLines(int $lines): array
    {
        $this->seek(PHP_INT_MAX);
        $lastLine = $this->key();
        $offset   = $lastLine - $lines;
        if ($offset < 0) {
            $offset = 0;
        }

        $allLines = new \LimitIterator($this, $offset, $lastLine);
        return array_reverse(array_values(iterator_to_array($allLines)));
    }

    protected function isWindowsOs(): bool
    {
        return WPStaging::isWindowsOs();
    }
}
