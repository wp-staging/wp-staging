<?php

namespace WPStaging\Backup;

use WPStaging\Backup\FileHeader\ExtraFieldCodec;
use WPStaging\Backup\FileHeader\ExtraFieldType;
use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Backup\Traits\EncodingErrorHandler;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Job\Exception\FileValidationException;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Traits\FormatTrait;
use WPStaging\Framework\Utils\DataEncoder;

class FileHeader implements IndexLineInterface
{
    use EndOfLinePlaceholderTrait;
    use FormatTrait;
    use EncodingErrorHandler;




















    const START_SIGNATURE = '47f6600b0200';

 
    const FILE_HEADER_FIXED_SIZE = 72;

 
    const INDEX_HEADER_FIXED_SIZE = 72;











    const FILE_HEADER_FORMAT = '44552424';

 
    const INDEX_HEADER_FORMAT = '644552424';

 
    const CRC32_CHECKSUM_ALGO = 'crc32b';

 
    private $startSignature;

 
    private $modifiedTime;

 
    private $crc32Checksum;

 
    private $crc32;

 
    private $compressedSize;

 
    private $uncompressedSize;

 
    private $attributes;

 
    private $extraFieldLength;

 
    private $fileNameLength;

 
    private $filePathLength;

 
    private $startOffset;

 
    private $filePath;

 
    private $fileName;

 
    private $extraField;

 
    private $encoder;

    private $pathIdentifier;

    public function __construct(DataEncoder $encoder, PathIdentifier $pathIdentifier)
    {
        $this->encoder        = $encoder;
        $this->pathIdentifier = $pathIdentifier;
        $this->resetHeader();
    }








    private function logEncodingError(string $method, string $errorMessage)
    {
        $fileName = $this->getIdentifiablePath();
        $context = [
            'file'             => $fileName ?: 'unknown',
            'method'           => $method,
            'modifiedTime'     => $this->modifiedTime,
            'crc32'            => $this->crc32,
            'compressedSize'   => $this->compressedSize,
            'uncompressedSize' => $this->uncompressedSize,
            'attributes'       => $this->attributes,
        ];

        $logMessageTemplate = 'DataEncoder error in %s for file "' . ($fileName ?: 'unknown') .
                              '": %s. Using fallback values to continue backup.';

        $this->logEncodingErrorWithContext($errorMessage, $context, $logMessageTemplate);
    }






    private function applyFallbackValues()
    {
 
        if ($this->modifiedTime === null) {
            $this->modifiedTime = time(); 
        }

        if ($this->crc32 === null) {
            $this->crc32 = 0; 
        }

        if ($this->compressedSize === null) {
            $this->compressedSize = 0; 
        }

        if ($this->uncompressedSize === null) {
            $this->uncompressedSize = 0; 
        }

        if ($this->attributes === null) {
            $this->attributes = 0; 
        }

        if ($this->startOffset === null) {
            $this->startOffset = 0; 
        }

 
        if ($this->filePathLength === null) {
            $this->filePathLength = strlen($this->filePath ?: '');
        }

        if ($this->fileNameLength === null) {
            $this->fileNameLength = strlen($this->fileName ?: '');
        }

        if ($this->extraFieldLength === null) {
            $this->extraFieldLength = strlen($this->extraField ?: '');
        }
    }









    private function encodeIntArrayToHex(string $format, array $intArray, string $method): string
    {
        try {
            return $this->encoder->intArrayToHex($format, $intArray);
        } catch (\InvalidArgumentException $e) {
 
            $this->logEncodingError($method, $e->getMessage());

 
            $this->applyFallbackValues();

 
            if ($method === 'getFileHeader' || $method === 'getUncompressedFileHeader') {
                $fallbackArray = [
                    $this->modifiedTime,
                    $this->crc32,
                    $this->compressedSize,
                    $this->uncompressedSize,
                    $this->attributes,
                    $this->filePathLength,
                    $this->fileNameLength,
                    $this->extraFieldLength,
                ];
            } elseif ($method === 'getIndexHeader') {
                $fallbackArray = [
                    $this->startOffset,
                    $this->modifiedTime,
                    $this->crc32,
                    $this->compressedSize,
                    $this->uncompressedSize,
                    $this->attributes,
                    $this->filePathLength,
                    $this->fileNameLength,
                    $this->extraFieldLength,
                ];
            } else {
 
                $fallbackArray = $intArray;
                foreach ($fallbackArray as $index => $value) {
                    if ($value === null) {
                        $fallbackArray[$index] = 0;
                    }
                }
            }

 
            return $this->encoder->intArrayToHex($format, $fallbackArray);
        }
    }










    public function readFile(string $filePath, string $identifiablePath, bool $skipChecksum = false)
    {
        $fileInfo = new \SplFileInfo($filePath);
        $this->setFileName($fileInfo->getFilename());

        $convertedPath     = $this->pathIdentifier->transformIdentifiableToPath($identifiablePath);
        $convertedPathName = basename($convertedPath);

        $path = substr($identifiablePath, 0, -strlen($convertedPathName));
        $this->setFilePath($path);
        $this->setExtraField("");
        $this->setUncompressedSize($fileInfo->getSize());
        $this->setCompressedSize($fileInfo->getSize());
        $this->setModifiedTime($fileInfo->getMTime());
        $this->setAttributes(0);

        if ($skipChecksum) {
 
 
            return;
        }

        $this->setCrc32Checksum(hash_file(self::CRC32_CHECKSUM_ALGO, $filePath));
    }






    public function decodeFileHeader(string $index)
    {
        $index         = $this->trimTrailingLineBreak($index);
        $fixedHeader   = substr($index, 0, self::FILE_HEADER_FIXED_SIZE);
        $dynamicHeader = substr($index, self::FILE_HEADER_FIXED_SIZE);
        if (strpos($fixedHeader, self::START_SIGNATURE) !== 0) {
            throw new \UnexpectedValueException('Invalid file header');
        }

        $header = $this->encoder->hexToIntArray(self::FILE_HEADER_FORMAT, substr($fixedHeader, 12, self::FILE_HEADER_FIXED_SIZE - 12));
        $this->setModifiedTime($header[0]);
        $this->setCrc32($header[1]);
        $this->setCompressedSize($header[2]);
        $this->setUncompressedSize($header[3]);
        $this->setAttributes($header[4]);
        $this->filePathLength = $header[5];
        $this->fileNameLength = $header[6];
        $this->extraFieldLength = $header[7];
        $this->setFilePath(substr($dynamicHeader, 0, $this->filePathLength));
        $this->setFileName(substr($dynamicHeader, $this->filePathLength, $this->fileNameLength));
        $this->setExtraField($this->replacePlaceholdersWithEOLs(substr($dynamicHeader, $this->filePathLength + $this->fileNameLength, $this->extraFieldLength)));
    }





    public function decodeIndexHeader(string $index)
    {
        $index         = $this->trimTrailingLineBreak($index);
        $fixedHeader   = substr($index, 0, self::INDEX_HEADER_FIXED_SIZE);
        $dynamicHeader = substr($index, self::INDEX_HEADER_FIXED_SIZE);
        $header        = $this->encoder->hexToIntArray(self::INDEX_HEADER_FORMAT, $fixedHeader);

        $this->setStartOffset($header[0]);
        $this->setModifiedTime($header[1]);
        $this->setCrc32($header[2]);
        $this->setCompressedSize($header[3]);
        $this->setUncompressedSize($header[4]);
        $this->setAttributes($header[5]);
        $this->filePathLength = $header[6];
        $this->fileNameLength = $header[7];
        $this->extraFieldLength = $header[8];
        $this->setFilePath(substr($dynamicHeader, 0, $this->filePathLength));
        $this->setFileName(substr($dynamicHeader, $this->filePathLength, $this->fileNameLength));
        $this->setExtraField($this->replacePlaceholdersWithEOLs(substr($dynamicHeader, $this->filePathLength + $this->fileNameLength, $this->extraFieldLength)));
    }







    private function trimTrailingLineBreak(string $line): string
    {
        if (substr($line, -2) === "\r\n") {
            return substr($line, 0, -2);
        }

        if (substr($line, -1) === "\n") {
            return substr($line, 0, -1);
        }

        return $line;
    }






    public function readIndexLine(string $indexLine): IndexLineInterface
    {
        $this->decodeIndexHeader($indexLine);

        return $this;
    }






    public function isIndexLine(string $indexLine): bool
    {
        if (strlen($indexLine) <= self::INDEX_HEADER_FIXED_SIZE) {
            return false;
        }

        return true;
    }

    public function getFileHeader(): string
    {
        $fixedHeader = $this->encodeIntArrayToHex(self::FILE_HEADER_FORMAT, [
            $this->modifiedTime,
            $this->crc32,
            $this->compressedSize,
            $this->uncompressedSize,
            $this->attributes,
            $this->filePathLength,
            $this->fileNameLength,
            $this->extraFieldLength,
        ], 'getFileHeader');

        $fileHeader = self::START_SIGNATURE . $fixedHeader . $this->filePath . $this->fileName . $this->extraField;
        $fileHeader = $this->replaceEOLsWithPlaceholders($fileHeader);

        return $fileHeader;
    }





    public function getUncompressedFileHeader(): string
    {
 
        $oldAttributes = $this->attributes;
        $this->setIsCompressed(false);

        $fixedHeader = $this->encodeIntArrayToHex(self::FILE_HEADER_FORMAT, [
            $this->modifiedTime,
            $this->crc32,
 
 
            $this->uncompressedSize,
            $this->uncompressedSize,
            $this->attributes,
            $this->filePathLength,
            $this->fileNameLength,
            $this->extraFieldLength,
        ], 'getUncompressedFileHeader');

        $fileHeader = self::START_SIGNATURE . $fixedHeader . $this->filePath . $this->fileName . $this->extraField;
        $fileHeader = $this->replaceEOLsWithPlaceholders($fileHeader);

        $this->setAttributes($oldAttributes);

        return $fileHeader;
    }

    public function getIndexHeader(): string
    {
        $fixedHeader = $this->encodeIntArrayToHex(self::INDEX_HEADER_FORMAT, [
            $this->startOffset,
            $this->modifiedTime,
            $this->crc32,
            $this->compressedSize,
            $this->uncompressedSize,
            $this->attributes,
            $this->filePathLength,
            $this->fileNameLength,
            $this->extraFieldLength,
        ], 'getIndexHeader');

        $fixedHeader = $fixedHeader . $this->filePath . $this->fileName . $this->extraField;
        $fixedHeader = $this->replaceEOLsWithPlaceholders($fixedHeader);

        return $fixedHeader;
    }




    public function resetHeader()
    {
        $this->startSignature   = '';
        $this->modifiedTime     = 0;
        $this->crc32            = 0;
        $this->crc32Checksum    = '';
        $this->compressedSize   = 0;
        $this->uncompressedSize = 0;
        $this->setAttributes(0);
        $this->extraFieldLength = 0;
        $this->fileNameLength   = 0;
        $this->filePathLength   = 0;
        $this->startOffset      = 0;
        $this->filePath         = '';
        $this->fileName         = '';
        $this->extraField       = '';
    }

    public function getStartSignature(): string
    {
        return $this->startSignature;
    }




    public function setStartSignature(string $startSignature)
    {
        $this->startSignature = $startSignature;
    }

    public function getModifiedTime(): int
    {
        return $this->modifiedTime;
    }




    public function setModifiedTime(int $modifiedTime)
    {
        $this->modifiedTime = $modifiedTime;
    }

    public function getCrc32(): int
    {
        return $this->crc32;
    }




    public function setCrc32(int $crc32)
    {
        $this->crc32         = $crc32;
        $this->crc32Checksum = bin2hex(pack('N', $crc32));
    }

    public function getCrc32Checksum(): string
    {
        return $this->crc32Checksum;
    }




    public function setCrc32Checksum(string $crc32Checksum)
    {
        $this->crc32Checksum = $crc32Checksum;
        $this->crc32         = unpack('N', pack('H*', $this->crc32Checksum))[1];
    }

    public function getCompressedSize(): int
    {
        return $this->compressedSize;
    }




    public function setCompressedSize(int $compressedSize)
    {
        $this->compressedSize = $compressedSize;
    }

    public function getUncompressedSize(): int
    {
        return $this->uncompressedSize;
    }




    public function setUncompressedSize(int $uncompressedSize)
    {
        $this->uncompressedSize = $uncompressedSize;
    }

    public function getAttributes(): int
    {
        return $this->attributes;
    }




    public function setAttributes(int $attributes)
    {
        $this->attributes = $attributes;
    }

    public function getIsCompressed(): bool
    {
        if ($this->attributes & FileHeaderAttribute::COMPRESSED) {
            return true;
        }

        return false;
    }




    public function setIsCompressed(bool $isCompressed)
    {
        $isCompressed ?
            $this->attributes |= FileHeaderAttribute::COMPRESSED :
            $this->attributes &= ~FileHeaderAttribute::COMPRESSED;
    }

    public function getIsPreviousPartRequired(): bool
    {
        if ($this->attributes & FileHeaderAttribute::REQUIRE_PREVIOUS_PART) {
            return true;
        }

        return false;
    }




    public function setIsPreviousPartRequired(bool $isPreviousPartRequired)
    {
        $isPreviousPartRequired ?
            $this->attributes |= FileHeaderAttribute::REQUIRE_PREVIOUS_PART :
            $this->attributes &= ~FileHeaderAttribute::REQUIRE_PREVIOUS_PART;
    }

    public function getIsNextPartRequired(): bool
    {
        if ($this->attributes & FileHeaderAttribute::REQUIRE_NEXT_PART) {
            return true;
        }

        return false;
    }




    public function setIsNextPartRequired(bool $isNextPartRequired)
    {
        $isNextPartRequired ?
            $this->attributes |= FileHeaderAttribute::REQUIRE_NEXT_PART :
            $this->attributes &= ~FileHeaderAttribute::REQUIRE_NEXT_PART;
    }

    public function getStartOffset(): int
    {
        return $this->startOffset;
    }









    public function setMultipartTailMetadata(int $wholeFileSize, string $wholeFileCrc)
    {
        $this->setExtraFieldEntry(ExtraFieldType::TAIL, sprintf('%d:%s', $wholeFileSize, $wholeFileCrc));
    }








    public function getMultipartTailMetadata()
    {
        $payload = $this->getExtraFieldEntry(ExtraFieldType::TAIL);
        if ($payload === null) {
            return null;
        }

        $parts   = explode(':', $payload, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [
            'wholeFileSize' => (int) $parts[0],
            'wholeFileCRC'  => $parts[1],
        ];
    }




    public function setStartOffset(int $startOffset)
    {
        $this->startOffset = $startOffset;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }




    public function setFilePath(string $filePath)
    {
        $this->filePath       = $filePath;
        $filePathRenamed      = $this->replaceEOLsWithPlaceholders($filePath);
        $this->filePathLength = strlen($filePathRenamed);
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }




    public function setFileName(string $fileName)
    {
        $this->fileName       = $fileName;
        $renamedFile          = $this->replaceEOLsWithPlaceholders($fileName);
        $this->fileNameLength = strlen($renamedFile);
    }

    public function getExtraField(): string
    {
        return $this->extraField;
    }




    public function setExtraField(string $extraField)
    {
        $this->extraField       = $extraField;
 
 
 
 
 
        $this->extraFieldLength = strlen($this->replaceEOLsWithPlaceholders($extraField));
    }















    public function setExtraFieldEntry(int $type, string $value)
    {
        if ($type === ExtraFieldType::LEGACY_RAW) {
            throw new \UnexpectedValueException(sprintf('FileHeader::setExtraFieldEntry: type 0x%02X (LEGACY_RAW) is a parser-only sentinel and cannot be written.', ExtraFieldType::LEGACY_RAW));
        }

        $codec   = new ExtraFieldCodec();
        $entries = $codec->decode($this->extraField);
        if (isset($entries[ExtraFieldType::LEGACY_RAW])) {
            throw new \UnexpectedValueException('FileHeader::setExtraFieldEntry: refusing to add a TLV entry to a non-TLV extraField; opaque legacy bytes would be destroyed. Reset the field with setExtraField("") first if this is intentional.');
        }

        $entries[$type] = $value;
        $this->setExtraField($codec->encode($entries));
    }







    public function getExtraFieldEntry(int $type)
    {
        $entries = (new ExtraFieldCodec())->decode($this->extraField);
        return isset($entries[$type]) ? $entries[$type] : null;
    }

    public function getIdentifiablePath(): string
    {
        return $this->filePath . $this->fileName;
    }

    public function getDynamicHeaderLength(): int
    {
        return $this->filePathLength + $this->fileNameLength + $this->extraFieldLength;
    }

    public function getContentStartOffset(): int
    {
        return $this->startOffset + self::FILE_HEADER_FIXED_SIZE + $this->getDynamicHeaderLength() + 1;
    }







    public function validateFile(string $filePath, string $pathForErrorLogging = '')
    {
        if (empty($pathForErrorLogging)) {
            $pathForErrorLogging = $filePath;
        }

        if (!file_exists($filePath)) {
            throw new FileValidationException(sprintf('File doesn\'t exist: %s.', $pathForErrorLogging));
        }

        $fileSize = filesize($filePath);
        if ($this->getUncompressedSize() !== $fileSize) {
            throw new FileValidationException(sprintf('Filesize validation failed for file %s. Expected: %s. Actual: %s', $pathForErrorLogging, $this->formatSize($this->getUncompressedSize(), 2), $this->formatSize($fileSize, 2)));
        }

        $crc32Checksum = hash_file(self::CRC32_CHECKSUM_ALGO, $filePath);
        if ($this->crc32Checksum !== $crc32Checksum) {
            throw new FileValidationException(sprintf('CRC32 Checksum validation failed for file %s. Expected: %s. Actual: %s', $pathForErrorLogging, $this->getCrc32Checksum(), $crc32Checksum));
        }
    }

    public function toArray(): array
    {
        return [
            'startOffset'      => $this->getStartOffset(),
            'modifiedTime'     => $this->getModifiedTime(),
            'crc32'            => $this->getCrc32(),
            'crc32Checksum'    => $this->getCrc32Checksum(),
            'compressedSize'   => $this->getCompressedSize(),
            'uncompressedSize' => $this->getUncompressedSize(),
            'filePath'         => $this->getFilePath(),
            'fileName'         => $this->getFileName(),
            'extraField'       => $this->getExtraField(),
            'isCompressed'     => $this->getIsCompressed(),
        ];
    }
}
