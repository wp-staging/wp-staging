<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Traits\EncodingErrorHandler;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\DataEncoder;
use WPStaging\Framework\Utils\Version;







class BackupHeader
{
    use EncodingErrorHandler;

 
    const WPSTG_SQL_BACKUP_DUMP_HEADER = "-- WP Staging SQL Backup Dump\n";





    const HEADER_SIZE = 512;




    const HEADER_IN_USE_HEX_FORMAT = '48888';






    const MAGIC = "wpstg";





    const MAGIC_SIZE = 8;






    const MIN_BACKUP_VERSION = '2.0.0';











    const BACKUP_VERSION = '2.1.0';







    const COPYRIGHT_TEXT = '57502053746167696e672066696c6520666f726d61742062792052656e65204865726d656e617520262048617373616e20536861666971756520323032342f30';

    /**
     * Copyright text size
     * @var int
     */
    const COPYRIGHT_TEXT_SIZE = 128;




    private $magic;




    private $backupVersion;




    private $filesIndexStartOffset = 0;




    private $filesIndexEndOffset = 0;




    private $metadataStartOffset = 0;




    private $metadataEndOffset = 0;




    private $copyrightText;

 
    private $encoder;

 
    private $versionUtil;






    public function __construct(DataEncoder $encoder, Version $versionUtil)
    {
        $this->encoder       = $encoder;
        $this->versionUtil   = $versionUtil;
        $this->backupVersion = $this->versionUtil->convertStringFormatToIntFormat(self::BACKUP_VERSION);
    }







    private function logBackupHeaderEncodingError(string $errorMessage)
    {
        $context = [
            'backupVersion'         => $this->backupVersion,
            'filesIndexStartOffset' => $this->filesIndexStartOffset,
            'filesIndexEndOffset'   => $this->filesIndexEndOffset,
            'metadataStartOffset'   => $this->metadataStartOffset,
            'metadataEndOffset'     => $this->metadataEndOffset,
        ];

        $this->logEncodingErrorWithContext(
            $errorMessage,
            $context,
            'DataEncoder error in BackupHeader::getHeader(): %s. Using fallback values to continue backup.'
        );
    }






    private function applyBackupHeaderFallbackValues()
    {
        if ($this->backupVersion === null) {
            $this->backupVersion = $this->versionUtil->convertStringFormatToIntFormat(self::BACKUP_VERSION);
        }

        if ($this->filesIndexStartOffset === null) {
            $this->filesIndexStartOffset = 0;
        }

        if ($this->filesIndexEndOffset === null) {
            $this->filesIndexEndOffset = 0;
        }

        if ($this->metadataStartOffset === null) {
            $this->metadataStartOffset = 0;
        }

        if ($this->metadataEndOffset === null) {
            $this->metadataEndOffset = 0;
        }
    }










    public function getBackupVersion(): int
    {
        return $this->backupVersion;
    }










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







    public function readFromPath(string $backupFilePath): BackupHeader
    {
        if (!file_exists($backupFilePath)) {
            throw new \RuntimeException('Backup file not found');
        }

        $file = new FileObject($backupFilePath, FileObject::MODE_READ);
        return $this->readFromFileObject($file);
    }







    public function readFromFileObject(FileObject $file): BackupHeader
    {
        if ($file->getSize() < self::HEADER_SIZE) {
            throw new \RuntimeException('Invalid v2 format backup file');
        }

        $file->seek(0);
        $rawHeader = $file->fread(self::HEADER_SIZE);

        return $this->setupBackupHeaderFromRaw($rawHeader);
    }







    public function setupBackupHeaderFromRaw(string $rawHeader): BackupHeader
    {
        $this->magic         = rtrim(substr($rawHeader, 0, self::MAGIC_SIZE));
        $this->copyrightText = substr($rawHeader, self::HEADER_SIZE - self::COPYRIGHT_TEXT_SIZE, self::COPYRIGHT_TEXT_SIZE); 

 
        $dynamicHeader = substr($rawHeader, self::MAGIC_SIZE, $this->getHeaderInUseSize());
        $headerIntData = $this->encoder->hexToIntArray(self::HEADER_IN_USE_HEX_FORMAT, $dynamicHeader);
 
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
        try {
            $encodedData = $this->encoder->intArrayToHex(
                self::HEADER_IN_USE_HEX_FORMAT, 
                [
                    $this->backupVersion,
                    $this->filesIndexStartOffset,
                    $this->filesIndexEndOffset,
                    $this->metadataStartOffset,
                    $this->metadataEndOffset,
                ]
            );
        } catch (\InvalidArgumentException $e) {
 
            $this->logBackupHeaderEncodingError($e->getMessage());

 
            $this->applyBackupHeaderFallbackValues();

 
            $encodedData = $this->encoder->intArrayToHex(
                self::HEADER_IN_USE_HEX_FORMAT,
                [
                    $this->backupVersion,
                    $this->filesIndexStartOffset,
                    $this->filesIndexEndOffset,
                    $this->metadataStartOffset,
                    $this->metadataEndOffset,
                ]
            );
        }

        return sprintf(
            '%s%s%s%s',
            str_pad(self::MAGIC, self::MAGIC_SIZE, "\0", STR_PAD_RIGHT), 
            $encodedData,
            bin2hex(str_pad("", $this->getUnusedBytesSize(), "\0", STR_PAD_RIGHT)),
            self::COPYRIGHT_TEXT 
        );
    }





    public function updateHeader(string $backupFilePath)
    {
        $header = $this->getHeader();
        $file   = new FileObject($backupFilePath, 'r+');
        $file->seek(0);
        $file->fwrite($header);
        $file = null;
    }






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
