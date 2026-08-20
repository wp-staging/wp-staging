<?php

namespace WPStaging\Backup\Interfaces;

use WPStaging\Framework\Job\Exception\FileValidationException;




interface IndexLineInterface
{
    public function getContentStartOffset(): int;

    public function getStartOffset(): int;

    public function getIdentifiablePath(): string;

    public function getUncompressedSize(): int;

    public function getCompressedSize(): int;

    public function getIsCompressed(): bool;

    public function isIndexLine(string $indexLine): bool;

    public function readIndexLine(string $indexLine): IndexLineInterface;








    public function validateFile(string $filePath, string $pathForErrorLogging = '');
}
