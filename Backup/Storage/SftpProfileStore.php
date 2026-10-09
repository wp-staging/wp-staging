<?php

namespace WPStaging\Backup\Storage;

use WPStaging\Framework\Settings\SettingsTable;





class SftpProfileStore
{
 
    const REGISTRY_KEY = 'sftp_profiles';

 
    const PROFILE_KEY_PREFIX = 'sftp_profile_';

 
    const LEGACY_OPTION_NAME = 'wpstg_sftp';

 
    const LOCK_WAIT_SECONDS = 5;

 
    private $settingsTable;

 
    private $tableIsThere;

 
    private static $isRunningLocked = false;




    public function __construct(SettingsTable $settingsTable)
    {
        $this->settingsTable = $settingsTable;
    }






    public function hasRegistry(): bool
    {
        return $this->settingsTableExists() && $this->settingsTable->has(self::REGISTRY_KEY);
    }







    public function hasDestination(string $storageId): bool
    {
        return $this->settingsTableExists() && $this->settingsTable->has($this->settingKey($storageId));
    }




    public function getRegistry(): array
    {
        $registry = $this->settingsTableExists() ? $this->settingsTable->get(self::REGISTRY_KEY, []) : [];

        return is_array($registry) ? $registry : [];
    }





    public function saveRegistry(array $registry): bool
    {
        $this->tableIsThere = true;

        return $this->settingsTable->set(self::REGISTRY_KEY, $registry);
    }




    public function deleteRegistry(): bool
    {
        return $this->settingsTable->delete(self::REGISTRY_KEY);
    }





    public function get(string $storageId)
    {
        $stored = $this->settingsTableExists() ? $this->settingsTable->get($this->settingKey($storageId), false) : false;
        if ($stored !== false) {
            return $stored;
        }

        if ($storageId !== Providers::IDENTIFIER_SFTP) {
            return false;
        }

        return get_option(self::LEGACY_OPTION_NAME);
    }






    public function save(string $storageId, $value): bool
    {
        $this->tableIsThere = true;

        return $this->settingsTable->set($this->settingKey($storageId), $value);
    }





    public function delete(string $storageId): bool
    {
        return $this->settingsTable->delete($this->settingKey($storageId));
    }









    public function runLocked(callable $operation)
    {
        global $wpdb;

        $lockName = 'wpstg_sftp_profiles_' . md5($wpdb->dbname . '|' . $wpdb->prefix);
        $acquired = (string)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $lockName, self::LOCK_WAIT_SECONDS));
        if ($acquired === '0') {
            return null;
        }

        try {
            self::$isRunningLocked = true;
            $this->forgetCachedDestinations();

            return $operation();
        } finally {
            self::$isRunningLocked = false;
            if ($acquired === '1') {
                $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lockName));
            }
        }
    }







    private function forgetCachedDestinations()
    {
        $this->settingsTable->forgetCachedValue(self::REGISTRY_KEY);

        $registry     = $this->getRegistry();
        $storageIds   = isset($registry['profiles']) && is_array($registry['profiles']) ? array_keys($registry['profiles']) : [];
        $storageIds[] = Providers::IDENTIFIER_SFTP;

        foreach (array_unique($storageIds) as $storageId) {
            $this->settingsTable->forgetCachedValue($this->settingKey((string)$storageId));
        }
    }







    public function migrateLegacyOption(): bool
    {
        if ($this->hasRegistry()) {
            return $this->deleteLegacyOptionIfPresent();
        }

        $stored = get_option(self::LEGACY_OPTION_NAME);
        if ($stored === false) {
            return true;
        }

        if (!$this->hasDestination(Providers::IDENTIFIER_SFTP) && !$this->save(Providers::IDENTIFIER_SFTP, $stored)) {
            return false;
        }

        $registry = [
            'nextIndex' => 0,
            'profiles'  => [Providers::IDENTIFIER_SFTP => ''],
        ];

        if (!$this->saveRegistry($registry)) {
            return false;
        }

        return $this->deleteLegacyOptionIfPresent();
    }






    private function deleteLegacyOptionIfPresent(): bool
    {
        if (get_option(self::LEGACY_OPTION_NAME) === false) {
            return true;
        }

        return delete_option(self::LEGACY_OPTION_NAME);
    }







    private function settingsTableExists(): bool
    {
        if ($this->tableIsThere === null || (!$this->tableIsThere && self::$isRunningLocked)) {
            $this->tableIsThere = $this->settingsTable->tableExists();
        }

        return $this->tableIsThere;
    }





    private function settingKey(string $storageId): string
    {
        return self::PROFILE_KEY_PREFIX . $storageId;
    }
}
