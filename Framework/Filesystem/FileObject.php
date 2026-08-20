<?php

namespace WPStaging\Framework\Filesystem;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;







class FileObject extends AbstractFileObject
{
    const AVERAGE_LINE_LENGTH = 4096;





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
 
            WPStaging::make(DiskWriteCheck::class)->testDiskIsWriteable();

 
            throw $e;
        }
    }






    public function fwriteSafe(string $str, $length = null)
    {
 
        mbstring_binary_safe_encoding();

        $strLen       = strlen($str);
        $writtenBytes = $length !== null ? $this->fwrite($str, $length) : $this->fwrite($str);
        reset_mbstring_encoding();

        if ($strLen !== $writtenBytes) {
            return false;
        }

        return $writtenBytes;
    }








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
