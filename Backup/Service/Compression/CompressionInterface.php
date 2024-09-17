<?php

namespace WPStaging\Backup\Service\Compression;

use RuntimeException;
use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Framework\Filesystem\FileObject;

interface CompressionInterface
{
    /**
     * @param string $string String to compress.
     * @return string Compressed string on success, throws an exception on failure.
     * @throws RuntimeException
     */
    public function compress(string $string): string;

    /**
     * @param string $string String to decompress.
     * @return string Decompressed string on success, throws an exception on failure.
     * @throws RuntimeException
     */
    public function decompress(string $string): string;

    /**
     * @param FileObject         $wpstgFile
     * @param FileBeingExtracted $extractingFile
     * @param callable|null      $callable
     * @return string
     */
    public function readChunk(FileObject $wpstgFile, FileBeingExtracted $fileBeingExtracted, $callable = null): string;
}
