<?php

namespace WPStaging\Backup\Service;

use SplFileInfo;
use Throwable;
use WPStaging\Backup\WithBackupIdentifier;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Framework\Facades\Hooks;

use function WPStaging\functions\debug_log;








class BeforeUpdateBackupsService
{
    use WithBackupIdentifier;

 
    const STAGING_UPDATE_SCHEDULE_PREFIX = 'wpstg-staging-update-';

 
    const KEEP_PER_STAGING_SITE = 1;





    const FILTER_KEEP_COUNT = 'wpstg.backup.beforeUpdate.keepCount';





    const FILTER_REUSE_WINDOW = 'wpstg.backup.beforeUpdate.reuseWindowSeconds';

 
    const DEFAULT_KEEP_COUNT = 3;

 
    const DEFAULT_REUSE_WINDOW = 900;





    const SCOPE_KEYS = [
        'isExportingPlugins',
        'isExportingMuPlugins',
        'isExportingThemes',
        'isExportingUploads',
        'isExportingOtherWpContentFiles',
        'isExportingOtherWpRootFiles',
        'isExportingDatabase',
    ];

 
    private $backupsFinder;

 
    private $backups = null;




    public function __construct(BackupsFinder $backupsFinder)
    {
        $this->backupsFinder = $backupsFinder;
    }








    public function findReusableBackup(array $requiredScope = []): array
    {
        $now    = time();
        $oldest = $now - $this->getReuseWindow();

        foreach ($this->findBackups() as $backup) {
            if ($backup['isStagingUpdate']) {
                continue;
            }

            if ($backup['dateCreated'] < $oldest) {
                return [];
            }

            if ($backup['dateCreated'] > $now || !$this->covers($backup['scope'], $requiredScope)) {
                continue;
            }

            return [
                'name'       => $backup['name'],
                'ageMinutes' => (int)floor(($now - $backup['dateCreated']) / MINUTE_IN_SECONDS),
            ];
        }

        return [];
    }






    private function covers(array $scope, array $requiredScope): bool
    {
        foreach (self::SCOPE_KEYS as $key) {
            if (empty($requiredScope[$key])) {
                continue;
            }

            if (empty($scope[$key])) {
                return false;
            }
        }

        return true;
    }







    public function prune(int $roomFor = 0): int
    {
        return $this->pruneMatching(max(0, $this->getKeepCount() - $roomFor), function (array $backup): bool {
            return !$backup['isStagingUpdate'];
        });
    }









    public function pruneForSchedule(string $scheduleId): int
    {
        if (strpos($scheduleId, self::STAGING_UPDATE_SCHEDULE_PREFIX) !== 0) {
            return 0;
        }

        return $this->pruneMatching(self::KEEP_PER_STAGING_SITE, $this->matchesSchedule($scheduleId));
    }









    public function pruneForScheduleInDirectory(string $scheduleId, string $backupDirectory): int
    {
        $backupDirectory = realpath($backupDirectory);
        if (strpos($scheduleId, self::STAGING_UPDATE_SCHEDULE_PREFIX) !== 0 || $backupDirectory === false || !is_dir($backupDirectory)) {
            return 0;
        }

        $backups = $this->findBackupsInDirectory($backupDirectory);

        return $this->pruneMatching(self::KEEP_PER_STAGING_SITE, $this->matchesSchedule($scheduleId), $backups);
    }





    private function matchesSchedule(string $scheduleId)
    {
        return function (array $backup) use ($scheduleId): bool {
            return $backup['scheduleId'] === $scheduleId;
        };
    }









    public static function getStagingUpdateScheduleId(string $cloneId, string $url, string $path): string
    {
        return self::STAGING_UPDATE_SCHEDULE_PREFIX . substr(hash('sha256', $cloneId . '|' . untrailingslashit($url) . '|' . untrailingslashit($path)), 0, 24);
    }







    private function pruneMatching(int $keep, $matches, $backups = null): int
    {
        $usesCachedBackups = $backups === null;
        $backups           = array_values(array_filter($usesCachedBackups ? $this->findBackups() : $backups, $matches));

        if (count($backups) <= $keep) {
            return 0;
        }

        $deleted = 0;
        foreach (array_slice($backups, $keep) as $backup) {
            if ($this->delete($backup['file'], $backup['parts'])) {
                $deleted++;
            }
        }

        if ($usesCachedBackups) {
            $this->backups = null;
        }

        return $deleted;
    }




    private function findBackups(): array
    {
        if ($this->backups !== null) {
            return $this->backups;
        }

        $this->backups = $this->describeBackups($this->backupsFinder->findBackups());

        return $this->backups;
    }





    private function findBackupsInDirectory(string $directory): array
    {
        return $this->describeBackups($this->backupsFinder->findBackupsIn($directory));
    }





    private function describeBackups(array $files): array
    {
        $backups = [];
        foreach ($files as $splFileInfo) {
            $metadata = $this->readMetadata($splFileInfo);
            if ($metadata === null) {
                continue;
            }

            $scheduleId      = (string)$metadata->getScheduleId();
            $isStagingUpdate = strpos($scheduleId, self::STAGING_UPDATE_SCHEDULE_PREFIX) === 0;
            if (!$metadata->getIsBeforeUpdateBackup() && !$isStagingUpdate) {
                continue;
            }

            $backups[] = [
                'file'            => $splFileInfo,
                'name'            => $metadata->getName(),
                'dateCreated'     => (int)$metadata->getDateCreated(),
                'parts'           => $this->getParts($metadata),
                'scheduleId'      => $scheduleId,
                'isStagingUpdate' => $isStagingUpdate,
                'scope'           => [
                    'isExportingPlugins'             => $metadata->getIsExportingPlugins(),
                    'isExportingMuPlugins'           => $metadata->getIsExportingMuPlugins(),
                    'isExportingThemes'              => $metadata->getIsExportingThemes(),
                    'isExportingUploads'             => $metadata->getIsExportingUploads(),
                    'isExportingOtherWpContentFiles' => $metadata->getIsExportingOtherWpContentFiles(),
                    'isExportingOtherWpRootFiles'    => $metadata->getIsExportingOtherWpRootFiles(),
                    'isExportingDatabase'            => $metadata->getIsExportingDatabase(),
                ],
            ];
        }

        usort($backups, function ($left, $right) {
            return $right['dateCreated'] - $left['dateCreated'];
        });

        return $backups;
    }





    private function readMetadata(SplFileInfo $splFileInfo)
    {
        try {
            return (new BackupMetadata())->hydrateByFilePath($splFileInfo->getPathname());
        } catch (Throwable $e) {
            debug_log('WP STAGING: Could not read metadata while pruning backup-before-update backups - File: ' . $splFileInfo->getPathname() . ' - ' . $e->getMessage());

            return null;
        }
    }





    private function getParts(BackupMetadata $metadata): array
    {
        if (!$metadata->getIsMultipartBackup()) {
            return [];
        }

        return $metadata->getMultipartMetadata()->getBackupParts();
    }






    private function delete(SplFileInfo $backup, array $parts): bool
    {
        foreach ($parts as $part) {
            $partPath = $this->resolvePartPath($part, $backup);
            if ($partPath === '' || !file_exists($partPath)) {
                continue;
            }

            if (!unlink($partPath)) {
                debug_log('WP STAGING: Could not delete backup part while pruning backup-before-update backups: ' . $partPath);

                return false;
            }
        }

 
 
 
        if (!file_exists($backup->getPathname())) {
            return true;
        }

        if (!unlink($backup->getPathname())) {
            debug_log('WP STAGING: Could not delete backup while pruning backup-before-update backups: ' . $backup->getPathname());

            return false;
        }

        return true;
    }






    private function resolvePartPath(string $part, SplFileInfo $backup): string
    {
        if ($part === '' || $part !== wp_basename($part) || !$this->isBackupPart($part)) {
            return '';
        }

        if ($this->extractBackupIdFromFilename($part) !== $this->extractBackupIdFromFilename($backup->getFilename())) {
            return '';
        }

        $backupDirectory = realpath($backup->getPath());
        if ($backupDirectory === false) {
            return '';
        }

        $partPath = trailingslashit($backupDirectory) . $part;
        if (!file_exists($partPath) || is_link($partPath)) {
            return '';
        }

        return $partPath;
    }




    private function getKeepCount(): int
    {
        return (int)Hooks::applyFilters(self::FILTER_KEEP_COUNT, self::DEFAULT_KEEP_COUNT);
    }




    private function getReuseWindow(): int
    {
        return (int)Hooks::applyFilters(self::FILTER_REUSE_WINDOW, self::DEFAULT_REUSE_WINDOW);
    }
}
