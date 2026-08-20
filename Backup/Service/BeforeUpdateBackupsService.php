<?php

namespace WPStaging\Backup\Service;

use SplFileInfo;
use Throwable;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Utils\BackupPathResolver;
use WPStaging\Framework\Facades\Hooks;

use function WPStaging\functions\debug_log;








class BeforeUpdateBackupsService
{




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

 
    private $backupPathResolver;

 
    private $backups = null;





    public function __construct(BackupsFinder $backupsFinder, BackupPathResolver $backupPathResolver)
    {
        $this->backupsFinder      = $backupsFinder;
        $this->backupPathResolver = $backupPathResolver;
    }








    public function findReusableBackup(array $requiredScope = []): array
    {
        $now    = time();
        $oldest = $now - $this->getReuseWindow();

        foreach ($this->findBackups() as $backup) {
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
        $keep = max(0, $this->getKeepCount() - $roomFor);

        $backups = $this->findBackups();
        if (count($backups) <= $keep) {
            return 0;
        }

        $deleted = 0;
        foreach (array_slice($backups, $keep) as $backup) {
            if ($this->delete($backup['file'], $backup['parts'])) {
                $deleted++;
            }
        }

        $this->backups = null;

        return $deleted;
    }




    private function findBackups(): array
    {
        if ($this->backups !== null) {
            return $this->backups;
        }

        $backups = [];
        foreach ($this->backupsFinder->findBackups() as $splFileInfo) {
            $metadata = $this->readMetadata($splFileInfo);
            if ($metadata === null || !$metadata->getIsBeforeUpdateBackup()) {
                continue;
            }

            $backups[] = [
                'file'        => $splFileInfo,
                'name'        => $metadata->getName(),
                'dateCreated' => (int)$metadata->getDateCreated(),
                'parts'       => $this->getParts($metadata),
                'scope'       => [
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

        $this->backups = $backups;

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
            $partPath = $this->backupPathResolver->resolveBackupPartPath($part, $backup->getFilename());
            if ($partPath === '' || !file_exists($partPath)) {
                continue;
            }

            if (!unlink($partPath)) {
                debug_log('WP STAGING: Could not delete backup part while pruning backup-before-update backups: ' . $partPath);

                return false;
            }
        }

        if (!unlink($backup->getPathname())) {
            debug_log('WP STAGING: Could not delete backup while pruning backup-before-update backups: ' . $backup->getPathname());

            return false;
        }

        return true;
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
