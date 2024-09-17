<?php

namespace WPStaging\Backup\Entity;

class ListableBackup
{
    /** @var string */
    public $backupName;

    /** @var int A timestamp of the date this backup was created */
    public $dateCreatedTimestamp;

    /** @var int A formatted $dateCreatedTimestamp */
    public $dateCreatedFormatted;

    /** @var int A timestamp of the date this backup was uploaded */
    public $dateUploadedTimestamp;

    /** @var int A formatted $dateUploadedTimestamp */
    public $dateUploadedFormatted;

    /** @var string */
    public $downloadUrl;

    /** @var string */
    public $relativePath;

    /** @var string The basename of the backup encrypted as md5 */
    public $md5BaseName;

    /** @var string */
    public $id;

    /** @var bool */
    public $isExportingDatabase = false;

    /** @var bool */
    public $isExportingMuPlugins = false;

    /** @var bool */
    public $isExportingOtherWpContentFiles = false;

    /** @var bool */
    public $isExportingOtherWpRootFiles = false;

    /** @var bool */
    public $isExportingPlugins = false;

    /** @var bool */
    public $isExportingThemes = false;

    /** @var bool */
    public $isExportingUploads = false;

    /** @var string */
    public $name;

    /** @var string */
    public $notes;

    /** @var int The size of this backup in bytes */
    public $size;

    /** @var string The type of this backup: single | multi*/
    public $type;

    /** @var string The subsite install type of this backup */
    public $subsiteType;

    /** @var string The WP STAGING version this backup was generated on */
    public $generatedOnWPStagingVersion;

    /** @var string The backup structure version this backup was generated on */
    public $generatedOnBackupVersion;

    /** @var bool Whether this backup was automatically generated. (Eg: pushing staging into production) */
    public $automatedBackup = false;

    /** @var bool Whether this listable refers to a legacy .SQL file backup */
    public $isLegacy = false;

    /** @var bool Whether this backup is corrupt i.e. metadata not hydratable */
    public $isCorrupt = false;

    /** @var bool Whether this backup is a multipart */
    public $isMultipartBackup = false;

    /** @var bool */
    public $isValidMultipartBackup = false;

    /** @var bool */
    public $isValidFileIndex = false;

    /** @var string  */
    public $errorMessage = '';

    /** @var array */
    public $validationIssues = [];

    /** @var array */
    public $existingBackupParts = [];

    /** @var bool */
    public $createdOnPro = false;

    /** @var bool */
    public $isUnsupported = false;

    /** @var string The storage provider name of this backup */
    public $storageProviderName;

    /** @var string */
    public $error;

    /** @var array */
    public $indexPartSize = [];

    /** @var bool */
    public $isZlibCompressed = false;

    /** @var bool */
    public $isContaining2GBFile = false;

    /**
     * @return string
     */
    public function getBackupType(): string
    {
        if ($this->type === BackupMetadata::BACKUP_TYPE_SINGLE) {
            return esc_html__('Single Site', 'wp-staging');
        }

        if ($this->type === BackupMetadata::BACKUP_TYPE_MULTISITE) {
            return esc_html__('Entire Network', 'wp-staging');
        }

        if ($this->type === BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE) {
            return esc_html__('Network Subsite', 'wp-staging');
        }

        if ($this->type === BackupMetadata::BACKUP_TYPE_MAIN_SITE) {
            return esc_html__('Main Network Site', 'wp-staging');
        }

        return esc_html__('Unknown Backup Type', 'wp-staging');
    }
}
