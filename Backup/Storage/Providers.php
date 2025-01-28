<?php

namespace WPStaging\Backup\Storage;

use WPStaging\Core\WPStaging;
use WPStaging\Pro\Backup\Storage\Storages\Amazon\S3 as AmazonS3Auth;
use WPStaging\Pro\Backup\Storage\Storages\DigitalOceanSpaces\Auth as DSOAuth;
use WPStaging\Pro\Backup\Storage\Storages\GenericS3\Auth as GenericS3Auth;
use WPStaging\Pro\Backup\Storage\Storages\GoogleDrive\Auth as GoogleDriveAuth;
use WPStaging\Pro\Backup\Storage\Storages\Dropbox\Auth as DropboxAuth;
use WPStaging\Pro\Backup\Storage\Storages\OneDrive\Auth as OneDriveAuth;
use WPStaging\Pro\Backup\Storage\Storages\SFTP\Auth as SftpAuth;
use WPStaging\Pro\Backup\Storage\Storages\Wasabi\Auth as WasabiAuth;

use function WPStaging\functions\debug_log;

class Providers
{
    /** @var array
     *
     * @example  [
     * 'storageIdentifier' => 'storageId'
     * ]
    */
    const STORAGE_IDS_BY_IDENTIFIERS = [
        'googledrive'         => 'googleDrive',
        'amazons3'            => 'amazonS3',
        'dropbox'             => 'dropbox',
        'one-drive'           => 'one-drive',
        'sftp'                => 'sftp',
        'digitalocean-spaces' => 'digitalocean-spaces',
        'wasabi'              => 'wasabi-s3',
        'generic-s3'          => 'generic-s3',
    ];

    protected $storages = [];

    public function __construct()
    {
        $this->storages = [
            [
                'id'   => 'googleDrive',
                'cli'  => 'google-drive',
                'name' => esc_html__('Google Drive', 'wp-staging'),
                'enabled'   => true,
                'authClass' => $this->filterAuthClassForPro(GoogleDriveAuth::class),
                'settingsPath' => $this->getStorageAdminPage('googleDrive'),
            ],
            [
                'id'   => 'amazonS3',
                'cli'  => 'amazon-s3',
                'name' => esc_html__('Amazon S3', 'wp-staging'),
                'enabled'   => true,
                'authClass' => $this->filterAuthClassForPro(AmazonS3Auth::class),
                'settingsPath' => $this->getStorageAdminPage('amazonS3'),
            ],
            [
                'id'   => 'dropbox',
                'cli'  => 'dropbox',
                'name' => esc_html__('Dropbox', 'wp-staging'),
                'enabled'   => true,
                'authClass' => $this->filterAuthClassForPro(DropboxAuth::class),
                'settingsPath' => $this->getStorageAdminPage('dropbox'),
            ],
            [
                'id'           => 'one-drive',
                'cli'          => 'one-drive',
                'name'         => esc_html__('Microsoft OneDrive', 'wp-staging'),
                'enabled'      => true,
                'authClass'    => $this->filterAuthClassForPro(OneDriveAuth::class),
                'settingsPath' => $this->getStorageAdminPage('one-drive'),
            ],
            [
                'id'   => 'sftp',
                'cli'  => 'sftp',
                'name' => esc_html__('FTP / SFTP', 'wp-staging'),
                'enabled'   => true,
                'authClass' => $this->filterAuthClassForPro(SftpAuth::class),
                'settingsPath' => $this->getStorageAdminPage('sftp'),
            ],
            [
                'id'   => 'digitalocean-spaces',
                'cli'  => 'digitalocean-spaces',
                'name' => esc_html__('DigitalOcean Spaces', 'wp-staging'),
                'enabled'   => true,
                'authClass' => $this->filterAuthClassForPro(DSOAuth::class),
                'settingsPath' => $this->getStorageAdminPage('digitalocean-spaces'),
            ],
            [
                'id'   => 'wasabi-s3',
                'cli'  => 'wasabi-s3',
                'name' => esc_html__('Wasabi S3', 'wp-staging'),
                'enabled'   => true,
                'authClass' => $this->filterAuthClassForPro(WasabiAuth::class),
                'settingsPath' => $this->getStorageAdminPage('wasabi-s3'),
            ],
            [
                'id'   => 'generic-s3',
                'cli'  => 'generic-s3',
                'name' => esc_html__('Generic S3', 'wp-staging'),
                'enabled'   => true,
                'authClass' => $this->filterAuthClassForPro(GenericS3Auth::class),
                'settingsPath' => $this->getStorageAdminPage('generic-s3'),
            ],
        ];
    }

    /**
     * @param null|bool $isEnabled. Default null
     *                  Use null for all storages,
     *                  Use true for enabled storages,
     *                  Use false for disabled storages
     *
     * @return array
     */
    public function getStorageIds($isEnabled = null)
    {
        return array_map(function ($storage) {
            return $storage['id'];
        }, $this->getStorages($isEnabled));
    }

    /**
     * @param null|bool $isEnabled. Default null
     *                  Use null for all storages,
     *                  Use true for enabled storages,
     *                  Use false for disabled storages
     *
     * @return array
     */
    public function getStorages($isEnabled = null)
    {
        if ($isEnabled === null) {
            return $this->storages;
        }

        return array_filter($this->storages, function ($storage) use ($isEnabled) {
            return $storage['enabled'] === $isEnabled;
        });
    }

    /**
     * @param string $id
     * @param string $property
     * @param null|bool $isEnabled. Default null
     *                  Use null for all storages,
     *                  Use true for enabled storages,
     *                  Use false for disabled storages
     *
     * @return mixed
     */
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

    /**
     * @param string $class
     * @return bool
     */
    public function isActivated($class)
    {
        if (empty($class)) {
            return false;
        }

        /** @see WPStaging\Backup\Storage\AbstractStorage */
        $storage = WPStaging::make($class);
        return $storage->isAuthenticated();
    }

    /**
     * @param string $identifier
     *
     * @return string|false
     */
    public function getStorageByIdentifier(string $identifier)
    {
        if (!isset(self::STORAGE_IDS_BY_IDENTIFIERS[$identifier])) {
            debug_log('Failed to find storage id by identifier.');
            return false;
        }

        return self::STORAGE_IDS_BY_IDENTIFIERS[$identifier];
    }

    /**
     * @param string $id
     * @return string
     */
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
}
