<?php

namespace WPStaging\Backup;

class BackupRetentionHandler
{
    /**
     * @var string
     */
    const OPTION_BACKUPS_RETENTION = 'wpstg_backups_retention';

    /**
     * @var array
     */
    protected $backupsRetention;

    /**
     * @param  array $backups
     * @return bool
     */
    public function updateBackupsRetentionOptions(array $backups): bool
    {
        return update_option(self::OPTION_BACKUPS_RETENTION, $backups);
    }

    /**
     * @param string|bool $storage if it is empty string('') all backups retention will be returned!
     *
     * @return array
     *
     * An array of arrays containing backup information:
     *    - 'backupId': An array with backup details.
     *        - 'createdDate': A string representing the date and time of backup creation.
     *        - 'storages': An array of storage types used for the backup.
     *        - 'backupSize': An integer representing the size of the backup.
     *        - 'isMultipart': A boolean indicating whether the backup is multipart.
     */
    public function getBackupsRetention($storage = ''): array
    {
        if ($storage === false) {
            return [];
        }

        $backups = (array) get_option(self::OPTION_BACKUPS_RETENTION, []);

        if ($storage) {
            $backups = array_filter($backups, function ($backup) use ($storage) {
                return in_array($storage, $backup['storages'], true);
            });
        }

        return $backups;
    }

    /**
     * @param  string $backupId
     * @param  string $storageToRemove
     * @return bool
     */
    public function unsetStorageFromBackupsRetention(string $backupId, string $storageToRemove): bool
    {
        $this->backupsRetention = $this->getBackupsRetention();

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

        // Don't hold backup without storage.
        if (empty($currentBackup['storages'])) {
            unset($this->backupsRetention[$backupId]);
        }

        // Don't hold backup with only localStorage.
        if (count($currentBackup['storages']) === 1 && reset($currentBackup['storages']) === 'localStorage') {
            unset($this->backupsRetention[$backupId]);
        }

        $this->updateBackupsRetentionOptions($this->backupsRetention);

        return true;
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
