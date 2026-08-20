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
        self::IDENTIFIER_SFTP                => 'sFTP/FTP',
        self::IDENTIFIER_DIGITALOCEAN_SPACES => 'Digital Ocean Spaces',
        self::IDENTIFIER_WASABI_S3           => 'Wasabi S3',
        self::IDENTIFIER_GENERIC_S3          => 'Generic S3',
        self::IDENTIFIER_DROPBOX             => 'Dropbox',
        self::IDENTIFIER_ONE_DRIVE           => 'Microsoft OneDrive',
        self::IDENTIFIER_PCLOUD              => 'pCloud',
    ];

    protected $storages = [];








    public function __construct()
    {
        $this->storages = [
            [
                'id'           => self::IDENTIFIER_GOOGLE_DRIVE,
                'cli'          => self::IDENTIFIER_GOOGLE_DRIVE,
                'name'         => 'Google Drive',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(GoogleDriveAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_GOOGLE_DRIVE),
            ],
            [
                'id'           => self::IDENTIFIER_AMAZON_S3,
                'cli'          => self::IDENTIFIER_AMAZON_S3,
                'name'         => 'Amazon S3',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(AmazonS3Auth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_AMAZON_S3),
            ],
            [
                'id'           => self::IDENTIFIER_DROPBOX,
                'cli'          => self::IDENTIFIER_DROPBOX,
                'name'         => 'Dropbox',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(DropboxAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_DROPBOX),
            ],
            [
                'id'           => self::IDENTIFIER_ONE_DRIVE,
                'cli'          => self::IDENTIFIER_ONE_DRIVE,
                'name'         => 'Microsoft OneDrive',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(OneDriveAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_ONE_DRIVE),
            ],
            [
                'id'           => self::IDENTIFIER_PCLOUD,
                'cli'          => self::IDENTIFIER_PCLOUD,
                'name'         => 'pCloud',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(PCloudAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_PCLOUD),
            ],
            [
                'id'           => self::IDENTIFIER_SFTP,
                'cli'          => self::IDENTIFIER_SFTP,
                'name'         => 'FTP / SFTP',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(SftpAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_SFTP),
            ],
            [
                'id'           => self::IDENTIFIER_DIGITALOCEAN_SPACES,
                'cli'          => self::IDENTIFIER_DIGITALOCEAN_SPACES,
                'name'         => 'DigitalOcean Spaces',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(DOSAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_DIGITALOCEAN_SPACES),
            ],
            [
                'id'           => self::IDENTIFIER_WASABI_S3,
                'cli'          => self::IDENTIFIER_WASABI_S3,
                'name'         => 'Wasabi S3',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(WasabiAuth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_WASABI_S3),
            ],
            [
                'id'           => self::IDENTIFIER_GENERIC_S3,
                'cli'          => self::IDENTIFIER_GENERIC_S3,
                'name'         => 'Generic S3',
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(GenericS3Auth::class),
                'settingsPath' => $this->getStorageAdminPage(self::IDENTIFIER_GENERIC_S3),
            ],
        ];
    }









    public function getStorageIds($isEnabled = null)
    {
        return array_map(function ($storage) {
            return $storage['id'];
        }, $this->getStorages($isEnabled));
    }









    public function getStorages($isEnabled = null)
    {
        if ($isEnabled === null) {
            return $this->storages;
        }

        return array_filter($this->storages, function ($storage) use ($isEnabled) {
            return $storage['enabled'] === $isEnabled;
        });
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





    public function isActivated($class)
    {
        if (empty($class)) {
            return false;
        }

 
        $storage = WPStaging::make($class);
        return $storage->isAuthenticated();
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
}
