<?php

namespace WPStaging\Backup\Storage;

use WPStaging\Core\WPStaging;
use WPStaging\Pro\Backup\Storage\Amazon\S3 as AmazonS3Auth;
use WPStaging\Pro\Backup\Storage\DigitalOceanSpaces\Auth as DOSAuth;
use WPStaging\Pro\Backup\Storage\GenericS3\Auth as GenericS3Auth;
use WPStaging\Pro\Backup\Storage\GoogleDrive\Auth as GoogleDriveAuth;
use WPStaging\Pro\Backup\Storage\Dropbox\Auth as DropboxAuth;
use WPStaging\Pro\Backup\Storage\OneDrive\Auth as OneDriveAuth;
use WPStaging\Pro\Backup\Storage\SFTP\Auth as SftpAuth;
use WPStaging\Pro\Backup\Storage\Wasabi\Auth as WasabiAuth;
use WPStaging\Pro\Backup\Storage\PCloud\Auth as PCloudAuth;
use WPStaging\Backup\Storage\Traits\StorageIdNormalizerTrait;









class Providers
{
    use StorageIdNormalizerTrait;

 
    const IDENTIFIER_GOOGLE_DRIVE = 'google-drive';

 
    const IDENTIFIER_AMAZON_S3 = 'amazon-s3';

 
    const IDENTIFIER_DROPBOX = 'dropbox';

 
    const IDENTIFIER_ONE_DRIVE = 'one-drive';

 
    const IDENTIFIER_PCLOUD = 'pcloud';

 
    const IDENTIFIER_SFTP = 'sftp';

 
    const IDENTIFIER_DIGITALOCEAN_SPACES = 'digitalocean-spaces';

 
    const IDENTIFIER_WASABI_S3 = 'wasabi-s3';

 
    const IDENTIFIER_GENERIC_S3 = 'generic-s3';





    const LEGACY_ID_MAP = [
        'googleDrive' => self::IDENTIFIER_GOOGLE_DRIVE,
        'amazonS3'    => self::IDENTIFIER_AMAZON_S3,
        'googledrive' => self::IDENTIFIER_GOOGLE_DRIVE,
        'amazons3'    => self::IDENTIFIER_AMAZON_S3,
    ];





    const REVERSE_LEGACY_ID_MAP = [
        self::IDENTIFIER_GOOGLE_DRIVE => 'googledrive',
        self::IDENTIFIER_AMAZON_S3    => 'amazons3',
    ];

 
    const LEGACY_OPTION_MAP = [
        self::IDENTIFIER_GOOGLE_DRIVE => 'wpstg_googledrive',
        self::IDENTIFIER_AMAZON_S3    => 'wpstg_amazons3',
    ];

 
    const LEGACY_PROPERTY_MAP = [
        self::IDENTIFIER_GOOGLE_DRIVE        => 'googleDrive',
        self::IDENTIFIER_AMAZON_S3           => 'amazonS3',
        self::IDENTIFIER_DIGITALOCEAN_SPACES => 'digitalOceanSpaces',
        self::IDENTIFIER_WASABI_S3           => 'wasabiS3',
        self::IDENTIFIER_GENERIC_S3          => 'genericS3',
        self::IDENTIFIER_ONE_DRIVE           => 'oneDrive',
        self::IDENTIFIER_PCLOUD              => 'pCloud',
    ];

 
    const STORAGE_LABELS = [
        self::IDENTIFIER_GOOGLE_DRIVE        => 'Google Drive',
        self::IDENTIFIER_AMAZON_S3           => 'Amazon S3',
        self::IDENTIFIER_SFTP                => 'FTP / SFTP',
        self::IDENTIFIER_DIGITALOCEAN_SPACES => 'DigitalOcean Spaces',
        self::IDENTIFIER_WASABI_S3           => 'Wasabi S3',
        self::IDENTIFIER_GENERIC_S3          => 'Generic S3',
        self::IDENTIFIER_DROPBOX             => 'Dropbox',
        self::IDENTIFIER_ONE_DRIVE           => 'Microsoft OneDrive',
        self::IDENTIFIER_PCLOUD              => 'pCloud',
    ];





    const SENSITIVE_OPTION_KEYS = [
        'accessKey',
        'accessToken',
        'ftpCertContent',
        'googleClientId',
        'googleClientSecret',
        'key',
        'passphrase',
        'password',
        'refreshToken',
        'secretKey',
        'sessionId',
        'sharedDriveId',
        'uploadId',
        'uploadUrl',
        'username',
    ];

    protected $storages = [];

 
    protected $storageProfiles;

 
    private $expandedStorages = null;

 
    private $expandedFromRegistry = null;








    public function __construct()
    {
        $this->storageProfiles = new StorageProfiles();

        $this->storages = [
            [
                'id'           => self::IDENTIFIER_GOOGLE_DRIVE,
                'cli'          => self::IDENTIFIER_GOOGLE_DRIVE,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_GOOGLE_DRIVE],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(GoogleDriveAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_GOOGLE_DRIVE),
            ],
            [
                'id'           => self::IDENTIFIER_AMAZON_S3,
                'cli'          => self::IDENTIFIER_AMAZON_S3,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_AMAZON_S3],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(AmazonS3Auth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_AMAZON_S3),
            ],
            [
                'id'           => self::IDENTIFIER_DROPBOX,
                'cli'          => self::IDENTIFIER_DROPBOX,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_DROPBOX],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(DropboxAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_DROPBOX),
            ],
            [
                'id'           => self::IDENTIFIER_ONE_DRIVE,
                'cli'          => self::IDENTIFIER_ONE_DRIVE,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_ONE_DRIVE],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(OneDriveAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_ONE_DRIVE),
            ],
            [
                'id'           => self::IDENTIFIER_PCLOUD,
                'cli'          => self::IDENTIFIER_PCLOUD,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_PCLOUD],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(PCloudAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_PCLOUD),
            ],
            [
                'id'           => self::IDENTIFIER_SFTP,
                'cli'          => self::IDENTIFIER_SFTP,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_SFTP],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(SftpAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_SFTP),
            ],
            [
                'id'           => self::IDENTIFIER_DIGITALOCEAN_SPACES,
                'cli'          => self::IDENTIFIER_DIGITALOCEAN_SPACES,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_DIGITALOCEAN_SPACES],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(DOSAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_DIGITALOCEAN_SPACES),
            ],
            [
                'id'           => self::IDENTIFIER_WASABI_S3,
                'cli'          => self::IDENTIFIER_WASABI_S3,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_WASABI_S3],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(WasabiAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_WASABI_S3),
            ],
            [
                'id'           => self::IDENTIFIER_GENERIC_S3,
                'cli'          => self::IDENTIFIER_GENERIC_S3,
                'name'         => self::STORAGE_LABELS[self::IDENTIFIER_GENERIC_S3],
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(GenericS3Auth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_GENERIC_S3),
            ],
        ];
    }







    private function expandProfiles(array $storages): array
    {
        $expanded = [];
        foreach ($storages as $storage) {
            $storage['icon'] = $storage['id'];

            $profiles = $this->storageProfiles->getProfiles($storage['id']);
            if ($profiles === []) {
                $expanded[] = $storage;
                continue;
            }

            foreach ($profiles as $profileId => $profileName) {
                $expanded[] = array_merge($storage, [
                    'id'           => $profileId,
                    'cli'          => $profileId,
                    'name'         => $profileName,
                    'settingsPath' => $this->getStorageAdminPage($profileId),
                ]);
            }
        }

        return $expanded;
    }









    public function getStorageIds($isEnabled = null)
    {
        return array_map(function ($storage) {
            return $storage['id'];
        }, $this->getStorages($isEnabled));
    }







    public function getBaseStorages($isEnabled = null): array
    {
        return $this->filterByEnabled($this->storages, $isEnabled);
    }






    private function filterByEnabled(array $storages, $isEnabled): array
    {
        if ($isEnabled === null) {
            return $storages;
        }

        return array_filter($storages, function ($storage) use ($isEnabled) {
            return $storage['enabled'] === $isEnabled;
        });
    }









    public function getStorages($isEnabled = null)
    {
        return $this->filterByEnabled($this->getExpandedStorages(), $isEnabled);
    }






    private function getExpandedStorages(): array
    {
        $registry = $this->storageProfiles->getRegistry();

        if ($this->expandedStorages === null || $this->expandedFromRegistry !== $registry) {
            $this->expandedStorages     = $this->expandProfiles($this->storages);
            $this->expandedFromRegistry = $registry;
        }

        return $this->expandedStorages;
    }











    public function getStorageProperty($id, $property, $isEnabled = null)
    {
        foreach ($this->getStorages($isEnabled) as $storage) {
            if ($storage['id'] === $id) {
                if (array_key_exists($property, $storage)) {
                    return $storage[$property];
                }
            }
        }

        return false;
    }





    public function isActivated(string $storageId): bool
    {
        $storage = $this->makeStorageFor($storageId);

        return $storage === null ? false : (bool)$storage->isAuthenticated();
    }








    public function makeStorage($class, string $storageId)
    {
        if (empty($class) || !class_exists($class)) {
            return null;
        }

        if (!$this->storageProfiles->exists($storageId)) {
            return null;
        }

 
        $storage = WPStaging::make($class);
        if ($storage instanceof ProfileAwareStorageInterface) {
            $storage->useProfile($storageId);
        }

        return $storage;
    }





    public function makeStorageFor(string $storageId)
    {
        return $this->makeStorage($this->getStorageProperty($storageId, 'authClass', true), $storageId);
    }







    public function getSettingsTemplate(string $storageId): string
    {
        return strtolower($this->storageProfiles->getBaseProvider($storageId));
    }




    public function getStorageProfiles(): StorageProfiles
    {
        return $this->storageProfiles;
    }





    protected function filterAuthClassForPro($id)
    {
        if (empty($id) || !WPStaging::isPro()) {
            return '';
        }

        return $id;
    }

    private function getStorageAdminPage($storageTab)
    {
        return admin_url('admin.php?page=wpstg-settings&tab=remote-storages&sub-tab=' . $storageTab);
    }





















    public function migrateRemoteStorageOptions()
    {
        $migrated = [];
        foreach (self::LEGACY_ID_MAP as $legacyId => $newId) {
            if (isset($migrated[$newId])) {
                continue;
            }

            $newOptionName = 'wpstg_' . $newId;

 
 
            $newValue = get_option($newOptionName);
            if ($newValue !== false) {
                $migrated[$newId] = true;
                continue;
            }

            $legacyOptionNames = array_unique([
                'wpstg_' . $legacyId,
                'wpstg_' . strtolower($legacyId),
            ]);

            foreach ($legacyOptionNames as $legacyOptionName) {
                $legacyValue = get_option($legacyOptionName, []);
                if (empty($legacyValue)) {
                    continue;
                }

                update_option($newOptionName, $legacyValue, false);
                break;
            }

            $migrated[$newId] = true;
        }
    }




    public function deleteMigratedLegacyStorageOptions()
    {
        foreach (self::LEGACY_OPTION_MAP as $newId => $legacyOptionName) {
            if (get_option('wpstg_' . $newId) === false) {
                continue;
            }

            delete_option($legacyOptionName);
        }
    }
}
