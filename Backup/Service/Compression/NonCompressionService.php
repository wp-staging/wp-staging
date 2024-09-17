<?php

namespace WPStaging\Backup\Service\Compression;

use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Framework\Filesystem\FileObject;

class NonCompressionService implements CompressionInterface
{
    /**
     * No compression, just return the string as it is.
     * @param string $string
     * @return string
     */
    public function compress(string $string): string
    {
        return $string;
    }

    /**
     * No compression, just return the string as it is.
     * @param string $string
     * @return string
     */
    public function decompress(string $string): string
    {
        return $string;
    }

    /**
     * Read the chunk from the file.
     * No Compression
     * @param FileObject         $wpstgFile
     * @param FileBeingExtracted $extractingFile
     * @param callable|null      $callable
     * @return string
     */
    public function readChunk(FileObject $wpstgFile, FileBeingExtracted $fileBeingExtracted, $callable = null): string
    {
        return $wpstgFile->fread($fileBeingExtracted->findReadTo());
    }
}
