<?php

namespace WPStaging\Backup\Entity;

use WPStaging\Backup\BackupHeader;
use WPStaging\Backup\Dto\Traits\IsExportingTrait;
use WPStaging\Backup\Dto\Traits\WithPluginsThemesMuPluginsTrait;
use WPStaging\Backup\Service\BackupMetadataReader;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Dto\Traits\DateCreatedTrait;
use WPStaging\Framework\Traits\HydrateTrait;

/**
 * Class AbstractBackupMetadata
 *
 * This is a OOP representation of the metadata that is
 * stored in a .wpstg backup file, such as date of creation,
 * whether it was created on single/multi sites, etc.
 *
 * @note Bump BackupHeader::BACKUP_VERSION whenever we make changes in backup structure
 *
 * @package WPStaging\Backup\Entity
 */
abstract class AbstractBackupMetadata implements \JsonSerializable
{
    use HydrateTrait {
        hydrate as traitHydrate;
    }

    use IsExportingTrait;
    use DateCreatedTrait;
    use WithPluginsThemesMuPluginsTrait;

    /**
     * Filter to detect the file format of the backup
     * @var string
     */
    const FILTER_BACKUP_FORMAT_V1 = 'wpstg.backup.format_v1';

    /**
     * Backup created on single site
     * @var string
     */
    const BACKUP_TYPE_SINGLE = 'single';

    /**
     * Full network backup created on multisite
     * @var string
     */
    const BACKUP_TYPE_MULTISITE = 'multi';

    /**
     * Single site backup created on non-main network subsite
     * @var string
     */
    const BACKUP_TYPE_NETWORK_SUBSITE = 'network-subsite';

    /**
     * Single site backup created on main network site
     * @var string
     */
    const BACKUP_TYPE_MAIN_SITE = 'main-network-site';

    /** @var string */
    private $id;

    /** @var int */
    private $headerStart;

    /** @var int */
    private $headerEnd;

    /** @var string */
    private $backupVersion = '';

    /** @var string */
    private $wpstgVersion = '';

    /** @var int */
    private $totalFiles;

    /** @var int */
    private $totalDirectories;

    /** @var string */
    private $siteUrl;

    /** @var string */
    private $homeUrl;

    /** @var string */
    private $absPath;

    /** @var string */
    private $prefix;

    /** @var string */
    private $backupType = '';

    /** @var string */
    private $name;

    /** @var string */
    private $note;

    /** @var bool If true, this backup was generated automatically, eg: When pushing a Staging site into Production. */
    private $isAutomatedBackup = false;

    /** @var string A path to where to extract a .sql file for this backup */
    private $databaseFile;

    /** @var int If this backup was uploaded from the user computer to the server, this value will hold the timestamp of that event. */
    private $uploadedOn;

    /** @var int The character length of the of the table with the largest name, unprefixed. */
    private $maxTableLength;

    /** @var int The size of the database included in this backup. */
    private $databaseFileSize;

    /** @var string The PHP version from which this backup was generated. */
    private $phpVersion;

    /** @var string The WP version from which this backup was generated. */
    private $wpVersion;

    /** @var string The WP DB version from which this backup was generated. */
    private $wpDbVersion;

    /** @var string The original database collation. */
    private $dbCollate;

    /** @var string The original database charset. */
    private $dbCharset;

    /** @var string The MySQL/MariaDB version from which this backup was generated. */
    private $sqlServerVersion;

    /** @var int|string The backup file size in bytes. Default is empty string. */
    private $backupSize = '';

    /** @var int The blog ID for this metadata. */
    private $blogId;

    /** @var int The network ID for this metadata. */
    private $networkId;

    /** @var array The network admins, only used in network site backups */
    private $networkAdmins;

    /** @var string The uploads path */
    private $uploadsPath;

    /** @var string The uploads URL */
    private $uploadsUrl;

    /** @var bool Whether PHP short_open_tags PHP directive is enabled. */
    private $phpShortOpenTags;

    /** @var bool Whether WP Bakery / Visual Composer installed and active. */
    private $wpBakeryActive;

    /** @var bool Whether Jetpack plugin installed and active. */
    private $isJetpackActive;

    /** @var bool Whether the backup created on wordpress.com. */
    private $isCreatedOnWordPressCom;

    /** @var string If this backup was created automatically as part of a schedule, this will hold the schedule ID. */
    private $scheduleId;

    /** @var array|null Sites backup during multisite backups. */
    private $sites;

    /** @var bool Whether the backup was created on multisite subdomain install. */
    private $subdomainInstall;

    /** @var bool Whether the backup was created on pro version. */
    private $createdOnPro;

    /** @var array|null Non Wp Tables */
    private $nonWpTables;

    /** @var string */
    private $logFile = '';

    /** @var MultipartMetadata|array|null */
    private $multipartMetadata = null;

    /** @var array */
    private $indexPartSize = [];

    /** @var bool */
    private $isZlibCompressed = false;

    /** @var int If compressed, how many chunks does the backup has. */
    private $totalChunks = 0;

    /**
     * Backup is created on which hosting type flywheel, wp.com or other
     * @var string
     */
    private $hostingType;

    /** @var bool */
    private $isContaining2GBFile = false;

    /** @var string */
    private $phpArchitecture;

    /** @var string */
    private $osArchitecture;

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<array<array>>
     */
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

    /**
     * @param array $data
     * @return BackupMetadata
     */
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

        /**
         * before hydrate set the backup version to empty,
         * to avoid populating it with latest backup version where backupVersion field was not available
         */
        $this->setBackupVersion('');

        $this->traitHydrate($data);

        return $this; // @phpstan-ignore-line
    }

    /**
     * @throws \RuntimeException
     * @return BackupMetadata
     */
    public function hydrateByFile(FileObject $file): BackupMetadata
    {
        $reader = new BackupMetadataReader($file);

        $backupMetadataArray = $reader->readBackupMetadata();

        return (new static())->hydrate($backupMetadataArray); // @phpstan-ignore-line
    }

    /**
     * @throws \RuntimeException
     * @return BackupMetadata
     */
    public function hydrateByFilePath($filePath): BackupMetadata
    {
        return $this->hydrateByFile(new FileObject($filePath));
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     * @return void
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * Returns the header (index) of a given backup file.
     *
     * @param string $backupPath The path to the backup file.
     * @return false|string
     */
    public function getHeader(string $backupPath)
    {
        if (!isset($this->headerStart)) {
            return '';
        }

        $backupFile = new FileObject($backupPath);
        $backupFile->fseek($this->headerStart);
        return $backupFile->fread($this->headerEnd - $this->headerStart);
    }

    /**
     * @return int
     */
    public function getHeaderStart()
    {
        return $this->headerStart;
    }

    /**
     * @param int|null $headerStart
     * @return void
     */
    public function setHeaderStart($headerStart)
    {
        $this->headerStart = $headerStart;
    }

    /**
     * @return int
     */
    public function getHeaderEnd()
    {
        return $this->headerEnd;
    }

    /**
     * @param int|null $headerEnd
     * @return void
     */
    public function setHeaderEnd($headerEnd)
    {
        $this->headerEnd = $headerEnd;
    }

    /**
     * @return string
     */
    public function getWpstgVersion(): string
    {
        return $this->wpstgVersion;
    }

    /**
     * @param string $wpstgVersion
     * @return void
     */
    public function setWpstgVersion(string $wpstgVersion)
    {
        $this->wpstgVersion = $wpstgVersion;
    }

    /**
     * @deprecated PRO 5.0.4
     * @deprecated FREE 3.0.4
     * @param string $version
     * @return void
     */
    public function setVersion(string $version)
    {
        $this->setWpstgVersion($version);
    }

    /**
     * @return string
     */
    public function getBackupVersion(): string
    {
        return $this->backupVersion;
    }

    /**
     * @param string $backupVersion
     * @return void
     */
    public function setBackupVersion(string $backupVersion)
    {
        $this->backupVersion = $backupVersion;
    }

    /**
     * @return int
     */
    public function getTotalFiles()
    {
        return $this->totalFiles;
    }

    /**
     * @param int|null $totalFiles
     * @return void
     */
    public function setTotalFiles($totalFiles)
    {
        $this->totalFiles = $totalFiles;
    }

    /**
     * @return int
     */
    public function getTotalDirectories()
    {
        return $this->totalDirectories;
    }

    /**
     * @param int|null $totalDirectories
     * @return void
     */
    public function setTotalDirectories($totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }

    /**
     * @return string
     */
    public function getSiteUrl(): string
    {
        return $this->siteUrl;
    }

    /**
     * @param string $siteUrl
     * @return void
     *
     * @throws RuntimeException
     */
    public function setSiteUrl(string $siteUrl)
    {
        // siteurl is always untrail-slashed, @see wp-includes/option.php:162
        $siteUrl = rtrim($siteUrl, '/');

        // mimic WordPress rules, see wp-includes/formatting.php:4763
        if (!preg_match('#http(s?)://(.+)#i', $siteUrl)) {
            throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
        }

        if (!parse_url($siteUrl, PHP_URL_HOST)) {
            throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
        }

        $this->siteUrl = $siteUrl;
    }

    /**
     * @return string
     */
    public function getHomeUrl(): string
    {
        return $this->homeUrl;
    }

    /**
     * @param string $homeUrl
     * @return void
     *
     * @throws RuntimeException
     */
    public function setHomeUrl(string $homeUrl)
    {
        // homeurl is always untrail-slashed, @see wp-includes/option.php:162
        $homeUrl = rtrim($homeUrl, '/');

        // mimic WordPress rules, see wp-includes/formatting.php:4763
        if (!preg_match('#http(s?)://(.+)#i', $homeUrl)) {
            throw new \RuntimeException('Please check the Site URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
        }

        if (!parse_url($homeUrl, PHP_URL_HOST)) {
            throw new \RuntimeException('Please check the Home URL option of this WordPress installation. Contact WP STAGING support if you need assistance.');
        }

        $this->homeUrl = $homeUrl;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string|null $prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $singleOrMulti
     * @return void
     * @deprecated since 5.2.0, Use setBackupType instead,
     *             kept for backward compatibility i.e. to support old backups which have singleOrMulti field in metadata and convert it properly to backup.
     */
    public function setSingleOrMulti(string $singleOrMulti)
    {
        $this->setBackupType($singleOrMulti);
    }

    /**
     * @return string
     */
    public function getBackupType(): string
    {
        return $this->backupType;
    }

    /**
     * @param string $backupType
     * @return void
     */
    public function setBackupType(string $backupType)
    {
        $this->backupType = $backupType;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     * @return void
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * @return bool
     */
    public function getIsAutomatedBackup(): bool
    {
        return $this->isAutomatedBackup;
    }

    /**
     * @param bool $isAutomatedBackup
     * @return void
     */
    public function setIsAutomatedBackup(bool $isAutomatedBackup)
    {
        $this->isAutomatedBackup = $isAutomatedBackup;
    }

    /**
     * @return string
     */
    public function getDatabaseFile()
    {
        return $this->databaseFile;
    }

    /**
     * @param string|null $databaseFile
     * @return void
     */
    public function setDatabaseFile($databaseFile)
    {
        $this->databaseFile = $databaseFile;
    }

    /**
     * @return int
     */
    public function getUploadedOn(): int
    {
        return $this->uploadedOn;
    }

    /**
     * @param int $uploadedOn
     * @return void
     */
    public function setUploadedOn($uploadedOn)
    {
        $this->uploadedOn = $uploadedOn;
    }

    /**
     * @return int|null
     */
    public function getMaxTableLength()
    {
        return $this->maxTableLength;
    }

    /**
     * @param int|null $maxTableLength
     * @return void
     */
    public function setMaxTableLength($maxTableLength)
    {
        $this->maxTableLength = $maxTableLength;
    }

    /**
     * @return int|null
     */
    public function getDatabaseFileSize()
    {
        return $this->databaseFileSize;
    }

    /**
     * @param int|null $databaseFileSize
     * @return void
     */
    public function setDatabaseFileSize($databaseFileSize)
    {
        $this->databaseFileSize = $databaseFileSize;
    }

    /**
     * @return string
     */
    public function getPhpVersion(): string
    {
        return (string)$this->phpVersion;
    }

    /**
     * @param string $phpVersion
     * @return void
     */
    public function setPhpVersion(string $phpVersion)
    {
        $this->phpVersion = (string)$phpVersion;
    }

    /**
     * @return string
     */
    public function getWpVersion(): string
    {
        return (string)$this->wpVersion;
    }

    /**
     * @param string $wpVersion
     * @return void
     */
    public function setWpVersion(string $wpVersion)
    {
        $this->wpVersion = (string)$wpVersion;
    }

    /**
     * @return string
     */
    public function getWpDbVersion(): string
    {
        return (string)$this->wpDbVersion;
    }

    /**
     * @param string $wpDbVersion
     * @return void
     */
    public function setWpDbVersion(string $wpDbVersion)
    {
        $this->wpDbVersion = (string)$wpDbVersion;
    }

    /**
     * @return string
     */
    public function getDbCollate(): string
    {
        return (string)$this->dbCollate;
    }

    /**
     * @param string $dbCollate
     * @return void
     */
    public function setDbCollate(string $dbCollate)
    {
        $this->dbCollate = (string)$dbCollate;
    }

    /**
     * @return string
     */
    public function getSqlServerVersion(): string
    {
        return (string)$this->sqlServerVersion;
    }

    /**
     * @param string $sqlServerVersion
     * @return void
     */
    public function setSqlServerVersion(string $sqlServerVersion)
    {
        $this->sqlServerVersion = (string)$sqlServerVersion;
    }

    /**
     * @return string
     */
    public function getDbCharset(): string
    {
        return (string)$this->dbCharset;
    }

    /**
     * @param string $dbCharset
     * @return void
     */
    public function setDbCharset(string $dbCharset)
    {
        $this->dbCharset = (string)$dbCharset;
    }

    /**
     * @return int
     */
    public function getBackupSize(): int
    {
        return (int)$this->backupSize;
    }

    /**
     * @param int $backupSize
     * @return void
     */
    public function setBackupSize($backupSize)
    {
        $this->backupSize = (int)$backupSize;
    }

    /**
     * @return string
     */
    public function getAbsPath(): string
    {
        return $this->absPath;
    }

    /**
     * @param string $absPath
     * @return void
     */
    public function setAbsPath(string $absPath)
    {
        $this->absPath = $absPath;
    }

    /**
     * @return int
     */
    public function getBlogId(): int
    {
        return $this->blogId;
    }

    /**
     * @param int $blogId
     * @return void
     */
    public function setBlogId(int $blogId)
    {
        $this->blogId = $blogId;
    }

    /**
     * @return string
     */
    public function getUploadsPath(): string
    {
        return $this->uploadsPath;
    }

    /**
     * @param string $uploadsPath
     * @return void
     */
    public function setUploadsPath(string $uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }

    /**
     * @return string
     */
    public function getUploadsUrl(): string
    {
        return $this->uploadsUrl;
    }

    /**
     * @param string $uploadsUrl
     * @return void
     */
    public function setUploadsUrl(string $uploadsUrl)
    {
        $this->uploadsUrl = $uploadsUrl;
    }

    /**
     * @return int
     */
    public function getNetworkId(): int
    {
        return $this->networkId;
    }

    /**
     * @param int $networkId
     * @return void
     */
    public function setNetworkId(int $networkId)
    {
        $this->networkId = $networkId;
    }

    /**
     * @return array
     */
    public function getNetworkAdmins(): array
    {
        if (!is_array($this->networkAdmins)) {
            $this->networkAdmins = [];
        }

        return $this->networkAdmins;
    }

    /**
     * Can be null for old backups
     * @param array|null $networkAdmins
     * @return void
     */
    public function setNetworkAdmins($networkAdmins)
    {
        $this->networkAdmins = $networkAdmins;
    }

    /**
     * @return bool Whether PHP can execute short open tags. Null if undefined.
     */
    public function getPhpShortOpenTags(): bool
    {
        return $this->phpShortOpenTags;
    }

    /**
     * @param bool $phpShortOpenTags
     * @return void
     */
    public function setPhpShortOpenTags(bool $phpShortOpenTags)
    {
        $this->phpShortOpenTags = $phpShortOpenTags;
    }

    /**
     * @return bool
     */
    public function getWpBakeryActive(): bool
    {
        return $this->wpBakeryActive;
    }

    /**
     * @param bool $wpBakeryActive
     * @return void
     */
    public function setWpBakeryActive(bool $wpBakeryActive)
    {
        $this->wpBakeryActive = $wpBakeryActive;
    }

    /**
     * @return bool
     */
    public function getIsJetpackActive(): bool
    {
        return $this->isJetpackActive ?? false;
    }

    /**
     * @param bool|null $isJetpackActive
     * @return void
     */
    public function setIsJetpackActive($isJetpackActive)
    {
        $this->isJetpackActive = $isJetpackActive;
    }

    /**
     * @return bool
     */
    public function getIsCreatedOnWordPressCom(): bool
    {
        return $this->isCreatedOnWordPressCom ?? false;
    }

    /**
     * @param bool|null $isCreatedOnWordPressCom
     * @return void
     */
    public function setIsCreatedOnWordPressCom($isCreatedOnWordPressCom)
    {
        $this->isCreatedOnWordPressCom = $isCreatedOnWordPressCom;
    }

    /**
     * @return string|null
     */
    public function getScheduleId()
    {
        return $this->scheduleId;
    }

    /**
     * @param string|null $scheduleId
     * @return void
     */
    public function setScheduleId($scheduleId)
    {
        $this->scheduleId = $scheduleId;
    }

    /**
     * @return array|null.
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * @param array|null $sites
     * @return void
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    /**
     * @return bool
     */
    public function getSubdomainInstall(): bool
    {
        return $this->subdomainInstall;
    }

    /**
     * @param bool $subdomainInstall
     * @return void
     */
    public function setSubdomainInstall(bool $subdomainInstall)
    {
        $this->subdomainInstall = $subdomainInstall;
    }

    /**
     * @return bool
     */
    public function getCreatedOnPro(): bool
    {
        // Backward compatbility for PRO backups
        if (!isset($this->createdOnPro) || is_null($this->createdOnPro)) {
            $this->createdOnPro = true;
        }

        return $this->createdOnPro;
    }

    /**
     * @param bool $createdOnPro
     * @return void
     */
    public function setCreatedOnPro($createdOnPro)
    {
        $this->createdOnPro = $createdOnPro;
    }

    /**
     * @return MultipartMetadata|null
     */
    public function getMultipartMetadata()
    {
        // Early bail if single file backup
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

    /**
     * @param MultipartMetadata|array|null $multipartMetadata
     * @return void
     */
    public function setMultipartMetadata($multipartMetadata)
    {
        $this->multipartMetadata = $multipartMetadata;
    }

    /** @return bool */
    public function getIsMultipartBackup(): bool
    {
        return !empty($this->multipartMetadata);
    }

    /**
     * @return array|null
     */
    public function getNonWpTables()
    {
        return $this->nonWpTables;
    }

    /**
     * @param array|null $tables
     * @return void
     */
    public function setNonWpTables($tables)
    {
        $this->nonWpTables = $tables;
    }

    /**
     * @param string $fileName
     * @return void
     */
    public function setLogFile(string $fileName)
    {
        $this->logFile = $fileName;
    }

    /**
     * @param array $indexPartSize
     * @return void
     */
    public function setIndexPartSize(array $indexPartSize)
    {
        $this->indexPartSize = $indexPartSize;
    }

    /**
     * Returns the size of each part of the backup.
     *
     * @return array{
     *     sqlSize: int,
     *     wpcontentSize: int,
     *     pluginsSize: int,
     *     mupluginsSize: int,
     *     themesSize: int,
     *     uploadsSize: int
     * }
     */
    public function getIndexPartSize(): array
    {
        return $this->indexPartSize;
    }

    /**
     * @return bool|mixed|null
     */
    public function getIsZlibCompressed()
    {
        return $this->isZlibCompressed;
    }

    /**
     * @param bool|mixed|null $isZlibCompressed
     */
    public function setIsZlibCompressed($isZlibCompressed)
    {
        $this->isZlibCompressed = $isZlibCompressed;
    }

    /**
     * @return int
     */
    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }

    /**
     * @param int $totalChunks
     * @return void
     */
    public function setTotalChunks(int $totalChunks)
    {
        $this->totalChunks = $totalChunks;
    }

    /**
     * @return string
     */
    public function getHostingType(): string
    {
        if (empty($this->hostingType)) {
            /**
             * Can't use SiteInfo::OTHER_HOST const here because of standalone tool
             * @see SiteInfo::OTHER_HOST for value
             */
            $this->hostingType = 'other';
        }

        return $this->hostingType;
    }

    /**
     * @param string $hostingType
     * @return void
     */
    public function setHostingType(string $hostingType)
    {
        $this->hostingType = $hostingType;
    }

    /**
     * @return bool
     */
    public function getIsContaining2GBFile(): bool
    {
        return $this->isContaining2GBFile;
    }

    /**
     * @param bool|null $isContaining2GBFile
     * @return void
     */
    public function setIsContaining2GBFile($isContaining2GBFile)
    {
        $this->isContaining2GBFile = (bool)$isContaining2GBFile;
    }

    /**
     * @return string
     */
    public function getPhpArchitecture(): string
    {
        return $this->phpArchitecture;
    }

    /**
     * @param string $phpArchitecture
     * @return void
     */
    public function setPhpArchitecture(string $phpArchitecture)
    {
        $this->phpArchitecture = $phpArchitecture;
    }

    /**
     * @return string
     */
    public function getOsArchitecture(): string
    {
        return $this->osArchitecture;
    }

    /**
     * @param string $osArchitecture
     * @return void
     */
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
}
