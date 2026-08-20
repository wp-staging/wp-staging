<?php

namespace WPStaging\Backup\Entity;















class ListableBackup
{
 
    public $backupName;

 
    public $dateCreatedTimestamp;

 
    public $dateCreatedFormatted;

 
    public $dateUploadedTimestamp;

 
    public $dateUploadedFormatted;

 
    public $downloadUrl;

 
    public $relativePath;

 
    public $md5BaseName;

 
    public $id;

 
    public $isExportingDatabase = false;

 
    public $isExportingMuPlugins = false;

 
    public $isExportingOtherWpContentFiles = false;

 
    public $isExportingOtherWpRootFiles = false;

 
    public $isExportingPlugins = false;

 
    public $isExportingThemes = false;

 
    public $isExportingUploads = false;

 
    public $name;

 
    public $notes;

 
    public $size;

 
    public $type;

 
    public $subsiteType;

 
    public $networkSitesCount = 0;

 
    public $generatedOnWPStagingVersion;

 
    public $generatedOnBackupVersion;

 
    public $automatedBackup = false;

 
    public $scheduleRecurrence;

 
    public $isLegacy = false;

 
    public $isCorrupt = false;

 
    public $isMultipartBackup = false;

 
    public $isValidMultipartBackup = false;

 
    public $isValidFileIndex = false;

 
    public $errorMessage = '';

 
    public $validationIssues = [];

 
    public $existingBackupParts = [];

 
    public $createdOnPro = false;

 
    public $isUnsupported = false;

 
    public $storageProviderName;

 
    public $error;

 
    public $indexPartSize = [];

 
    public $isZlibCompressed = false;

 
    public $isContaining2GBFile = false;

 
    public $isUnsignedBackup = false;




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




    public function getNetworkSitesCountLabel(): string
    {
        if ($this->type !== BackupMetadata::BACKUP_TYPE_MULTISITE || $this->networkSitesCount < 1) {
            return '';
        }

        return sprintf(
            _n('%d Site', '%d Sites', $this->networkSitesCount, 'wp-staging'),
            $this->networkSitesCount
        );
    }
}
