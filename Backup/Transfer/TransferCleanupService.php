<?php

namespace WPStaging\Backup\Transfer;

use DirectoryIterator;
use Generator;
use Throwable;
use UnexpectedValueException;
use WPStaging\Framework\Facades\Hooks;

use function WPStaging\functions\debug_log;




class TransferCleanupService
{
 
    const FILTER_CLEANUP_INTERVAL = 'wpstg.transfer_session.cleanup_interval';

 
    const TRANSIENT_LAST_CLEANUP = 'wpstg.transfer_session.last_cleanup';

 
    const CRON_HOOK = 'wpstg_transfer_session_cleanup';

 
    const DEFAULT_CLEANUP_INTERVAL = 15 * MINUTE_IN_SECONDS;

 
    const CLEANUP_BATCH_SIZE = 50;

 
    const MAX_CLEANUP_ATTEMPTS = 5;

 
    const ROW_RETENTION = 7 * DAY_IN_SECONDS;

 
    const ORPHAN_SAFETY_BUFFER = HOUR_IN_SECONDS;

 
    private $repository;

 
    private $transferDirectory;

 
    private $pathGuard;

 
    private $tokenGenerator;

    public function __construct(TransferSessionRepository $repository, TransferDirectory $transferDirectory, TransferPathGuard $pathGuard, TransferTokenGenerator $tokenGenerator)
    {
        $this->repository        = $repository;
        $this->transferDirectory = $transferDirectory;
        $this->pathGuard         = $pathGuard;
        $this->tokenGenerator    = $tokenGenerator;
    }






    public function maybeCleanup()
    {
        if (get_transient(self::TRANSIENT_LAST_CLEANUP)) {
            return;
        }

        $interval = (int)Hooks::applyFilters(self::FILTER_CLEANUP_INTERVAL, self::DEFAULT_CLEANUP_INTERVAL);
        set_transient(self::TRANSIENT_LAST_CLEANUP, time(), max($interval, MINUTE_IN_SECONDS));

        $this->cleanup();
    }

 
    public function cleanup(): int
    {
        $cleaned = 0;

        try {
            foreach ($this->repository->findDueForCleanup(self::CLEANUP_BATCH_SIZE, self::MAX_CLEANUP_ATTEMPTS) as $session) {
                if (!$this->deleteArtifact($session)) {
                    $this->recordFailedAttempt($session);
                    continue;
                }

                $this->saveSessionAsExpiredUnlessTerminal($session);
                $cleaned++;
            }

            $this->repository->purgeTerminalRows(self::ROW_RETENTION);
            $this->cleanupOrphanDirectories();
        } catch (Throwable $e) {
            debug_log($this->tokenGenerator->redactTokens('WP STAGING: Transfer session cleanup failed. ' . $e->getMessage()));
        }

        return $cleaned;
    }







    public function removeAllArtifacts(int $blogId = 0): int
    {
        $removed = 0;

        try {
            foreach ($this->repository->findWithArtifact($blogId) as $session) {
                if (!$this->deleteArtifactEvenWhenItsPathsAreUnreadable($session)) {
                    continue;
                }

                $this->saveSessionAsExpiredUnlessTerminal($session);
                $removed++;
            }

            if ($blogId === 0) {
                $this->cleanupOrphanDirectories(0);
            }
        } catch (Throwable $e) {
            debug_log($this->tokenGenerator->redactTokens('WP STAGING: Could not remove the transfer links. ' . $e->getMessage()));
        }

        return $removed;
    }







    public function deleteArtifact(TransferSession $session): bool
    {
        if ($session->hasUnreadableArtifactPaths()) {
            return false;
        }

        $deleted = true;

        if ($session->transferFilePath !== '') {
            foreach ([$session->transferFilePath, $session->transferFilePath . TransferArtifactFactory::TMP_SUFFIX] as $path) {
                $deleted = $this->deleteArtifactFile($path) && $deleted;
            }
        }

        if ($session->transferDir !== '') {
            $deleted = $this->deleteSessionDirectory($session->transferDir) && $deleted;
        }

        if ($deleted) {
            $session->transferFilePath = '';
            $session->transferDir      = '';
        }

        return $deleted;
    }





    private function deleteArtifactEvenWhenItsPathsAreUnreadable(TransferSession $session): bool
    {
        if (!$session->hasUnreadableArtifactPaths()) {
            return $this->deleteArtifact($session);
        }

        return $this->deleteTokenDirectoryWithHash($session->publicTokenHash);
    }

 
    private function deleteTokenDirectoryWithHash(string $publicTokenHash): bool
    {
        if ($publicTokenHash === '') {
            return false;
        }

        foreach ($this->findTokenDirectories() as $item) {
            if (hash_equals($publicTokenHash, $this->tokenGenerator->hash($item->getBasename()))) {
                return $this->deleteSessionDirectory(trailingslashit($item->getPathname()));
            }
        }

        return true;
    }

 
    private function deleteArtifactFile(string $path): bool
    {
        return $this->pathGuard->deleteFileWithRedactedWarnings($path, $this->createWarningLogger());
    }







    private function recordFailedAttempt(TransferSession $session)
    {
        $session->cleanupAttempts++;

        if ($session->cleanupAttempts >= self::MAX_CLEANUP_ATTEMPTS) {
            $session->errorMessage = TransferSessionException::cannotDeleteArtifact()->getMessage();
        }

        $this->repository->update($session);

        debug_log(sprintf('WP STAGING: Could not clean up the artifact of transfer session #%d (attempt %d).', $session->id, $session->cleanupAttempts));
    }

 
    private function saveSessionAsExpiredUnlessTerminal(TransferSession $session)
    {
        if (!TransferSessionStatus::isTerminal($session->status)) {
            $session->status = TransferSessionStatus::EXPIRED;
        }

        $this->repository->update($session);
    }







    public function cleanupOrphanDirectories(int $minimumAge = self::ORPHAN_SAFETY_BUFFER + TransferSessionService::MAX_TTL): int
    {
        $swept = $this->sweepOrphanDirectories($minimumAge);

        return $swept['removed'];
    }







    public function removeUnclaimedDirectories(): bool
    {
        $swept = $this->sweepOrphanDirectories(0);

        return $swept['leftBehind'] === 0;
    }





    private function sweepOrphanDirectories(int $minimumAge): array
    {
        $knownDirectories = $this->repository->getKnownTransferDirectoryNames(self::MAX_CLEANUP_ATTEMPTS);
        $removed          = 0;
        $leftBehind       = 0;

        foreach ($this->findTokenDirectories() as $item) {
            if (in_array($item->getBasename(), $knownDirectories, true)) {
                continue;
            }

            if ((time() - $item->getMTime()) < $minimumAge) {
                $leftBehind++;
                continue;
            }

            if ($this->deleteSessionDirectory(trailingslashit($item->getPathname()))) {
                $removed++;
                continue;
            }

            $leftBehind++;
        }

        if ($removed > 0) {
            debug_log(sprintf('WP STAGING: Removed %d orphaned transfer directories.', $removed));
        }

        return ['removed' => $removed, 'leftBehind' => $leftBehind];
    }

 
    private function findTokenDirectories(): Generator
    {
        $baseDirectory = $this->transferDirectory->getBaseDirectory();

        if (!is_dir($baseDirectory)) {
            return;
        }

        foreach (new DirectoryIterator($baseDirectory) as $item) {
            if (!$item->isDot() && $item->isDir() && $this->tokenGenerator->isValidFormat($item->getBasename())) {
                yield $item;
            }
        }
    }





    private function deleteSessionDirectory(string $directory): bool
    {
        if (!$this->pathGuard->isDeletableTransferPath($directory)) {
            debug_log('WP STAGING: Refused to delete a transfer directory outside the transfer base directory.');

            return false;
        }

        if (!is_dir($directory)) {
            return true;
        }

        try {
            $directoryEntries = new DirectoryIterator($directory);
        } catch (UnexpectedValueException $e) {
            debug_log($this->tokenGenerator->redactTokens('WP STAGING: Could not read a transfer directory. ' . $e->getMessage()));

            return false;
        }

        $unexpected = [];
        $ownedFiles = [];

        foreach ($directoryEntries as $item) {
            if ($item->isDot()) {
                continue;
            }

            if (!$item->isDir() && $this->isExpectedArtifactFileName($item->getBasename())) {
                $ownedFiles[] = $item->getPathname();
                continue;
            }

            $unexpected[] = $item->getBasename();
        }

        if (!empty($unexpected)) {
            debug_log($this->tokenGenerator->redactTokens(sprintf('WP STAGING: Left transfer directory %s in place, it contains unexpected entries: %s', basename(untrailingslashit($directory)), implode(', ', $unexpected))));

            return false;
        }

        foreach ($ownedFiles as $ownedFile) {
            $this->pathGuard->deleteFileWithRedactedWarnings($ownedFile, $this->createWarningLogger());
        }

        return (bool)$this->pathGuard->runFilesystemCallWithRedactedWarnings(function () use ($directory) {
            return rmdir($directory);
        }, $this->createWarningLogger());
    }

 
    private function isExpectedArtifactFileName(string $fileName): bool
    {
        if (in_array($fileName, ['index.php', 'index.html', '.htaccess', 'web.config'], true)) {
            return true;
        }

        return strpos($fileName, TransferArtifactNaming::BASE_NAME . '.') === 0 || strpos($fileName, TransferArtifactNaming::BASE_NAME . '-') === 0;
    }

    private function createWarningLogger(): callable
    {
        return function (string $warning) {
            debug_log('WP STAGING: ' . $warning);
        };
    }
}
