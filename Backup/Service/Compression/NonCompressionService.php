<?php

namespace WPStaging\Backup\Service\Compression;

use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Backup\Exceptions\EmptyChunkException;
use WPStaging\Framework\Filesystem\FileObject;

class NonCompressionService implements CompressionInterface
{





    public function compress(string $string): string
    {
        return $string;
    }






    public function decompress(string $string): string
    {
        return $string;
    }









    public function readChunk(FileObject $wpstgFile, FileBeingExtracted $fileBeingExtracted, $callable = null): string
    {
        $length = $fileBeingExtracted->findReadTo();
        if ($length <= 0) {
            $fileBeingExtracted->setWrittenBytes($fileBeingExtracted->getTotalBytes());
            throw new EmptyChunkException();
        }

        return $wpstgFile->fread($length);
    }
}
