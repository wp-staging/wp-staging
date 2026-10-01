<?php

namespace WPStaging\Backup\Storage;

use WPStaging\Core\WPStaging;





class StorageProfiles
{
 
    const PROFILE_CAPABLE_PROVIDERS = [
        Providers::IDENTIFIER_SFTP,
    ];

 
    const FIRST_PROFILE_INDEX = 1;

 
    const MAX_PROFILES = 10;

 
    const MAX_NAME_LENGTH = 40;

 
    private $profileStore = null;







    public function getProfiles(string $providerId): array
    {
        if (!$this->isKnownProvider($providerId)) {
            return [];
        }

        $defaultName = $this->getDefaultName($providerId);
        if (!$this->isProfileCapable($providerId)) {
            return [$providerId => $defaultName];
        }

        $stored   = $this->getStoredProfiles($providerId);
        $profiles = [$providerId => $defaultName];

        foreach ($stored as $storageId => $name) {
            $profiles[$storageId] = $name === '' ? $this->getDefaultName($storageId) : $name;
        }

        return array_slice($profiles, 0, self::MAX_PROFILES, true);
    }





    private function getStoredProfiles(string $providerId): array
    {
        $stored   = $this->getStoredProvider($providerId);
        $profiles = [];

        foreach ($stored['profiles'] as $storageId => $name) {
            if ($this->getBaseProvider((string)$storageId) !== $providerId) {
                continue;
            }

            $profiles[(string)$storageId] = (string)$name;
        }

        return $profiles;
    }





    public function getProfileIds(string $providerId): array
    {
        return array_keys($this->getProfiles($providerId));
    }





    public function isProfileCapable(string $providerId): bool
    {
        return in_array($providerId, self::PROFILE_CAPABLE_PROVIDERS, true);
    }







    private function isKnownProvider(string $providerId): bool
    {
        return array_key_exists($providerId, Providers::STORAGE_LABELS);
    }







    public function getBaseProvider(string $storageId): string
    {
        if (preg_match('/^(.+)-[1-9][0-9]*$/', $storageId, $matches) !== 1) {
            return $storageId;
        }

        return $this->isProfileCapable($matches[1]) ? $matches[1] : $storageId;
    }





    public function exists(string $storageId): bool
    {
        return array_key_exists($storageId, $this->getProfiles($this->getBaseProvider($storageId)));
    }







    public function isAdditionalProfile(string $storageId): bool
    {
        return $this->getBaseProvider($storageId) !== $storageId;
    }





    public function getProfileName(string $storageId): string
    {
        $profiles = $this->getProfiles($this->getBaseProvider($storageId));

        return isset($profiles[$storageId]) ? $profiles[$storageId] : $this->getDefaultName($storageId);
    }





    public function canAddProfile(string $providerId): bool
    {
        return $this->isProfileCapable($providerId) && count($this->getProfiles($providerId)) < self::MAX_PROFILES;
    }








    public function isNameTaken(string $name, string $exceptStorageId = ''): bool
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === '') {
            return false;
        }

        foreach (array_keys(Providers::STORAGE_LABELS) as $providerId) {
            foreach ($this->getProfiles($providerId) as $storageId => $profileName) {
                if ($storageId === $exceptStorageId) {
                    continue;
                }

                if (mb_strtolower($profileName) === mb_strtolower($normalized)) {
                    return true;
                }
            }
        }

        return false;
    }







    public function addProfile(string $providerId): string
    {
        if (!$this->canAddProfile($providerId)) {
            return '';
        }

        if (!$this->store()->migrateLegacyOption()) {
            return '';
        }

        $nextIndex            = $this->getNextIndex($providerId);
        $storageId            = $providerId . '-' . $nextIndex;
        $profiles             = $this->getStoredProfiles($providerId);
        $profiles[$storageId] = $this->nameForNewProfile($storageId);

        return $this->save($providerId, $profiles, $nextIndex + 1) ? $storageId : '';
    }







    private function nameForNewProfile(string $storageId): string
    {
        $candidate = $this->getDefaultName($storageId);
        if (!$this->isNameTaken($candidate, $storageId)) {
            return '';
        }

        $providerId = $this->getBaseProvider($storageId);
        $base       = $this->getDefaultName($providerId);
        $suffix = (int)substr($storageId, strlen($providerId) + 1);
        $limit  = $suffix + self::MAX_PROFILES + 1;

        do {
            $suffix++;
            $candidate = $this->normalizeName($base . ' ' . $suffix);
        } while ($suffix < $limit && $this->isNameTaken($candidate, $storageId));

        return $candidate;
    }






    public function renameProfile(string $storageId, string $name): bool
    {
        if (!$this->exists($storageId)) {
            return false;
        }

        if ($this->isNameTaken($this->displayedNameFor($storageId, $name), $storageId)) {
            return false;
        }

        $providerId           = $this->getBaseProvider($storageId);
        $normalized           = $this->normalizeName($name);
        $profiles             = $this->getStoredProfiles($providerId);
        $profiles[$storageId] = $normalized;

        return $this->save($providerId, $profiles, $this->getNextIndex($providerId));
    }







    public function deleteProfile(string $storageId): bool
    {
        if (!$this->isAdditionalProfile($storageId)) {
            return false;
        }

        if (!$this->exists($storageId)) {
            return false;
        }

        $providerId = $this->getBaseProvider($storageId);
        $profiles   = $this->getStoredProfiles($providerId);
        unset($profiles[$storageId]);

        return $this->save($providerId, $profiles, $this->getNextIndex($providerId));
    }





    private function getStoredProvider(string $providerId): array
    {
        if (!$this->isProfileCapable($providerId)) {
            return ['nextIndex' => 0, 'profiles' => []];
        }

        $provider = $this->store()->getRegistry();

        return [
            'nextIndex' => isset($provider['nextIndex']) ? (int)$provider['nextIndex'] : 0,
            'profiles'  => isset($provider['profiles']) && is_array($provider['profiles']) ? $provider['profiles'] : [],
        ];
    }





    private function getNextIndex(string $providerId): int
    {
        $highest = self::FIRST_PROFILE_INDEX;
        foreach (array_keys($this->getStoredProfiles($providerId)) as $storageId) {
            $highest = max($highest, (int)substr($storageId, strlen($providerId) + 1));
        }

        $stored = $this->getStoredProvider($providerId);

        return max($stored['nextIndex'], $highest + 1);
    }







    private function save(string $providerId, array $profiles, int $nextIndex): bool
    {
        if (!$this->isProfileCapable($providerId)) {
            return false;
        }

        $provider = [
            'nextIndex' => $nextIndex,
            'profiles'  => $profiles,
        ];

        if ($this->store()->getRegistry() === $provider) {
            return true;
        }

        return $this->store()->saveRegistry($provider);
    }




    public function getRegistry(): array
    {
        return $this->store()->getRegistry();
    }







    public function forgetAll()
    {
        foreach ($this->getProfileIds(Providers::IDENTIFIER_SFTP) as $storageId) {
            $this->store()->delete($storageId);
        }

        $this->store()->deleteRegistry();
    }




    private function store(): SftpProfileStore
    {
        if ($this->profileStore === null) {
            $this->profileStore = WPStaging::make(SftpProfileStore::class);
        }

        return $this->profileStore;
    }








    public function displayedNameFor(string $storageId, string $name): string
    {
        $normalized = $this->normalizeName($name);

        return $normalized === '' ? $this->getDefaultName($storageId) : $normalized;
    }





    private function getDefaultName(string $storageId): string
    {
        $providerId   = $this->getBaseProvider($storageId);
        $providerName = isset(Providers::STORAGE_LABELS[$providerId]) ? Providers::STORAGE_LABELS[$providerId] : $providerId;

        if ($storageId === $providerId) {
            return $providerName;
        }

        return $providerName . ' ' . substr($storageId, strlen($providerId) + 1);
    }





    private function normalizeName(string $name): string
    {
        return mb_substr(trim(sanitize_text_field($name)), 0, self::MAX_NAME_LENGTH);
    }
}
