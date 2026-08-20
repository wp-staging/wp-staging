<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Storage\Traits\StorageIdNormalizerTrait;

class BackupRetentionHandler
{
    use StorageIdNormalizerTrait;




    const OPTION_BACKUPS_RETENTION = 'wpstg_backups_retention';




    protected $backupsRetention;





    public function updateBackupsRetentionOptions(array $backups): bool
    {
        $backups = $this->normalizeStorageIds($backups);
        return update_option(self::OPTION_BACKUPS_RETENTION, $backups);
    }













    public function getBackupsRetention($storage = ''): array
    {
        if ($storage === false) {
            return [];
        }

        $backups = (array) get_option(self::OPTION_BACKUPS_RETENTION, []);
        $originalBackups = $backups;
        $backups = $this->normalizeStorageIds($backups);

 
        if ($backups !== $originalBackups) {
            update_option(self::OPTION_BACKUPS_RETENTION, $backups);
        }

 
        $storage = $this->normalizeStorageId($storage);

        if ($storage) {
            $backups = array_filter($backups, function ($backup) use ($storage) {
                return in_array($storage, $backup['storages'], true);
            });
        }

        return $backups;
    }






    public function unsetStorageFromBackupsRetention(string $backupId, string $storageToRemove): bool
    {
        $this->backupsRetention = $this->getBackupsRetention();

 
        $storageToRemove = $this->normalizeStorageId($storageToRemove);

        if (!isset($this->backupsRetention[$backupId])) {
            $backupId = $this->getBackupId($backupId);
        }

        if (!isset($this->backupsRetention[$backupId])) {
            return false;
        }

        $currentBackup      = $this->backupsRetention[$backupId];
        $storageToRemoveKey = array_search($storageToRemove, $currentBackup['storages'], true);
        if ($storageToRemoveKey === false) {
            return false;
        }

        unset($currentBackup['storages'][$storageToRemoveKey]);
        $this->backupsRetention[$backupId] = $currentBackup;

 
        if (empty($currentBackup['storages'])) {
            unset($this->backupsRetention[$backupId]);
        }

 
        if (count($currentBackup['storages']) === 1 && reset($currentBackup['storages']) === 'localStorage') {
            unset($this->backupsRetention[$backupId]);
        }

        $this->updateBackupsRetentionOptions($this->backupsRetention);

        return true;
    }








    private function normalizeStorageIds(array $backups): array
    {
        foreach ($backups as $backupId => &$backup) {
            if (!isset($backup['storages']) || !is_array($backup['storages'])) {
                continue;
            }

            $backup['storages'] = array_unique(
                array_map([$this, 'normalizeStorageId'], $backup['storages'])
            );
        }

        unset($backup);

        return $backups;
    }

    private function getBackupId(string $backupName): string
    {
        $backupsRetention = $this->getBackupsRetention();
        foreach ($backupsRetention as $retainedBackupId => $retainedBackup) {
            if (strpos($backupName, $retainedBackupId) !== false) {
                return $retainedBackupId;
            }
        }

        return '';
    }
}
