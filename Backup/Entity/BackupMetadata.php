<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Backup\Entity;

use JsonSerializable;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Traits\HydrateTrait;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Utils\Times;
use WPStaging\Backup\Dto\Traits\DateCreatedTrait;
use WPStaging\Backup\Dto\Traits\IsExportingTrait;
use WPStaging\Backup\Dto\Traits\WithPluginsThemesMuPluginsTrait;

/**
 * Class BackupMetadata
 *
 * This is a OOP representation of the metadata that is
 * stored in a .wpstg backup file, such as date of creation,
 * whether it was created on single/multi sites, etc.
 *
 * @package WPStaging\Backup\Entity
 */
class BackupMetadata implements JsonSerializable
{
    use HydrateTrait {
        hydrate as traitHydrate;
    }

    use IsExportingTrait;
    use DateCreatedTrait;
    use WithPluginsThemesMuPluginsTrait;

    /**
     * Version of Backup Metadata
     * This need to be bump whenever we make changes in backup structure
     * @var string
     */
    const BACKUP_VERSION = '1.0.0';

    /** @var string */
    private $id;

    /** @var int */
    private $headerStart;

    /** @var int */
    private $headerEnd;

    /** @var string */
    private $backupVersion;

    /** @var string */
    private $wpstgVersion;

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

    /** @var bool */
    private $singleOrMulti;

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

    /** @var string The uploads path */
    private $uploadsPath;

    /** @var string The uploads URL */
    private $uploadsUrl;

    /** @var bool Whether PHP short_open_tags PHP directive is enabled. */
    private $phpShortOpenTags;

    /** @var bool Whether WP Bakery / Visual Composer installed and active. */
    private $wpBakeryActive;

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

    /**
     * BackupMetadata constructor.
     *
     * Sets reasonable defaults.
     */
    public function __construct()
    {
        $siteInfo = new SiteInfo();
        $time     = new Times();

        $this->setWpstgVersion(WPStaging::getVersion());
        $this->setBackupVersion(self::BACKUP_VERSION);
        $this->setSiteUrl(get_option('siteurl'));
        $this->setHomeUrl(get_option('home'));
        $this->setAbsPath(ABSPATH);
        $this->setBlogId(get_current_blog_id());
        $this->setNetworkId(get_current_network_id());
        $this->setDateCreated(time());
        $this->setDateCreatedTimezone($time->getSiteTimezoneString());
        $this->setSingleOrMulti(is_multisite() ? 'multi' : 'single');
        $this->setPhpShortOpenTags($siteInfo->isPhpShortTagsEnabled());

        $this->setWpBakeryActive($siteInfo->isWpBakeryActive());

        $uploadDir = wp_upload_dir(null, false, true);

        if (!is_array($uploadDir)) {
            return;
        }

        $this->setUploadsPath(array_key_exists('basedir', $uploadDir) ? $uploadDir['basedir'] : '');
        $this->setUploadsUrl(array_key_exists('baseurl', $uploadDir) ? $uploadDir['baseurl'] : '');

        $this->setSites(null);
        $this->setSubdomainInstall(is_multisite() && is_subdomain_install());
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array<array<array>>
     */
    public function toArray()
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
     * @return $this
     */
    public function hydrate(array $data = [])
    {
        if (key($data) === 'networks') {
            if (array_key_exists(get_current_network_id(), $data['networks'])) {
                $data = $data['networks'][get_current_network_id()];
            } else {
                $data = array_shift($data['networks']);
            }
        }

        if (key($data) === 'blogs') {
            if (array_key_exists(get_current_blog_id(), $data['blogs'])) {
                $data = $data['blogs'][get_current_blog_id()];
            } else {
                $data = array_shift($data['blogs']);
            }
        }

        $this->traitHydrate($data);

        return $this;
    }

    /**
     * @throws \RuntimeException
     */
    public function hydrateByFile(FileObject $file)
    {
        $backupMetadataArray = $file->readBackupMetadata();

        return (new self())->hydrate($backupMetadataArray);
    }

    /**
     * @throws \RuntimeException
     */
    public function hydrateByFilePath($filePath)
    {
        return $this->hydrateByFile(new FileObject($filePath));
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getHeaderStart()
    {
        return $this->headerStart;
    }

    /**
     * @param int $headerStart
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
     * @param int $headerEnd
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
     * @deprecated T.B.D
     * @param string $version
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
     * @param int $totalFiles
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
     * @param int $totalDirectories
     */
    public function setTotalDirectories($totalDirectories)
    {
        $this->totalDirectories = $totalDirectories;
    }

    /**
     * @return string
     */
    public function getSiteUrl()
    {
        return $this->siteUrl;
    }

    /**
     * @param string $siteUrl
     */
    public function setSiteUrl($siteUrl)
    {
        // siteurl is always untrail-slashed, @see wp-includes/option.php:162
        $siteUrl = untrailingslashit($siteUrl);

        // mimick WordPress rules, see wp-includes/formatting.php:4763
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
    public function getHomeUrl()
    {
        return $this->homeUrl;
    }

    /**
     * @param string $homeUrl
     */
    public function setHomeUrl($homeUrl)
    {
        // homeurl is always untrail-slashed, @see wp-includes/option.php:162
        $homeUrl = untrailingslashit($homeUrl);

        // mimick WordPress rules, see wp-includes/formatting.php:4763
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
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return bool
     */
    public function getSingleOrMulti()
    {
        return $this->singleOrMulti;
    }

    /**
     * @param bool $singleOrMulti
     */
    public function setSingleOrMulti($singleOrMulti)
    {
        $this->singleOrMulti = $singleOrMulti;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * @return bool
     */
    public function getIsAutomatedBackup()
    {
        return $this->isAutomatedBackup;
    }

    /**
     * @param bool $isAutomatedBackup
     */
    public function setIsAutomatedBackup($isAutomatedBackup)
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
     * @param $databaseFile
     * @return void
     */
    public function setDatabaseFile($databaseFile)
    {
        $this->databaseFile = $databaseFile;
    }

    /**
     * @return int
     */
    public function getUploadedOn()
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
     * @return int
     */
    public function getMaxTableLength()
    {
        return $this->maxTableLength;
    }

    /**
     * @param int $maxTableLength
     */
    public function setMaxTableLength($maxTableLength)
    {
        $this->maxTableLength = $maxTableLength;
    }

    /**
     * @return int
     */
    public function getDatabaseFileSize()
    {
        return $this->databaseFileSize;
    }

    /**
     * @param int $databaseFileSize
     */
    public function setDatabaseFileSize($databaseFileSize)
    {
        $this->databaseFileSize = $databaseFileSize;
    }

    /**
     * @return string
     */
    public function getPhpVersion()
    {
        return (string)$this->phpVersion;
    }

    /**
     * @param string $phpVersion
     */
    public function setPhpVersion($phpVersion)
    {
        $this->phpVersion = (string)$phpVersion;
    }

    /**
     * @return string
     */
    public function getWpVersion()
    {
        return (string)$this->wpVersion;
    }

    /**
     * @param string $wpVersion
     */
    public function setWpVersion($wpVersion)
    {
        $this->wpVersion = (string)$wpVersion;
    }

    /**
     * @return string
     */
    public function getWpDbVersion()
    {
        return (string)$this->wpDbVersion;
    }

    /**
     * @param string $wpDbVersion
     */
    public function setWpDbVersion($wpDbVersion)
    {
        $this->wpDbVersion = (string)$wpDbVersion;
    }

    /**
     * @return string
     */
    public function getDbCollate()
    {
        return (string)$this->dbCollate;
    }

    /**
     * @param string $dbCollate
     */
    public function setDbCollate($dbCollate)
    {
        $this->dbCollate = (string)$dbCollate;
    }

    /**
     * @return string
     */
    public function getSqlServerVersion()
    {
        return (string)$this->sqlServerVersion;
    }

    /**
     * @param string $sqlServerVersion
     */
    public function setSqlServerVersion($sqlServerVersion)
    {
        $this->sqlServerVersion = (string)$sqlServerVersion;
    }

    /**
     * @return string
     */
    public function getDbCharset()
    {
        return (string)$this->dbCharset;
    }

    /**
     * @param string $dbCharset
     */
    public function setDbCharset($dbCharset)
    {
        $this->dbCharset = (string)$dbCharset;
    }

    /**
     * @return int
     */
    public function getBackupSize()
    {
        return (int)$this->backupSize;
    }

    /**
     * @param int $backupSize
     */
    public function setBackupSize($backupSize)
    {
        $this->backupSize = (int)$backupSize;
    }

    /**
     * @return string
     */
    public function getAbsPath()
    {
        return $this->absPath;
    }

    /**
     * @param string $absPath
     */
    public function setAbsPath($absPath)
    {
        $this->absPath = $absPath;
    }

    /**
     * @return int
     */
    public function getBlogId()
    {
        return $this->blogId;
    }

    /**
     * @param int $blogId
     */
    public function setBlogId($blogId)
    {
        $this->blogId = $blogId;
    }

    /**
     * @return string
     */
    public function getUploadsPath()
    {
        return $this->uploadsPath;
    }

    /**
     * @param mixed $uploadsPath
     */
    public function setUploadsPath($uploadsPath)
    {
        $this->uploadsPath = $uploadsPath;
    }

    /**
     * @return string
     */
    public function getUploadsUrl()
    {
        return $this->uploadsUrl;
    }

    /**
     * @param mixed $uploadsUrl
     */
    public function setUploadsUrl($uploadsUrl)
    {
        $this->uploadsUrl = $uploadsUrl;
    }

    /**
     * @return int
     */
    public function getNetworkId()
    {
        return $this->networkId;
    }

    /**
     * @param int $networkId
     */
    public function setNetworkId($networkId)
    {
        $this->networkId = $networkId;
    }

    /**
     * @return bool Whether PHP can execute short open tags. Null if undefined.
     */
    public function getPhpShortOpenTags()
    {
        return $this->phpShortOpenTags;
    }

    /**
     * @param bool $phpShortOpenTags
     */
    public function setPhpShortOpenTags($phpShortOpenTags)
    {
        $this->phpShortOpenTags = $phpShortOpenTags;
    }

    /**
     * @return bool
     */
    public function getWpBakeryActive()
    {
        return $this->wpBakeryActive;
    }

    /**
     * @param bool $wpBakeryActive
     */
    public function setWpBakeryActive($wpBakeryActive)
    {
        $this->wpBakeryActive = $wpBakeryActive;
    }

    /*
     * @return string
     */
    public function getScheduleId()
    {
        return $this->scheduleId;
    }

    /**
     * @param string $scheduleId
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
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
    }

    /**
     * @return bool
     */
    public function getSubdomainInstall()
    {
        return $this->subdomainInstall;
    }

    /**
     * @param bool $subdomainInstall
     */
    public function setSubdomainInstall($subdomainInstall)
    {
        $this->subdomainInstall = $subdomainInstall;
    }

    /**
     * @return bool
     */
    public function getCreatedOnPro()
    {
        // Backward compatbility for PRO backups
        if (!isset($this->createdOnPro) || is_null($this->createdOnPro)) {
            $this->createdOnPro = true;
        }

        return $this->createdOnPro;
    }

    /**
     * @param bool $createdOnPro
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
     */
    public function setMultipartMetadata($multipartMetadata)
    {
        $this->multipartMetadata = $multipartMetadata;
    }

    /** @return bool */
    public function getIsMultipartBackup()
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
     */
    public function setNonWpTables($tables)
    {
        $this->nonWpTables = $tables;
    }

    public function setLogFile($fileName)
    {
        $this->logFile = $fileName;
    }

    public function setIndexPartSize($indexPartSize)
    {
        $this->indexPartSize = $indexPartSize;
    }

    /** @return array */
    public function getIndexPartSize()
    {
        return $this->indexPartSize;
    }
}
