<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Interfaces\IndexLineInterface;
use WPStaging\Framework\Filesystem\PathIdentifier;
use WPStaging\Framework\Job\Exception\FileValidationException;
use WPStaging\Framework\Traits\EndOfLinePlaceholderTrait;
use WPStaging\Framework\Traits\FormatTrait;
use WPStaging\Framework\Utils\DataEncoder;

class FileHeader implements IndexLineInterface
{
    use EndOfLinePlaceholderTrait;
    use FormatTrait;

    /**
     * Packed Hex Code of `WPSTG`
     * This constant represents an 48bit unsigned integer packed as a hex string.
     * It is appended as it is to the start of the file header.
     *
     * Example:
     * $hex = '47f6600b0200';
     * to make it 8 bytes
     * $hex = $hex . '0000';
     * $bin = hex2bin($hex);
     * $int = unpack('P', $bin)[1];
     * echo $int; //8780838471 the original string
     * 87 -> W
     * 80 -> P
     * 83 -> S
     * 84 -> T
     * 71 -> G
     * @var string
     */
    const START_SIGNATURE = '47f6600b0200';

    /** @var int */
    const FILE_HEADER_FIXED_SIZE = 72;

    /** @var int */
    const INDEX_HEADER_FIXED_SIZE = 72;

    /**
     * @var string
     * The File Header format without the start signature to make it compatible with 32bit PHP
     */
    const FILE_HEADER_FORMAT = '44552424';

    /** @var string */
    const INDEX_HEADER_FORMAT = '644552424';

    /** @var string */
    const CRC32_CHECKSUM_ALGO = 'crc32b';

    /** @var string */
    private $startSignature;

    /** @var int */
    private $modifiedTime;

    /** @var string */
    private $crc32Checksum;

    /** @var int */
    private $crc32;

    /** @var int */
    private $compressedSize;

    /** @var int */
    private $uncompressedSize;

    /** @var int */
    private $attributes;

    /** @var int */
    private $extraFieldLength;

    /** @var int */
    private $fileNameLength;

    /** @var int */
    private $filePathLength;

    /** @var int */
    private $startOffset;

    /** @var string */
    private $filePath;

    /** @var string */
    private $fileName;

    /** @var string */
    private $extraField;

    /** @var DataEncoder */
    private $encoder;

    private $pathIdentifier;

    public function __construct(DataEncoder $encoder, PathIdentifier $pathIdentifier)
    {
        $this->encoder        = $encoder;
        $this->pathIdentifier = $pathIdentifier;
        $this->resetHeader();
    }

    /**
     * @param string $filePath
     * @param string $identifiablePath
     * @return void
     */
    public function readFile(string $filePath, string $identifiablePath)
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
        $this->setCrc32Checksum(hash_file(self::CRC32_CHECKSUM_ALGO, $filePath));
    }

    /**
     * @param string $index
     * @return void
     * @throws \UnexpectedValueException
     */
    public function decodeFileHeader(string $index)
    {
        $index         = rtrim($index);
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
        $this->setExtraField(substr($dynamicHeader, $this->filePathLength + $this->fileNameLength, $this->extraFieldLength));
    }

    /**
     * @param string $index
     * @return void
     */
    public function decodeIndexHeader(string $index)
    {
        $index         = rtrim($index);
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
        $this->setExtraField(substr($dynamicHeader, $this->filePathLength + $this->fileNameLength, $this->extraFieldLength));
    }

    /**
     * For compatibility with IndexLineInterface
     * @param string $indexLine
     * @return IndexLineInterface
     */
    public function readIndexLine(string $indexLine): IndexLineInterface
    {
        $this->decodeIndexHeader($indexLine);

        return $this;
    }

    /**
     * For compatibility with IndexLineInterface
     * @param string $indexLine
     * @return bool
     */
    public function isIndexLine(string $indexLine): bool
    {
        if (strlen($indexLine) <= self::INDEX_HEADER_FIXED_SIZE) {
            return false;
        }

        return true;
    }

    public function getFileHeader(): string
    {
        $fixedHeader = $this->encoder->intArrayToHex(self::FILE_HEADER_FORMAT, [
            $this->modifiedTime,
            $this->crc32,
            $this->compressedSize,
            $this->uncompressedSize,
            $this->attributes,
            $this->filePathLength,
            $this->fileNameLength,
            $this->extraFieldLength
        ]);
        $fileHeader = self::START_SIGNATURE . $fixedHeader . $this->filePath . $this->fileName . $this->extraField;
        $fileHeader = $this->replaceEOLsWithPlaceholders($fileHeader);

        return $fileHeader;
    }

    /**
     * Used for repairing the file content in compressed file i.e. removing header inside the content. Issue: https://github.com/wp-staging/wp-staging-pro/issues/4241
     * @return string
     */
    public function getUncompressedFileHeader(): string
    {
        // Force current attribute to be without compression, preserving them first to restore them later
        $oldAttributes = $this->attributes;
        $this->setIsCompressed(false);

        $fixedHeader = $this->encoder->intArrayToHex(self::FILE_HEADER_FORMAT, [
            $this->modifiedTime,
            $this->crc32,
            // Usually, it refers to the compressed size, but we need to set it to the uncompressed size because we initially add the file without compression and perform compression later.
            // This is done to remove this file header within file content through search replace
            $this->uncompressedSize,
            $this->uncompressedSize,
            $this->attributes,
            $this->filePathLength,
            $this->fileNameLength,
            $this->extraFieldLength
        ]);
        $fileHeader = self::START_SIGNATURE . $fixedHeader . $this->filePath . $this->fileName . $this->extraField;
        $fileHeader = $this->replaceEOLsWithPlaceholders($fileHeader);

        $this->setAttributes($oldAttributes);

        return $fileHeader;
    }

    public function getIndexHeader(): string
    {
        $fixedHeader = $this->encoder->intArrayToHex(self::INDEX_HEADER_FORMAT, [
            $this->startOffset,
            $this->modifiedTime,
            $this->crc32,
            $this->compressedSize,
            $this->uncompressedSize,
            $this->attributes,
            $this->filePathLength,
            $this->fileNameLength,
            $this->extraFieldLength
        ]);

        $fixedHeader = $fixedHeader . $this->filePath . $this->fileName . $this->extraField;
        $fixedHeader = $this->replaceEOLsWithPlaceholders($fixedHeader);

        return $fixedHeader;
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function setStartSignature(string $startSignature)
    {
        $this->startSignature = $startSignature;
    }

    public function getModifiedTime(): int
    {
        return $this->modifiedTime;
    }

    /**
     * @return void
     */
    public function setModifiedTime(int $modifiedTime)
    {
        $this->modifiedTime = $modifiedTime;
    }

    public function getCrc32(): int
    {
        return $this->crc32;
    }

    /**
     * @return void
     */
    public function setCrc32(int $crc32)
    {
        $this->crc32         = $crc32;
        $this->crc32Checksum = bin2hex(pack('N', $crc32));
    }

    public function getCrc32Checksum(): string
    {
        return $this->crc32Checksum;
    }

    /**
     * @return void
     */
    public function setCrc32Checksum(string $crc32Checksum)
    {
        $this->crc32Checksum = $crc32Checksum;
        $this->crc32         = unpack('N', pack('H*', $this->crc32Checksum))[1];
    }

    public function getCompressedSize(): int
    {
        return $this->compressedSize;
    }

    /**
     * @return void
     */
    public function setCompressedSize(int $compressedSize)
    {
        $this->compressedSize = $compressedSize;
    }

    public function getUncompressedSize(): int
    {
        return $this->uncompressedSize;
    }

    /**
     * @return void
     */
    public function setUncompressedSize(int $uncompressedSize)
    {
        $this->uncompressedSize = $uncompressedSize;
    }

    public function getAttributes(): int
    {
        return $this->attributes;
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function setStartOffset(int $startOffset)
    {
        $this->startOffset = $startOffset;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return void
     */
    public function setExtraField(string $extraField)
    {
        $this->extraField = $extraField;
        $this->extraFieldLength = strlen($extraField);
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

    /**
     * @param string $filePath
     * @param string $pathForErrorLogging
     * @return void
     * @throws FileValidationException
     */
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
}
