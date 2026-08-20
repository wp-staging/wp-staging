<?php

namespace WPStaging\Backup\Entity;

use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Dto\Traits\IsExportingTrait;
use WPStaging\Backup\Dto\Traits\WithPluginsThemesMuPluginsTrait;
use WPStaging\Backup\Service\BackupMetadataReader;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Dto\Traits\DateCreatedTrait;
use WPStaging\Framework\Traits\HydrateTrait;












abstract class AbstractBackupMetadata implements \JsonSerializable
{
    use HydrateTrait {
        hydrate as traitHydrate;
    }

    use IsExportingTrait;
    use DateCreatedTrait;
    use WithPluginsThemesMuPluginsTrait;





    const FILTER_BACKUP_FORMAT_V1 = 'wpstg.backup.format_v1';





    const BACKUP_TYPE_SINGLE = 'single';





    const BACKUP_TYPE_MULTISITE = 'multi';





    const BACKUP_TYPE_NETWORK_SUBSITE = 'network-subsite';





    const BACKUP_TYPE_MAIN_SITE = 'main-network-site';

 
    private $id = '';

 
    private $headerStart;

 
    private $headerEnd;

 
    private $backupVersion = '';

 
    private $wpstgVersion = '';

 
    private $totalFiles;

 
    private $totalDirectories;

 
    private $siteUrl;

 
    private $homeUrl;

 
    private $absPath;

 
    private $prefix;

 
    private $backupType = '';

 
    private $name = '';

 
    private $note;

 
    private $isAutomatedBackup = false;

 
    private $isBeforeUpdateBackup = false;

 
    private $databaseFile;

 
    private $uploadedOn;

 
    private $maxTableLength;

 
    private $databaseFileSize;

 
    private $phpVersion = '';

 
    private $wpVersion = '';

 
    private $wpDbVersion = '';

 
    private $dbCollate = '';

 
    private $dbCharset = '';

 
    private $sqlServerVersion = '';

 
    private $backupSize = '';

 
    private $blogId;

 
    private $networkId;

 
    private $networkAdmins;

 
    private $uploadsPath;

 
    private $uploadsUrl;

 
    private $phpShortOpenTags;

 
    private $wpBakeryActive;

 
    private $isJetpackActive;

 
    private $isCreatedOnWordPressCom;

 
    private $scheduleId;

 
    private $scheduleRecurrence;

 
    private $sites;

 
    private $subdomainInstall;

 
    private $createdOnPro;

 
    private $nonWpTables;

 
    private $logFile = '';

 
    private $multipartMetadata = null;

 
    private $indexPartSize = [];

 
    private $isZlibCompressed = false;

 
    private $totalChunks = 0;





    private $hostingType;

 
    private $isContaining2GBFile = false;

 
    private $phpArchitecture;

 
    private $osArchitecture;

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }




    public function toArray(): array
    {
        $array = get_object_vars($this);

        return [
            'networks' => [
                $this->getNetworkId() => [
                    'blogs' => [
                        $this->getBlogId() => $array,
                    ],
                ],
            ],
        ];
    }





    public function hydrate(array $data = []): BackupMetadata
    {
        if (key($data) === 'networks') {
            if (array_key_exists($this->networkId, $data['networks'])) {
                $data = $data['networks'][$this->networkId];
            } else {
                $data = array_shift($data['networks']);
            }
        }

        if (key($data) === 'blogs') {
            if (array_key_exists($this->blogId, $data['blogs'])) {
                $data = $data['blogs'][$this->blogId];
            } else {
                $data = array_shift($data['blogs']);
            }
        }





        $this->setBackupVersion('');

        $this->traitHydrate($data);

        return $this; // @phpstan-ignore-line
    }





    public function hydrateByFile(FileObject $file): BackupMetadata
    {
        $reader = new BackupMetadataReader($file);

        $backupMetadataArray = $reader->readBackupMetadata();

        return (new static())->hydrate($backupMetadataArray); // @phpstan-ignore-line
    }





    public function hydrateByFilePath($filePath): BackupMetadata
    {
        return $this->hydrateByFile(new FileObject($filePath));
    }




    public function getId(): string
    {
        return $this->id;
    }





    public function setId(string $id)
    {
        $this->id = $id;
    }







    public function getHeader(string $backupPath)
    {
        if (!isset($this->headerStart)) {
            return '';
        }

        $backupFile = new FileObject($backupPath);
        $backupFile->fseek($this->headerStart);
        return $backupFile->fread($this->headerEnd - $this->headerStart);
    }




    public function getHeaderStart()
    {
        return $this->headerStart;
    }





    public function setHeaderStart($headerStart)
    {
        $this->headerStart = $headerStart;
    }




    public function getHeaderEnd()
    {
        return $this->headerEnd;
    }





    public function setHeaderEnd($headerEnd)
    {
        $this->headerEnd = $headerEnd;
    }




    public function getWpstgVersion(): string
    {
        return $this->wpstgVersion;
    }





    public function setWpstgVersion(string $wpstgVersion)
    {
        $this->wpstgVersion = $wpstgVersion;
    }







    public function setVersion(string $version)
    {
        $this->setWpstgVersion($version);
    }




    public function getBackupVersion(): string
    {
        return $this->backupVersion;
    }





    public function setBackupVersion(string $backupVersion)
    {
        $this->backupVersion = $backupVersion;
    }




    public function getTotalFiles()
    {
        return $this->totalFiles;
    }





    public function setTotalFiles($totalFiles)
    {
        $this->totalFiles = $totalFiles;
    }




    public function getTotalDirectories()
    {
        return $this->totalDirectories;
    }





    public function setTotalDirectories($totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }




    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }







    public function setSiteUrl(string $siteUrl)
    {
 
        $siteUrl = rtrim($siteUrl, '/');

 
        if (!preg_match('#http(s?)://(.+)#i', $siteUrl)) {
            throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
        }

        if (!parse_url($siteUrl, PHP_URL_HOST)) {
            throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
        }

        $this->siteUrl = $siteUrl;
    }




    public function getHomeUrl(): string
    {
        return $this->homeUrl;
    }







    public function setHomeUrl(string $homeUrl)
    {
 
        $homeUrl = rtrim($homeUrl, '/');

 
        if (!preg_match('#http(s?)://(.+)#i', $homeUrl)) {
            throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
        }

        if (!parse_url($homeUrl, PHP_URL_HOST)) {
            throw new \RuntimeException('Please check the Home URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
        }

        $this->homeUrl = $homeUrl;
    }




    public function getPrefix()
    {
        return $this->prefix;
    }





    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }







    public function setSingleOrMulti(string $singleOrMulti)
    {
        $this->setBackupType($singleOrMulti);
    }




    public function getBackupType(): string
    {
        return $this->backupType;
    }





    public function setBackupType(string $backupType)
    {
        $this->backupType = $backupType;
    }




    public function getName(): string
    {
        return $this->name;
    }





    public function setName(string $name)
    {
        $this->name = $name;
    }




    public function getNote()
    {
        return $this->note;
    }





    public function setNote($note)
    {
        $this->note = $note;
    }




    public function getIsAutomatedBackup(): bool
    {
        return $this->isAutomatedBackup;
    }





    public function setIsAutomatedBackup(bool $isAutomatedBackup)
    {
        $this->isAutomatedBackup = $isAutomatedBackup;
    }




    public function getIsBeforeUpdateBackup(): bool
    {
        return $this->isBeforeUpdateBackup;
    }





    public function setIsBeforeUpdateBackup(bool $isBeforeUpdateBackup)
    {
        $this->isBeforeUpdateBackup = $isBeforeUpdateBackup;
    }




    public function getDatabaseFile()
    {
        return $this->databaseFile;
    }





    public function setDatabaseFile($databaseFile)
    {
        $this->databaseFile = $databaseFile;
    }




    public function getUploadedOn(): int
    {
        return $this->uploadedOn;
    }





    public function setUploadedOn($uploadedOn)
    {
        $this->uploadedOn = $uploadedOn;
    }




    public function getMaxTableLength()
    {
        return $this->maxTableLength;
    }





    public function setMaxTableLength($maxTableLength)
    {
        $this->maxTableLength = $maxTableLength;
    }




    public function getDatabaseFileSize()
    {
        return $this->databaseFileSize;
    }





    public function setDatabaseFileSize($databaseFileSize)
    {
        $this->databaseFileSize = $databaseFileSize;
    }




    public function getPhpVersion(): string
    {
        return (string)$this->phpVersion;
    }





    public function setPhpVersion(string $phpVersion)
    {
        $this->phpVersion = (string)$phpVersion;
    }




    public function getWpVersion(): string
    {
        return (string)$this->wpVersion;
    }





    public function setWpVersion(string $wpVersion)
    {
        $this->wpVersion = (string)$wpVersion;
    }




    public function getWpDbVersion(): string
    {
        return (string)$this->wpDbVersion;
    }





    public function setWpDbVersion(string $wpDbVersion)
    {
        $this->wpDbVersion = (string)$wpDbVersion;
    }




    public function getDbCollate(): string
    {
        return (string)$this->dbCollate;
    }





    public function setDbCollate(string $dbCollate)
    {
        $this->dbCollate = (string)$dbCollate;
    }




    public function getSqlServerVersion(): string
    {
        return (string)$this->sqlServerVersion;
    }





    public function setSqlServerVersion(string $sqlServerVersion)
    {
        $this->sqlServerVersion = (string)$sqlServerVersion;
    }




    public function getDbCharset(): string
    {
        return (string)$this->dbCharset;
    }





    public function setDbCharset(string $dbCharset)
    {
        $this->dbCharset = (string)$dbCharset;
    }




    public function getBackupSize(): int
    {
        return (int)$this->backupSize;
    }





    public function setBackupSize($backupSize)
    {
        $this->backupSize = (int)$backupSize;
    }




    public function getAbsPath(): string
    {
        return $this->absPath;
    }





    public function setAbsPath(string $absPath)
    {
        $this->absPath = $absPath;
    }




    public function getBlogId(): int
    {
        return $this->blogId;
    }





    public function setBlogId(int $blogId)
    {
        $this->blogId = $blogId;
    }




    public function getUploadsPath(): string
    {
        return $this->uploadsPath;
    }





    public function setUploadsPath(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }




    public function getUploadsUrl(): string
    {
        return $this->uploadsUrl;
    }





    public function setUploadsUrl(string $uploadsUrl)
    {
        $this->uploadsUrl = $uploadsUrl;
    }




    public function getNetworkId(): int
    {
        return $this->networkId;
    }





    public function setNetworkId(int $networkId)
    {
        $this->networkId = $networkId;
    }




    public function getNetworkAdmins(): array
    {
        if (!is_array($this->networkAdmins)) {
            $this->networkAdmins = [];
        }

        return $this->networkAdmins;
    }






    public function setNetworkAdmins($networkAdmins)
    {
        $this->networkAdmins = $networkAdmins;
    }




    public function getPhpShortOpenTags(): bool
    {
        return $this->phpShortOpenTags;
    }





    public function setPhpShortOpenTags(bool $phpShortOpenTags)
    {
        $this->phpShortOpenTags = $phpShortOpenTags;
    }




    public function getWpBakeryActive(): bool
    {
        return $this->wpBakeryActive;
    }





    public function setWpBakeryActive(bool $wpBakeryActive)
    {
        $this->wpBakeryActive = $wpBakeryActive;
    }




    public function getIsJetpackActive(): bool
    {
        return $this->isJetpackActive ?? false;
    }





    public function setIsJetpackActive($isJetpackActive)
    {
        $this->isJetpackActive = $isJetpackActive;
    }




    public function getIsCreatedOnWordPressCom(): bool
    {
        return $this->isCreatedOnWordPressCom ?? false;
    }





    public function setIsCreatedOnWordPressCom($isCreatedOnWordPressCom)
    {
        $this->isCreatedOnWordPressCom = $isCreatedOnWordPressCom;
    }




    public function getScheduleId()
    {
        return $this->scheduleId;
    }





    public function setScheduleId($scheduleId)
    {
        $this->scheduleId = $scheduleId;
    }




    public function getScheduleRecurrence()
    {
        return $this->scheduleRecurrence;
    }





    public function setScheduleRecurrence($scheduleRecurrence)
    {
        $this->scheduleRecurrence = $scheduleRecurrence;
    }




    public function getSites()
    {
        return $this->sites;
    }





    public function setSites($sites)
    {
        $this->sites = $sites;
    }




    public function getSubdomainInstall(): bool
    {
        return $this->subdomainInstall;
    }





    public function setSubdomainInstall(bool $subdomainInstall)
    {
        $this->subdomainInstall = $subdomainInstall;
    }




    public function getCreatedOnPro(): bool
    {
 
        if (!isset($this->createdOnPro) || is_null($this->createdOnPro)) {
            $this->createdOnPro = true;
        }

        return $this->createdOnPro;
    }





    public function setCreatedOnPro($createdOnPro)
    {
        $this->createdOnPro = $createdOnPro;
    }




    public function getMultipartMetadata()
    {
 
        if (empty($this->multipartMetadata)) {
            return null;
        }

        if ($this->multipartMetadata instanceof MultipartMetadata) {
            return $this->multipartMetadata;
        }

        $metadata                = new MultipartMetadata();
        $this->multipartMetadata = $metadata->hydrate($this->multipartMetadata);

        return $this->multipartMetadata;
    }





    public function setMultipartMetadata($multipartMetadata)
    {
        $this->multipartMetadata = $multipartMetadata;
    }

 
    public function getIsMultipartBackup(): bool
    {
        return !empty($this->multipartMetadata);
    }




    public function getNonWpTables()
    {
        return $this->nonWpTables;
    }





    public function setNonWpTables($tables)
    {
        $this->nonWpTables = $tables;
    }





    public function setLogFile(string $fileName)
    {
        $this->logFile = $fileName;
    }





    public function setIndexPartSize(array $indexPartSize)
    {
        $this->indexPartSize = $indexPartSize;
    }













    public function getIndexPartSize(): array
    {
        return $this->indexPartSize;
    }




    public function getIsZlibCompressed()
    {
        return $this->isZlibCompressed;
    }




    public function setIsZlibCompressed($isZlibCompressed)
    {
        $this->isZlibCompressed = $isZlibCompressed;
    }




    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }





    public function setTotalChunks(int $totalChunks)
    {
        $this->totalChunks = $totalChunks;
    }




    public function getHostingType(): string
    {
        if (empty($this->hostingType)) {




            $this->hostingType = 'other';
        }

        return $this->hostingType;
    }





    public function setHostingType(string $hostingType)
    {
        $this->hostingType = $hostingType;
    }




    public function getIsContaining2GBFile(): bool
    {
        return $this->isContaining2GBFile;
    }





    public function setIsContaining2GBFile($isContaining2GBFile)
    {
        $this->isContaining2GBFile = (bool)$isContaining2GBFile;
    }




    public function getPhpArchitecture(): string
    {
        return $this->phpArchitecture;
    }





    public function setPhpArchitecture(string $phpArchitecture)
    {
        $this->phpArchitecture = $phpArchitecture;
    }




    public function getOsArchitecture(): string
    {
        return $this->osArchitecture;
    }





    public function setOsArchitecture(string $osArchitecture)
    {
        $this->osArchitecture = $osArchitecture;
    }

    public function getIsBackupFormatV1(): bool
    {
        return version_compare($this->getBackupVersion(), BackupHeader::MIN_BACKUP_VERSION, '<');
    }

    public function getIsMultisiteBackup(): bool
    {
        return $this->backupType !== self::BACKUP_TYPE_SINGLE;
    }




    public function revertBackupSizeToDefault()
    {
        $this->backupSize = '';
    }
}
