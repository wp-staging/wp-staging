<?php

namespace WPStaging\Backup;

use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\DataEncoder;
use WPStaging\Framework\Utils\Version;

/**
 * Backup Header class
 * It will generate the header of the backup file
 * and read the header of the backup file
 * and update the header of the backup file
 */
class BackupHeader
{
    /** @var string */
    const WPSTG_SQL_BACKUP_DUMP_HEADER = "-- WP Staging SQL Backup Dump\n";

    /**
     * In Length
     * @var int
     */
    const HEADER_SIZE = 512;

    /**
     * @var string
     */
    const HEADER_IN_USE_HEX_FORMAT = '48888';

    /**
     * File magic
     * should not exceed 8 characters
     * @var string
     */
    const MAGIC = "wpstg";

    /**
     * Magic size in length
     * @var int
     */
    const MAGIC_SIZE = 8;

    /**
     * Minimum Backup version that support this new header
     *
     * @var string
     */
    const MIN_BACKUP_VERSION = '2.0.0';

    /**
     * Backup version
     * Should not exceed 4-bytes unsigned limit 4294967295
     * In the format X.Y.Z
     * Where X is the major version and can be upto 429495 :)
     * Where Y is the minor version and can be upto 99
     * Where Z is the patch version and can be upto 99
     *
     * @var string
     */
    const BACKUP_VERSION = '2.0.0';

    /**
     * Original string should not exceed 64 characters for consistency
     * Generated from running bin2hex(str_pad("orignalString", 64 "\0", STR_PAD_RIGHT)) to 128 characters hex string
     * To retrieve original string run hex2bin(this.constant)
     * @var string
     */
    const COPYRIGHT_TEXT = '57502053746167696e672066696c6520666f726d61742062792052656e65204865726d656e617520262048617373616e20536861666971756520323032342f30';

    /**
     * Copyright text size
     * @var int
     */
    const COPYRIGHT_TEXT_SIZE = 128;

    /**
     * @var string
     */
    private $magic;

    /**
     * @var int
     */
    private $backupVersion;

    /**
     * @var int
     */
    private $filesIndexStartOffset = 0;

    /**
     * @var int
     */
    private $filesIndexEndOffset = 0;

    /**
     * @var int
     */
    private $metadataStartOffset = 0;

    /**
     * @var int
     */
    private $metadataEndOffset = 0;

    /**
     * @var string
     */
    private $copyrightText;

    /** @var DataEncoder */
    private $encoder;

    /** @var Version */
    private $versionUtil;

    /**
     * BackupHeader constructor.
     * @param DataEncoder $encoder
     * @param Version $versionUtil
     */
    public function __construct(DataEncoder $encoder, Version $versionUtil)
    {
        $this->encoder       = $encoder;
        $this->versionUtil   = $versionUtil;
        $this->backupVersion = $this->versionUtil->convertStringFormatToIntFormat(self::BACKUP_VERSION);
    }

    /**
     * Get backup version in XYYZZ integer format
     *
     * Where ZZ is the patch version from 00 to 99
     * Where YY is the minor version from 00 to 99
     * Where X is the major version from 0 to 429495
     *
     * @return int
     */
    public function getBackupVersion(): int
    {
        return $this->backupVersion;
    }

    /**
     * Get backup version in X.Y.Z string format
     *
     * Where Z is the patch version from 0 to 99
     * Where Y is the minor version from 0 to 99
     * Where X is the major version from 0 to 429495
     *
     * @return string
     */
    public function getFormattedBackupVersion(): string
    {
        return $this->versionUtil->convertIntFormatToStringFormat($this->backupVersion);
    }

    public function getMetadataStartOffset(): int
    {
        return $this->metadataStartOffset;
    }

    public function setMetadataStartOffset(int $metadataStartOffset): BackupHeader
    {
        $this->metadataStartOffset = $metadataStartOffset;
        return $this;
    }

    public function getMetadataEndOffset(): int
    {
        return $this->metadataEndOffset;
    }

    public function setMetadataEndOffset(int $metadataEndOffset): BackupHeader
    {
        $this->metadataEndOffset = $metadataEndOffset;
        return $this;
    }

    public function getFilesIndexStartOffset(): int
    {
        return $this->filesIndexStartOffset;
    }

    public function setFilesIndexStartOffset(int $filesIndexStartOffset): BackupHeader
    {
        $this->filesIndexStartOffset = $filesIndexStartOffset;
        return $this;
    }

    public function getFilesIndexEndOffset(): int
    {
        return $this->filesIndexEndOffset;
    }

    public function setFilesIndexEndOffset(int $filesIndexEndOffset): BackupHeader
    {
        $this->filesIndexEndOffset = $filesIndexEndOffset;
        return $this;
    }

    /**
     * @param string $backupFilePath
     * @return BackupHeader
     *
     * @throws \RuntimeException
     */
    public function readFromPath(string $backupFilePath): BackupHeader
    {
        if (!file_exists($backupFilePath)) {
            throw new \RuntimeException('Backup file not found');
        }

        $file = new FileObject($backupFilePath, FileObject::MODE_READ);
        return $this->readFromFileObject($file);
    }

    /**
     * @param FileObject $file
     * @return BackupHeader
     *
     * @throws \RuntimeException
     */
    public function readFromFileObject(FileObject $file): BackupHeader
    {
        if ($file->getSize() < self::HEADER_SIZE) {
            throw new \RuntimeException('Invalid v2 format backup file');
        }

        $file->seek(0);
        $rawHeader = $file->fread(self::HEADER_SIZE);

        return $this->setupBackupHeaderFromRaw($rawHeader);
    }

    /**
     * @param  string $rawHeader
     *
     * @throws InvalidArgumentException
     * @return BackupHeader
     */
    public function setupBackupHeaderFromRaw(string $rawHeader): BackupHeader
    {
        $this->magic         = rtrim(substr($rawHeader, 0, self::MAGIC_SIZE));
        $this->copyrightText = substr($rawHeader, self::HEADER_SIZE - self::COPYRIGHT_TEXT_SIZE, self::COPYRIGHT_TEXT_SIZE); // Don't trim this, because it's fixed length with null characters

        // Dynamic part of header currently in use
        $dynamicHeader = substr($rawHeader, self::MAGIC_SIZE, $this->getHeaderInUseSize());
        $headerIntData = $this->encoder->hexToIntArray(self::HEADER_IN_USE_HEX_FORMAT, $dynamicHeader);
        // Change the below code into [$a, $b, $c, $d, $e] = $array format when min php is 7.1
        $this->backupVersion         = $headerIntData[0];
        $this->filesIndexStartOffset = $headerIntData[1];
        $this->filesIndexEndOffset   = $headerIntData[2];
        $this->metadataStartOffset   = $headerIntData[3];
        $this->metadataEndOffset     = $headerIntData[4];

        return $this;
    }

    public function isValidBackupHeader(): bool
    {
        if ($this->magic !== self::MAGIC) {
            return false;
        }

        if ($this->copyrightText !== self::COPYRIGHT_TEXT) {
            return false;
        }

        return version_compare($this->getFormattedBackupVersion(), self::MIN_BACKUP_VERSION, '>=');
    }

    public function getHeader(): string
    {
        return sprintf(
            '%s%s%s%s',
            str_pad(self::MAGIC, self::MAGIC_SIZE, "\0", STR_PAD_RIGHT), // let write magic as it is without converting to hex
            $this->encoder->intArrayToHex(
                self::HEADER_IN_USE_HEX_FORMAT, // 36-bytes of hex data
                [
                    $this->backupVersion,
                    $this->filesIndexStartOffset,
                    $this->filesIndexEndOffset,
                    $this->metadataStartOffset,
                    $this->metadataEndOffset
                ]
            ),
            bin2hex(str_pad("", $this->getUnusedBytesSize(), "\0", STR_PAD_RIGHT)),
            self::COPYRIGHT_TEXT // 64-bytes of fixed hex data
        );
    }

    /**
     * @param string $backupFilePath
     * @return void
     */
    public function updateHeader(string $backupFilePath)
    {
        $header = $this->getHeader();
        $file   = new FileObject($backupFilePath, 'r+');
        $file->seek(0);
        $file->fwrite($header);
        $file = null;
    }

    /**
     * Validate Old Backup Header
     * @param string $content
     * @return bool
     */
    public function verifyV1FormatHeader(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        $wpstgBackupHeaderFileContent = self::WPSTG_SQL_BACKUP_DUMP_HEADER;
        $headerToVerifyLength         = strlen($wpstgBackupHeaderFileContent);
        if (substr($wpstgBackupHeaderFileContent, 0, $headerToVerifyLength) === substr($content, 0, $headerToVerifyLength)) {
            return true;
        }

        $wpstgBackupHeaderFile = WPSTG_RESOURCES_DIR . 'wpstgBackupHeader.txt';
        if (!file_exists($wpstgBackupHeaderFile)) {
            return true;
        }

        $wpstgBackupHeaderFileContent = file_get_contents($wpstgBackupHeaderFile);
        $headerToVerifyLength         = self::HEADER_SIZE;
        if (!empty($wpstgBackupHeaderFileContent) && substr($wpstgBackupHeaderFileContent, 0, $headerToVerifyLength) === substr($content, 0, $headerToVerifyLength)) {
            return true;
        }

        return false;
    }

    public function getV1FormatHeader(): string
    {
        $wpstgBackupHeaderFile = WPSTG_RESOURCES_DIR . 'wpstgBackupHeader.txt';
        // Should not happen
        if (!file_exists($wpstgBackupHeaderFile)) {
            return "";
        }

        return file_get_contents($wpstgBackupHeaderFile);
    }

    private function getHeaderInUseSize(): int
    {
        $size = 0;
        for ($i = 0; $i < strlen(self::HEADER_IN_USE_HEX_FORMAT); $i++) {
            $size += intval(substr(self::HEADER_IN_USE_HEX_FORMAT, $i, 1));
        }

        return $size * 2;
    }

    private function getUnusedBytesSize(): int
    {
        return (self::HEADER_SIZE - $this->getHeaderInUseSize() - self::MAGIC_SIZE - self::COPYRIGHT_TEXT_SIZE) / 2;
    }
}
