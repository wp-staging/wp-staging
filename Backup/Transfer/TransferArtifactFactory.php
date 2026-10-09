<?php

namespace WPStaging\Backup\Transfer;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Traits\SetTimeLimitTrait;

use function WPStaging\functions\debug_log;




class TransferArtifactFactory
{
    use SetTimeLimitTrait;

 
    const FILTER_PREFER_HARDLINK = 'wpstg.transfer_session.prefer_hardlink';

 
    const FILTER_COPY_CHUNK_SIZE = 'wpstg.transfer_session.copy_chunk_size';

 
    const DEFAULT_COPY_CHUNK_SIZE = 8 * MB_IN_BYTES;

 
    const FILTER_MAX_COPY_SIZE = 'wpstg.transfer_session.max_copy_size';

 
    const DEFAULT_MAX_COPY_SIZE = 2 * GB_IN_BYTES;

 
    const REQUIRED_FREE_SPACE_FACTOR = 1.05;

 
    const TMP_SUFFIX = '.tmp';

 
    private $pathGuard;

 
    private $lastError = '';

    public function __construct(TransferPathGuard $pathGuard)
    {
        $this->pathGuard = $pathGuard;
    }







    public function create(string $sourcePath, string $targetPath): string
    {
        $this->lastError = '';
        $targetPath      = $this->pathGuard->resolveTransferPath($targetPath);
        $sourceSize      = filesize($sourcePath);

        if ($sourceSize === false) {
            throw TransferSessionException::sourceFileNotReadable();
        }

        $sourceSize = (int)$sourceSize;

        if ($this->prefersHardlink()) {
            if ($this->tryHardlink($sourcePath, $targetPath, $sourceSize)) {
                return TransferArtifactMode::HARDLINK;
            }

            debug_log('WP STAGING: This server could not create a hardlink for the transfer artifact, falling back to a temporary copy. Reason: ' . $this->lastError);
        }

        $this->assertCopyIsWithinReach($sourceSize);
        $this->assertEnoughDiskSpace($targetPath, $sourceSize);

        if ($this->tryCopy($sourcePath, $targetPath, $sourceSize)) {
            return TransferArtifactMode::COPY;
        }

        debug_log('WP STAGING: Could not create a transfer artifact. Last error: ' . $this->lastError);

        throw TransferSessionException::artifactCreationFailed();
    }

    private function prefersHardlink(): bool
    {
        return (bool)Hooks::applyFilters(self::FILTER_PREFER_HARDLINK, true) && function_exists('link');
    }

    private function tryHardlink(string $sourcePath, string $targetPath, int $expectedSize): bool
    {
        $created = $this->executeWithoutWarnings(function () use ($sourcePath, $targetPath) {
            return link($sourcePath, $targetPath);
        });

        return $created === true && $this->verifyArtifact($targetPath, $expectedSize);
    }

 
    private function tryCopy(string $sourcePath, string $targetPath, int $expectedSize): bool
    {
        $this->setTimeLimit(0);

        $tmpPath = $targetPath . self::TMP_SUFFIX;

        $sourceHandle = $this->executeWithoutWarnings(function () use ($sourcePath) {
            return fopen($sourcePath, 'rb');
        });

        if (!is_resource($sourceHandle)) {
            return false;
        }

        $targetHandle = $this->executeWithoutWarnings(function () use ($tmpPath) {
            return fopen($tmpPath, 'wb');
        });

        if (!is_resource($targetHandle)) {
            fclose($sourceHandle);
            return false;
        }

        $chunkSize = $this->getCopyChunkSize();
        $success   = true;

        while (!feof($sourceHandle)) {
            $chunk = fread($sourceHandle, $chunkSize);
            if ($chunk === false) {
                $success = false;
                break;
            }

            if ($chunk === '') {
                continue;
            }

            if (fwrite($targetHandle, $chunk) === false) {
                $success = false;
                break;
            }
        }

        fclose($sourceHandle);
        fclose($targetHandle);

        clearstatcache(true, $tmpPath);

        $copiedSize = $this->executeWithoutWarnings(function () use ($tmpPath) {
            return filesize($tmpPath);
        });

        if (!$success || $copiedSize !== $expectedSize) {
            $this->lastError = 'The temporary copy did not match the size of the backup file.';
            $this->pathGuard->deleteFileWithRedactedWarnings($tmpPath, $this->createLastErrorRecorder());

            return false;
        }

        $renamed = $this->executeWithoutWarnings(function () use ($tmpPath, $targetPath) {
            return rename($tmpPath, $targetPath);
        });

        if (!$renamed) {
            $this->lastError = 'The completed temporary copy could not be renamed into place.';
            $this->pathGuard->deleteFileWithRedactedWarnings($tmpPath, $this->createLastErrorRecorder());

            return false;
        }

        return $this->verifyArtifact($targetPath, $expectedSize);
    }





    private function assertCopyIsWithinReach(int $sourceSize)
    {
        $maximumSize = (int)Hooks::applyFilters(self::FILTER_MAX_COPY_SIZE, self::DEFAULT_MAX_COPY_SIZE);

        if ($maximumSize <= 0 || $sourceSize <= $maximumSize) {
            return;
        }

        debug_log(sprintf('WP STAGING: Refused to copy a %s backup into a transfer artifact, the limit is %s.', size_format($sourceSize), size_format($maximumSize)));

        throw TransferSessionException::backupTooLargeToCopy(size_format($sourceSize), size_format($maximumSize));
    }







    private function assertEnoughDiskSpace(string $targetPath, int $requiredBytes)
    {
        $freeSpace = $this->executeWithoutWarnings(function () use ($targetPath) {
            return disk_free_space(dirname($targetPath));
        });

        if (!is_float($freeSpace) && !is_int($freeSpace)) {
            return;
        }

        if ($freeSpace >= $requiredBytes * self::REQUIRED_FREE_SPACE_FACTOR) {
            return;
        }

        debug_log(sprintf('WP STAGING: Refused to create a transfer copy. Required %s, free %s.', size_format($requiredBytes), size_format((int)$freeSpace)));

        throw TransferSessionException::notEnoughDiskSpace();
    }

    private function verifyArtifact(string $targetPath, int $expectedSize): bool
    {
        clearstatcache(true, $targetPath);

        if (!file_exists($targetPath)) {
            $this->lastError = 'The transfer artifact was reported as created but does not exist.';
            return false;
        }

        if (filesize($targetPath) !== $expectedSize) {
            $this->lastError = 'The transfer artifact does not have the same size as the backup file.';
            $this->pathGuard->deleteFileWithRedactedWarnings($targetPath, $this->createLastErrorRecorder());

            return false;
        }

        return true;
    }

    private function getCopyChunkSize(): int
    {
        $chunkSize = (int)Hooks::applyFilters(self::FILTER_COPY_CHUNK_SIZE, self::DEFAULT_COPY_CHUNK_SIZE);

        return $chunkSize > 0 ? $chunkSize : self::DEFAULT_COPY_CHUNK_SIZE;
    }






    private function executeWithoutWarnings(callable $filesystemCall)
    {
        return $this->pathGuard->runFilesystemCallWithRedactedWarnings($filesystemCall, $this->createLastErrorRecorder());
    }

    private function createLastErrorRecorder(): callable
    {
        return function (string $warning) {
            $this->lastError = $warning;
        };
    }
}
