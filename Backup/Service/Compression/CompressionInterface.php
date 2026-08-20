<?php

namespace WPStaging\Backup\Service\Compression;

use RuntimeException;
use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Framework\Filesystem\FileObject;

interface CompressionInterface
{





    public function compress(string $string): string;






    public function decompress(string $string): string;







    public function readChunk(FileObject $wpstgFile, FileBeingExtracted $fileBeingExtracted, $callable = null): string;
}
