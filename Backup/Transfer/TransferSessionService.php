<?php

namespace WPStaging\Backup\Transfer;

use DirectoryIterator;
use SplFileInfo;
use Throwable;
use WPStaging\Backup\Service\BackupsFinder;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Framework\Facades\DataEncryption;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Filesystem\Filesystem;

use function WPStaging\functions\debug_log;





class TransferSessionService
{
 
    const FILTER_ENABLED = 'wpstg.transfer_session.enabled';

 
    const FILTER_DEFAULT_TTL = 'wpstg.transfer_session.default_ttl';

 
    const FILTER_MAX_TTL = 'wpstg.transfer_session.max_ttl';

 
    const FILTER_DELETE_AFTER_BUFFER = 'wpstg.transfer_session.delete_after_buffer';

 
    const DEFAULT_TTL = 30 * MINUTE_IN_SECONDS;

 
    const MIN_TTL = 5 * MINUTE_IN_SECONDS;

 
    const MAX_TTL = DAY_IN_SECONDS;

 
    const DEFAULT_DELETE_AFTER_BUFFER = 30 * MINUTE_IN_SECONDS;

 
    private $backupsFinder;

 
    private $transferDirectory;

 
    private $pathGuard;

 
    private $tokenGenerator;

 
    private $artifactFactory;

 
    private $artifactNaming;

 
    private $repository;

 
    private $lock;

 
    private $cleanupService;

 
    private $filesystem;

 
    private $backupPathResolver;

    public function __construct(
        BackupsFinder $backupsFinder,
        TransferDirectory $transferDirectory,
        TransferPathGuard $pathGuard,
        TransferTokenGenerator $tokenGenerator,
        TransferArtifactFactory $artifactFactory,
        TransferArtifactNaming $artifactNaming,
        TransferSessionRepository $repository,
        TransferSessionLock $lock,
        TransferCleanupService $cleanupService,
        Filesystem $filesystem,
        BackupPathResolver $backupPathResolver
    ) {
        $this->backupsFinder      = $backupsFinder;
        $this->transferDirectory  = $transferDirectory;
        $this->pathGuard          = $pathGuard;
        $this->tokenGenerator     = $tokenGenerator;
        $this->artifactFactory    = $artifactFactory;
        $this->artifactNaming     = $artifactNaming;
        $this->repository         = $repository;
        $this->lock               = $lock;
        $this->cleanupService     = $cleanupService;
        $this->filesystem         = $filesystem;
        $this->backupPathResolver = $backupPathResolver;
    }

    public function isEnabled(): bool
    {
        return (bool)Hooks::applyFilters(self::FILTER_ENABLED, true);
    }





    public function createForBackupId(string $backupId, int $requestedTtl = 0): TransferSession
    {
        $backupFile = $this->findBackupFileById($backupId);

        if ($backupFile === null) {
            throw TransferSessionException::sourceFileNotFound();
        }

        return $this->createForPath($backupFile->getPathname(), $requestedTtl, $backupId);
    }

 
    public function createForPath(string $sourcePath, int $requestedTtl = 0, string $backupId = ''): TransferSession
    {
        return $this->createSession($sourcePath, $requestedTtl, $backupId, false);
    }







    public function createForFinishedSyncBackup(string $sourcePath, int $requestedTtl = 0): TransferSession
    {
        return $this->createSession($sourcePath, $requestedTtl, '', true);
    }







    public function revoke(int $sessionId): TransferSession
    {
        $session = $this->findSessionOfCurrentSite($sessionId);

        if ($session === null) {
            throw TransferSessionException::sessionNotFound();
        }

        $isEveryLinkRevoked   = $this->revokeSession($session);
        $hasUnreadableSibling = false;

        foreach ($this->repository->findActiveByBackupId($session->backupId) as $siblingSession) {
            if ($siblingSession->id === $session->id || $siblingSession->blogId !== $session->blogId) {
                continue;
            }

            if ($siblingSession->hasUnreadableArtifactPaths()) {
                $hasUnreadableSibling = true;
                continue;
            }

            $isEveryLinkRevoked = $this->revokeSession($siblingSession) && $isEveryLinkRevoked;
        }

        $isEveryUnclaimedFolderRemoved = !$hasUnreadableSibling || $this->cleanupService->removeUnclaimedDirectories();

        if (!$isEveryLinkRevoked) {
            throw TransferSessionException::cannotDeleteArtifact();
        }

        if (!$isEveryUnclaimedFolderRemoved) {
            throw TransferSessionException::unreadableLinkFolderNotCleared();
        }

        return $session;
    }

 
    private function revokeSession(TransferSession $session): bool
    {
        if (!$this->cleanupService->deleteArtifact($session)) {
            $session->errorMessage = TransferSessionException::cannotDeleteArtifact()->getMessage();
            $this->repository->update($session);

            debug_log(sprintf('WP STAGING: Could not revoke transfer session #%d (token %s...). The artifact could not be deleted.', $session->id, $session->publicTokenPrefix));

            return false;
        }

        $session->status    = TransferSessionStatus::REVOKED;
        $session->revokedAt = current_time('mysql', true);
        $this->repository->update($session);

        debug_log(sprintf('WP STAGING: Revoked transfer session #%d (token %s...) for backup %s.', $session->id, $session->publicTokenPrefix, $session->backupId));

        return true;
    }







    public function deleteTransferArtifactsBeforeBackupDeletion(string $blockReason, string $sourcePath): string
    {
        if ($blockReason !== '') {
            return $blockReason;
        }

        $result = $this->closeSessionsForSourcePath($sourcePath);

        if ($result['failed'] > 0) {
            return __('A temporary download link for this backup could not be removed, so the backup was kept. Deleting it now would leave its contents downloadable through that link. Revoke the link and try again.', 'wp-staging');
        }

        if ($result['hasUnreachableArtifact']) {
            return __('A temporary download link for this backup was created with WordPress security keys this site no longer has, so WP STAGING can no longer tell which file belongs to it. It could not clear the folder those links are served from either, because something it did not put there is still inside. The backup was kept, because deleting it now would leave its contents downloadable. Empty wp-content/wp-staging/transfers and try again.', 'wp-staging');
        }

        return '';
    }







    private function closeSessionsForSourcePath(string $sourcePath): array
    {
        if ($sourcePath === '') {
            return ['failed' => 0, 'hasUnreachableArtifact' => false];
        }

        $normalizedPath = wp_normalize_path($sourcePath);
        $realPath       = realpath($sourcePath);
        $sessions       = $this->repository->findBySourcePath($normalizedPath);

        if ($realPath !== false && wp_normalize_path($realPath) !== $normalizedPath) {
            $sessions = array_merge($sessions, $this->repository->findBySourcePath(wp_normalize_path($realPath)));
        }

        $failed     = 0;
        $unreadable = 0;

        foreach ($sessions as $session) {
            if ($session->hasUnreadableArtifactPaths()) {
                $unreadable++;
                continue;
            }

            if ($session->transferFilePath === '' && $session->transferDir === '') {
                continue;
            }

            if (!$this->cleanupService->deleteArtifact($session)) {
                debug_log(sprintf('WP STAGING: Could not delete the transfer artifact of session #%d while deleting its backup.', $session->id));
                $failed++;
                continue;
            }

            if (!TransferSessionStatus::isTerminal($session->status)) {
                $session->status      = TransferSessionStatus::COMPLETED;
                $session->completedAt = current_time('mysql', true);
            }

            $this->repository->update($session);
        }

        return [
            'failed'                 => $failed,
            'hasUnreachableArtifact' => $unreadable > 0 && !$this->cleanupService->removeUnclaimedDirectories(),
        ];
    }

 
    public function markDownloadStarted(int $sessionId): TransferSession
    {
        $session = $this->findSessionOfCurrentSite($sessionId);

        if ($session === null || !$session->isReady()) {
            throw TransferSessionException::sessionNotFound();
        }

        $session->downloadStartedCount++;
        $session->lastAccessedAt = current_time('mysql', true);
        $this->repository->update($session);

        return $session;
    }

 
    public function getActiveSession(string $backupId)
    {
        $now = time();

        foreach ($this->repository->findActiveByBackupId($backupId) as $session) {
            if ($this->isSessionOfferedOnThisSite($session, $now)) {
                return $session;
            }
        }

        return null;
    }

    public function getDefaultTtl(): int
    {
        return $this->clampTtl((int)Hooks::applyFilters(self::FILTER_DEFAULT_TTL, self::DEFAULT_TTL));
    }

    public function getMaxTtl(): int
    {
        $maxTtl = (int)Hooks::applyFilters(self::FILTER_MAX_TTL, self::MAX_TTL);

        return min(max($maxTtl, self::MIN_TTL), self::MAX_TTL);
    }

 
    public function getDeleteAfterBuffer(): int
    {
        $buffer = (int)Hooks::applyFilters(self::FILTER_DELETE_AFTER_BUFFER, self::DEFAULT_DELETE_AFTER_BUFFER);

        return max($buffer, 0);
    }

 
    private function createSession(string $sourcePath, int $requestedTtl, string $backupId, bool $isFinishedSyncBackup): TransferSession
    {
        if (!$this->isEnabled()) {
            throw TransferSessionException::transferDisabled();
        }

        $this->cleanupService->cleanup();

        $sourcePath = $this->pathGuard->resolveSourcePath($sourcePath);

        $isEligible = $isFinishedSyncBackup ? $this->artifactNaming->isFinishedSyncBackupFile($sourcePath) : $this->artifactNaming->isFinalizedBackupFile($sourcePath);
        if (!$isEligible) {
            throw TransferSessionException::backupStillBeingWritten();
        }

        if ($backupId === '') {
            $backupId = md5(basename($sourcePath));
        }

        $staleLockSeconds = $isFinishedSyncBackup ? TransferSessionLock::SYNC_STALE_LOCK_SECONDS : TransferSessionLock::STALE_LOCK_SECONDS;
        if (!$this->lock->acquire($sourcePath, $staleLockSeconds)) {
            throw TransferSessionException::alreadyPreparing();
        }

        try {
            if (!$this->isSourceFileStillThere($sourcePath)) {
                throw TransferSessionException::sourceFileNotFound();
            }

            $ttl             = $this->resolveTtl($requestedTtl);
            $reusableSession = $this->findReusableSession($backupId, $sourcePath, $ttl);

            if ($reusableSession !== null) {
                return $reusableSession;
            }

            return $this->prepareSession($sourcePath, $backupId, $ttl);
        } finally {
            $this->lock->release($sourcePath);
        }
    }





    private function isSourceFileStillThere(string $sourcePath): bool
    {
        clearstatcache(true, $sourcePath);

        return is_file($sourcePath);
    }







    private function findReusableSession(string $backupId, string $sourcePath, int $ttl)
    {
        $now = time();

        foreach ($this->repository->findActiveByBackupId($backupId) as $session) {
            $hasEnoughLifeLeft = (int)strtotime($session->expiresAt . ' UTC') - $now >= intdiv($ttl, 2);

            if ($this->isSessionOfferedOnThisSite($session, $now) && $hasEnoughLifeLeft && $session->sourcePath === $sourcePath) {
                return $session;
            }
        }

        return null;
    }

 
    private function isSessionOfferedOnThisSite(TransferSession $session, int $now): bool
    {
        return $session->blogId === get_current_blog_id() && $session->isReady() && !$session->isExpired($now) && file_exists($session->transferFilePath);
    }

 
    private function prepareSession(string $sourcePath, string $backupId, int $ttl): TransferSession
    {
        if (!$this->transferDirectory->isPubliclyAddressable()) {
            throw TransferSessionException::transferDirectoryNotPublic();
        }

        if (!$this->canProtectStoredPaths()) {
            throw TransferSessionException::encryptionUnavailable();
        }

        $this->transferDirectory->prepareBaseDirectory();

        $token   = $this->tokenGenerator->generate();
        $session = $this->buildSession($sourcePath, $backupId, $token, $ttl);

        if ($this->repository->insert($session) === 0) {
            throw TransferSessionException::storageUnavailable();
        }

        try {
            $this->createArtifactDirectory($session->transferDir);
            $session->artifactMode = $this->artifactFactory->create($sourcePath, $session->transferFilePath);
            $session->status       = TransferSessionStatus::READY;
            $this->repository->update($session);
        } catch (Throwable $e) {
            $this->failSession($session, $e->getMessage());

            throw $e instanceof TransferSessionException ? $e : TransferSessionException::artifactCreationFailed();
        }

        debug_log(sprintf(
            'WP STAGING: Prepared transfer session #%d for backup %s (token %s..., mode %s, size %s, expires %s).',
            $session->id,
            $session->backupId,
            $session->publicTokenPrefix,
            $session->artifactMode,
            size_format($session->fileSize),
            $session->expiresAt
        ));

        return $session;
    }

 
    private function canProtectStoredPaths(): bool
    {
        $probe = DataEncryption::encrypt('wpstg-transfer-session-probe');

        return $probe !== '' && DataEncryption::isEncrypted($probe);
    }

    private function buildSession(string $sourcePath, string $backupId, string $token, int $ttl): TransferSession
    {
        $fileName    = $this->artifactNaming->getArtifactFileName($sourcePath);
        $now         = time();
        $transferDir = $this->transferDirectory->getSessionDirectory($token);

        $session                    = new TransferSession();
        $session->backupId          = $backupId;
        $session->sourcePath        = $sourcePath;
        $session->publicTokenHash   = $this->tokenGenerator->hash($token);
        $session->publicTokenPrefix = $this->tokenGenerator->getPrefix($token);
        $session->transferDir       = $transferDir;
        $session->transferFilePath  = $transferDir . $fileName;
        $session->transferUrl       = $this->transferDirectory->getArtifactUrl($token, $fileName);
        $session->artifactMode      = TransferArtifactMode::FAILED;
        $session->status            = TransferSessionStatus::PREPARING;
        $session->fileSize          = (int)filesize($sourcePath);
        $session->expiresAt         = gmdate('Y-m-d H:i:s', $now + $ttl);
        $session->deleteAfter       = gmdate('Y-m-d H:i:s', $now + $ttl + $this->getDeleteAfterBuffer());
        $session->createdBy         = get_current_user_id();
        $session->blogId            = get_current_blog_id();

        return $session;
    }








    private function createArtifactDirectory(string $transferDir)
    {
        $transferDir = $this->pathGuard->resolveTransferPath($transferDir);

        if (!$this->filesystem->mkdir($transferDir)) {
            debug_log('WP STAGING: Could not create a transfer directory below ' . $this->transferDirectory->getBaseDirectory());

            throw TransferSessionException::cannotCreateTransferDirectory();
        }

        $this->transferDirectory->addAntiListingFiles($transferDir);
    }







    private function findSessionOfCurrentSite(int $sessionId)
    {
        $session = $this->repository->findById($sessionId);

        if ($session === null || $session->blogId !== get_current_blog_id()) {
            return null;
        }

        return $session;
    }







    private function failSession(TransferSession $session, string $reason)
    {
        $removed = $this->cleanupService->deleteArtifact($session);

        if ($removed) {
            $session->status = TransferSessionStatus::FAILED;
        }

        $session->artifactMode = TransferArtifactMode::FAILED;
        $session->errorMessage = $reason;
        $this->repository->update($session);

        debug_log(sprintf('WP STAGING: Transfer session #%d failed. %s', $session->id, $reason));
    }







    private function findBackupFileById(string $backupId)
    {
        if ($backupId === '' || !preg_match('/^[a-f0-9]{32}$/', $backupId)) {
            return null;
        }

        try {
            $iterator = new DirectoryIterator($this->backupsFinder->getBackupsDirectory());
        } catch (Throwable $e) {
            debug_log('WP STAGING: Could not read the backup directory while preparing a transfer session. ' . $e->getMessage());

            return null;
        }

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }

            if (md5($file->getBasename()) !== $backupId) {
                continue;
            }

            return $this->backupPathResolver->resolveBackupPath($file->getBasename()) === '' ? null : clone $file;
        }

        return null;
    }

    private function resolveTtl(int $requestedTtl): int
    {
        if ($requestedTtl <= 0) {
            return $this->getDefaultTtl();
        }

        return $this->clampTtl($requestedTtl);
    }

    private function clampTtl(int $ttl): int
    {
        return min(max($ttl, self::MIN_TTL), $this->getMaxTtl());
    }
}
