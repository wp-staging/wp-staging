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

/**
 * Registry of remote-storage providers (Google Drive, Amazon S3, Dropbox, etc.)
 * available to the plugin for backup upload targets.
 *
 * Owns the canonical list of storage definitions (id, display name, auth class,
 * settings URL) and the legacy-to-current identifier mapping used to migrate
 * old option names to the new hyphenated format.
 */
class Providers
{
    use StorageIdNormalizerTrait;

    /** @var string */
    const IDENTIFIER_GOOGLE_DRIVE = 'google-drive';

    /** @var string */
    const IDENTIFIER_AMAZON_S3 = 'amazon-s3';

    /** @var string */
    const IDENTIFIER_DROPBOX = 'dropbox';

    /** @var string */
    const IDENTIFIER_ONE_DRIVE = 'one-drive';

    /** @var string */
    const IDENTIFIER_PCLOUD = 'pcloud';

    /** @var string */
    const IDENTIFIER_SFTP = 'sftp';

    /** @var string */
    const IDENTIFIER_DIGITALOCEAN_SPACES = 'digitalocean-spaces';

    /** @var string */
    const IDENTIFIER_WASABI_S3 = 'wasabi-s3';

    /** @var string */
    const IDENTIFIER_GENERIC_S3 = 'generic-s3';

    /**
     * Map of legacy storage IDs to new hyphenated format for backward compatibility.
     * Includes legacy storage IDs (camelCase) and legacy identifiers (lowercase).
     */
    const LEGACY_ID_MAP = [
        'googleDrive' => self::IDENTIFIER_GOOGLE_DRIVE,
        'amazonS3'    => self::IDENTIFIER_AMAZON_S3,
        'googledrive' => self::IDENTIFIER_GOOGLE_DRIVE,
        'amazons3'    => self::IDENTIFIER_AMAZON_S3,
    ];

    /**
     * Map of new hyphenated storage IDs to legacy lowercase identifiers.
     * Used for backward compatibility with option names (e.g. wpstg_googledrive, wpstg_amazons3).
     */
    const REVERSE_LEGACY_ID_MAP = [
        self::IDENTIFIER_GOOGLE_DRIVE => 'googledrive',
        self::IDENTIFIER_AMAZON_S3    => 'amazons3',
    ];

    /** Maps hyphenated identifiers to their legacy wpstg_* option names for backward compatibility. */
    const LEGACY_OPTION_MAP = [
        self::IDENTIFIER_GOOGLE_DRIVE => 'wpstg_googledrive',
        self::IDENTIFIER_AMAZON_S3    => 'wpstg_amazons3',
    ];

    /** Maps hyphenated identifiers to legacy camelCase property names stored in wpstg_tmp_data. */
    const LEGACY_PROPERTY_MAP = [
        self::IDENTIFIER_GOOGLE_DRIVE        => 'googleDrive',
        self::IDENTIFIER_AMAZON_S3           => 'amazonS3',
        self::IDENTIFIER_DIGITALOCEAN_SPACES => 'digitalOceanSpaces',
        self::IDENTIFIER_WASABI_S3           => 'wasabiS3',
        self::IDENTIFIER_GENERIC_S3          => 'genericS3',
        self::IDENTIFIER_ONE_DRIVE           => 'oneDrive',
        self::IDENTIFIER_PCLOUD              => 'pCloud',
    ];

    /** Maps hyphenated identifiers to their display names, used for logging. */
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

    /**
     * Build the static storage-provider registry used across the plugin.
     *
     * Each entry exposes the storage id, CLI slug, display name, an "enabled"
     * flag, the auth class (filtered so Free builds never reference Pro-only
     * auth implementations), and the admin settings page URL.
     */
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

    /**
     * Rename each remote-storage wp_options entry from its legacy camelCase or
     * lowercase key to the current hyphenated key (e.g. wpstg_googleDrive and
     * wpstg_googledrive -> wpstg_google-drive, wpstg_amazonS3 and wpstg_amazons3
     * -> wpstg_amazon-s3).
     *
     * Idempotent and safe to re-run:
     * - Skips any storage whose new-format option already exists (even if the
     *   stored value is empty, e.g. after the user revoked credentials) so we
     *   never resurrect old credentials on top of an intentionally cleared one.
     * - Tracks already-handled new ids in a local map so multi-entry legacy
     *   mappings (camelCase + lowercase pointing at the same new id) only
     *   migrate the first non-empty legacy option found.
     * - Uses autoload = false when writing the migrated option.
     *
     * Called once per install from the Upgrade dispatchers, gated by the
     * `remote_storage_option_names_migrated` feature flag in UpgradeFlags.
     *
     * @return void
     */
    public function migrateRemoteStorageOptions()
    {
        $migrated = [];
        foreach (self::LEGACY_ID_MAP as $legacyId => $newId) {
            if (isset($migrated[$newId])) {
                continue;
            }

            $newOptionName = 'wpstg_' . $newId;

            // Check if the new option already exists (even if empty, e.g. after revoking credentials).
            // Only migrate when the option is truly missing, to avoid resurrecting old credentials.
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
