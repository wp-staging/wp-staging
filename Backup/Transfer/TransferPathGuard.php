<?php

namespace WPStaging\Backup\Transfer;

use WPStaging\Backup\Exceptions\BackupRuntimeException;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Framework\Facades\Hooks;

use function WPStaging\functions\debug_log;




class TransferPathGuard
{
 
    const FILTER_ALLOWED_SOURCE_DIRECTORIES = 'wpstg.transfer_session.allowed_source_directories';

 
    const STREAM_WRAPPER_PATTERN = '#^[a-zA-Z][a-zA-Z0-9+.\-]*://#';

 
    private $backupsFinder;

 
    private $transferDirectory;

 
    private $tokenGenerator;

    public function __construct(BackupsFinder $backupsFinder, TransferDirectory $transferDirectory, TransferTokenGenerator $tokenGenerator)
    {
        $this->backupsFinder     = $backupsFinder;
        $this->transferDirectory = $transferDirectory;
        $this->tokenGenerator    = $tokenGenerator;
    }






    public function resolveSourcePath(string $path): string
    {
        $this->assertPathIsSyntacticallySafe($path);

        $realPath = realpath($path);
        if ($realPath === false) {
            throw TransferSessionException::sourceFileNotFound();
        }

        $realPath = wp_normalize_path($realPath);

        if (!is_file($realPath) || !is_readable($realPath)) {
            throw TransferSessionException::sourceFileNotReadable();
        }

        if (!$this->isInsideAnyAllowedSourceDirectory($realPath)) {
            throw TransferSessionException::invalidRequest();
        }

        return $realPath;
    }






    public function resolveTransferPath(string $path): string
    {
        $this->assertPathIsSyntacticallySafe($path);

        $normalizedPath = wp_normalize_path($path);

        if (!$this->isInsideTransferBase($normalizedPath)) {
            throw TransferSessionException::invalidRequest();
        }

        return $normalizedPath;
    }





    public function isDeletableTransferPath(string $path): bool
    {
        try {
            return trailingslashit($this->resolveTransferPath($path)) !== trailingslashit($this->transferDirectory->getBaseDirectory());
        } catch (TransferSessionException $e) {
            return false;
        }
    }

    public function isInsideTransferBase(string $normalizedPath): bool
    {
        return $this->isInside($this->transferDirectory->getBaseDirectory(), $normalizedPath);
    }

 
    public function getAllowedSourceDirectories(): array
    {
        $directories = Hooks::applyFilters(self::FILTER_ALLOWED_SOURCE_DIRECTORIES, [$this->backupsFinder->getBackupsDirectory()]);

        $resolved = [];
        foreach ((array)$directories as $directory) {
            $realPath = realpath((string)$directory);
            if ($realPath === false) {
                continue;
            }

            $resolved[] = trailingslashit(wp_normalize_path($realPath));
        }

        return array_values(array_unique($resolved));
    }







    public function runFilesystemCallWithRedactedWarnings(callable $filesystemCall, callable $onWarning)
    {
        set_error_handler(function ($severity, $message) use ($onWarning) {
            $onWarning($this->tokenGenerator->redactTokens($message));

            return true;
        });

        try {
            return $filesystemCall();
        } finally {
            restore_error_handler();
        }
    }







    public function deleteFileWithRedactedWarnings(string $path, callable $onWarning): bool
    {
        if (!$this->isDeletableTransferPath($path)) {
            debug_log('WP STAGING: Refused to delete a transfer artifact outside the transfer directory.');

            return false;
        }

        clearstatcache(true, $path);

        if (!file_exists($path) && !is_link($path)) {
            return true;
        }

        return (bool)$this->runFilesystemCallWithRedactedWarnings(function () use ($path) {
            return unlink($path);
        }, $onWarning);
    }





    private function assertPathIsSyntacticallySafe(string $path)
    {
        if (trim($path) === '' || preg_match(self::STREAM_WRAPPER_PATTERN, $path) === 1) {
            throw TransferSessionException::invalidRequest();
        }

        $normalizedPath = wp_normalize_path($path);
        if (strpos($normalizedPath, '../') !== false || substr($normalizedPath, -3) === '/..') {
            throw TransferSessionException::invalidRequest();
        }
    }

 
    private function isInsideAnyAllowedSourceDirectory(string $realPath): bool
    {
        foreach ($this->getAllowedSourceDirectories() as $allowedDirectory) {
            if ($this->isInside($allowedDirectory, $realPath)) {
                return true;
            }
        }

        return false;
    }

 
    private function isInside(string $baseDirectory, string $path): bool
    {
        $baseDirectory = trailingslashit(wp_normalize_path($baseDirectory));

        return $baseDirectory !== '/' && strpos(trailingslashit($path), $baseDirectory) === 0;
    }
}
