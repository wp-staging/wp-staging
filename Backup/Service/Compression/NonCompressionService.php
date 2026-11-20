<?php

namespace WPStaging\Backup\Service\Compression;

use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Backup\Exceptions\EmptyChunkException;
use WPStaging\Backup\Service\Encryption\BackupEncryptionService;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;

class NonCompressionService implements CompressionInterface
{





    const CHUNK_HEADER_SIZE = 4;

 
    protected $encryptionService;

 
    protected $decryptionKey = '';

 
    protected $hmacKey = '';





    public function setEncryptionService(BackupEncryptionService $service)
    {
        $this->encryptionService = $service;
    }





    public function setDecryptionKey($key)
    {
        $this->decryptionKey = $key;
    }





    public function setHmacKey($key)
    {
        $this->hmacKey = $key;
    }






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
        if ($this->canDecrypt($fileBeingExtracted)) {
            $sealedChunk = $this->readChunkFromHeaders($wpstgFile, $fileBeingExtracted, $callable);
            return $this->encryptionService->openChunk($sealedChunk, $this->decryptionKey, $this->hmacKey, $fileBeingExtracted);
        }

        $length = $fileBeingExtracted->findReadTo();
        if ($length <= 0) {
            $fileBeingExtracted->setWrittenBytes($fileBeingExtracted->getTotalBytes());
            throw new EmptyChunkException();
        }

        return $wpstgFile->fread($length);
    }

    protected function canDecrypt(FileBeingExtracted $fileBeingExtracted): bool
    {
        if (!$fileBeingExtracted->getIsEncrypted()) {
            return false;
        }

        if ($this->encryptionService === null || $this->decryptionKey === '') {
            throw new \RuntimeException('Cannot restore encrypted file without a decryption key.');
        }

        return true;
    }











    protected function readChunkFromHeaders(FileObject $wpstgFile, FileBeingExtracted $fileBeingExtracted, $callable = null)
    {
        $chunkInfo = unpack('N', $wpstgFile->fread(self::CHUNK_HEADER_SIZE));
        if (!is_array($chunkInfo) || count($chunkInfo) < 1) {
            throw new DiskNotWritableException('Failed to unpack chunk information. May happen with not enough disk space.');
        }

        $currentChunkNumber = $chunkInfo[1];

        if ($callable !== null) {
            $callable($currentChunkNumber);
        }

        $lengthInfo = unpack('N', $wpstgFile->fread(self::CHUNK_HEADER_SIZE));
        if (!is_array($lengthInfo) || count($lengthInfo) < 1) {
            throw new DiskNotWritableException();
        }

        $length = $lengthInfo[1];

        if ($length === 0) {
            $fileBeingExtracted->setWrittenBytes(self::CHUNK_HEADER_SIZE);
            throw new EmptyChunkException();
        }

        return $wpstgFile->fread($length);
    }
}
