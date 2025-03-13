<?php

namespace WPStaging\Staging\Dto;

use WPStaging\Framework\Traits\ArrayableTrait;
use WPStaging\Framework\Traits\HydrateTrait;

/**
 * Class StagingSiteDto
 *
 * This is OOP representation of all staging site options stored in database.
 *
 * @package WPStaging\Staging\Dto
 */
class StagingSiteDto implements \JsonSerializable
{
    use HydrateTrait;
    use ArrayableTrait;

    /**
     * @var string
     */
    const STATUS_FINISHED = 'finished';

    const STATUS_UNFINISHED_BROKEN = 'unfinished or broken (?)';

    /** @var string */
    protected $cloneId = '';

    /** @var string */
    protected $cloneName = '';

    /** @var string */
    protected $directoryName = '';

    /** @var string */
    protected $path = '';

    /** @var string */
    protected $url = '';

    /** @var int */
    protected $number = 0;

    /** @var string */
    protected $version = '';

    /** @var string */
    protected $status = '';

    /** @var string */
    protected $prefix = '';

    /** @var int */
    protected $datetime = 0;

    /** @var string */
    protected $databaseUser = '';

    /** @var string */
    protected $databasePassword = '';

    /** @var string */
    protected $databaseDatabase = '';

    /** @var string */
    protected $databaseServer = '';

    /** @var string */
    protected $databasePrefix = '';

    /** @var bool */
    protected $databaseSsl = false;

    /** @var bool */
    protected $emailsAllowed = true;

    /** @var bool */
    protected $uploadsSymlinked = false;

    /** @var array */
    protected $includedTables = [];

    /** @var array */
    protected $excludeSizeRules = [];

    /** @var array */
    protected $excludeGlobRules = [];

    /** @var array */
    protected $excludedDirectories = [];

    /** @var array */
    protected $extraDirectories = [];

    /** @var bool */
    protected $networkClone = false;

    /** @var bool */
    protected $cronDisabled = false;

    /** @var bool */
    protected $wooSchedulerDisabled = false;

    /** @var bool */
    protected $emailsReminderAllowed = false;

    /** @var int */
    protected $ownerId = 0;

    /** @var bool */
    protected $useNewAdminAccount = false;

    /** @var string */
    protected $adminEmail = '';

    /** @var string */
    protected $adminPassword = '';

    /** @var array */
    protected $excludedDirs = [];

    /** @var array */
    protected $tablePushSelection = [];

    /** @var bool */
    protected $isAutoUpdatePlugins = false;

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function toListableItem(): ListableStagingSite
    {
        $listable = new ListableStagingSite();
        $listable->cloneId = $this->cloneId;
        $listable->cloneName = $this->cloneName;
        $listable->siteName = $this->getSiteName();
        $listable->path = $this->path;
        $listable->url = $this->url;
        $listable->isNetworkClone = $this->networkClone;
        $listable->directoryName = $this->directoryName;
        $listable->status = $this->status;
        $listable->databaseName = $this->getDatabaseName();
        $listable->databasePrefix = $this->getUsedPrefix();
        $listable->modifiedAt = empty($this->datetime) ? 0 : get_date_from_gmt(date("Y-m-d H:i:s", $this->datetime), "D, d M Y H:i:s T");
        $listable->createdBy = $this->getOwnerName();

        return $listable;
    }

    public function getCloneId(): string
    {
        return $this->cloneId;
    }

    /**
     * @param string|null $cloneId
     * @return void
     */
    public function setCloneId($cloneId)
    {
        $this->cloneId = (string)$cloneId;
    }

    public function getCloneName(): string
    {
        return $this->cloneName;
    }

    /**
     * @param string $cloneName
     * @return void
     */
    public function setCloneName(string $cloneName)
    {
        $this->cloneName = $cloneName;
    }

    public function getDirectoryName(): string
    {
        return $this->directoryName;
    }

    /**
     * @param string $directoryName
     * @return void
     */
    public function setDirectoryName(string $directoryName)
    {
        $this->directoryName = $directoryName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return void
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @param int $number
     * @return void
     */
    public function setNumber(int $number)
    {
        $this->number = $number;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     * @return void
     */
    public function setVersion(string $version)
    {
        $this->version = $version;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return void
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     * @return void
     */
    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function getDatetime(): int
    {
        return $this->datetime;
    }

    /**
     * @param int $datetime
     * @return void
     */
    public function setDatetime(int $datetime)
    {
        $this->datetime = $datetime;
    }

    public function getDatabaseUser(): string
    {
        return $this->databaseUser;
    }

    /**
     * @param string $databaseUser
     * @return void
     */
    public function setDatabaseUser(string $databaseUser)
    {
        $this->databaseUser = $databaseUser;
    }

    public function getDatabasePassword(): string
    {
        return $this->databasePassword;
    }

    /**
     * @param string $databasePassword
     * @return void
     */
    public function setDatabasePassword(string $databasePassword)
    {
        $this->databasePassword = $databasePassword;
    }

    public function getDatabaseDatabase(): string
    {
        return $this->databaseDatabase;
    }

    /**
     * @param string $databaseDatabase
     * @return void
     */
    public function setDatabaseDatabase(string $databaseDatabase)
    {
        $this->databaseDatabase = $databaseDatabase;
    }

    public function getDatabaseServer(): string
    {
        return $this->databaseServer;
    }

    /**
     * @param string $databaseServer
     * @return void
     */
    public function setDatabaseServer(string $databaseServer)
    {
        $this->databaseServer = $databaseServer;
    }

    public function getDatabasePrefix(): string
    {
        if (empty($this->databasePrefix)) {
            return $this->prefix;
        }

        return $this->databasePrefix;
    }

    /**
     * @param string $databasePrefix
     * @return void
     */
    public function setDatabasePrefix(string $databasePrefix)
    {
        $this->databasePrefix = $databasePrefix;
    }

    public function getDatabaseSsl(): bool
    {
        return $this->databaseSsl;
    }

    /**
     * @param bool $databaseSsl
     * @return void
     */
    public function setDatabaseSsl(bool $databaseSsl)
    {
        $this->databaseSsl = $databaseSsl;
    }

    public function getEmailsAllowed(): bool
    {
        return $this->emailsAllowed;
    }

    /**
     * @param bool $emailsAllowed
     * @return void
     */
    public function setEmailsAllowed(bool $emailsAllowed)
    {
        $this->emailsAllowed = $emailsAllowed;
    }

    public function getUploadsSymlinked(): bool
    {
        return $this->uploadsSymlinked;
    }

    /**
     * @param bool $uploadsSymlinked
     * @return void
     */
    public function setUploadsSymlinked(bool $uploadsSymlinked)
    {
        $this->uploadsSymlinked = $uploadsSymlinked;
    }

    public function getIncludedTables(): array
    {
        return $this->includedTables;
    }

    /**
     * @param array $includedTables
     * @return void
     */
    public function setIncludedTables(array $includedTables)
    {
        $this->includedTables = $includedTables;
    }

    public function getExcludeSizeRules(): array
    {
        return $this->excludeSizeRules;
    }

    /**
     * @param array $excludeSizeRules
     * @return void
     */
    public function setExcludeSizeRules(array $excludeSizeRules)
    {
        $this->excludeSizeRules = $excludeSizeRules;
    }

    public function getExcludeGlobRules(): array
    {
        return $this->excludeGlobRules;
    }

    /**
     * @param array $excludeGlobRules
     * @return void
     */
    public function setExcludeGlobRules(array $excludeGlobRules)
    {
        $this->excludeGlobRules = $excludeGlobRules;
    }

    public function getExcludedDirectories(): array
    {
        return $this->excludedDirectories;
    }

    /**
     * @param array $excludedDirectories
     * @return void
     */
    public function setExcludedDirectories(array $excludedDirectories)
    {
        $this->excludedDirectories = $excludedDirectories;
    }

    public function getExtraDirectories(): array
    {
        return $this->extraDirectories;
    }

    /**
     * @param array $extraDirectories
     * @return void
     */
    public function setExtraDirectories(array $extraDirectories)
    {
        $this->extraDirectories = $extraDirectories;
    }

    public function getNetworkClone(): bool
    {
        return $this->networkClone;
    }

    /**
     * @param bool $networkClone
     * @return void
     */
    public function setNetworkClone(bool $networkClone)
    {
        $this->networkClone = $networkClone;
    }

    public function getCronDisabled(): bool
    {
        return $this->cronDisabled;
    }

    /**
     * @param bool $cronDisabled
     * @return void
     */
    public function setCronDisabled(bool $cronDisabled)
    {
        $this->cronDisabled = $cronDisabled;
    }

    public function getWooSchedulerDisabled(): bool
    {
        return $this->wooSchedulerDisabled;
    }

    /**
     * @param bool $wooSchedulerDisabled
     * @return void
     */
    public function setWooSchedulerDisabled(bool $wooSchedulerDisabled)
    {
        $this->wooSchedulerDisabled = $wooSchedulerDisabled;
    }

    public function getEmailsReminderAllowed(): bool
    {
        return $this->emailsReminderAllowed;
    }

    /**
     * @param bool $emailsReminderAllowed
     * @return void
     */
    public function setEmailsReminderAllowed(bool $emailsReminderAllowed)
    {
        $this->emailsReminderAllowed = $emailsReminderAllowed;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    /**
     * @param int $ownerId
     * @return void
     */
    public function setOwnerId(int $ownerId)
    {
        $this->ownerId = $ownerId;
    }

    public function getUseNewAdminAccount(): bool
    {
        return $this->useNewAdminAccount;
    }

    /**
     * @param bool $useNewAdminAccount
     * @return void
     */
    public function setUseNewAdminAccount(bool $useNewAdminAccount)
    {
        $this->useNewAdminAccount = $useNewAdminAccount;
    }

    public function getAdminEmail(): string
    {
        return $this->adminEmail;
    }

    /**
     * @param string $adminEmail
     * @return void
     */
    public function setAdminEmail(string $adminEmail)
    {
        $this->adminEmail = $adminEmail;
    }

    public function getAdminPassword(): string
    {
        return $this->adminPassword;
    }

    /**
     * @param string $adminPassword
     * @return void
     */
    public function setAdminPassword(string $adminPassword)
    {
        $this->adminPassword = $adminPassword;
    }

    public function getExcludedDirs(): array
    {
        return $this->excludedDirs;
    }

    /**
     * @param array $excludedDirs
     * @return void
     */
    public function setExcludedDirs(array $excludedDirs)
    {
        $this->excludedDirs = $excludedDirs;
    }

    public function getTablePushSelection(): array
    {
        return $this->tablePushSelection;
    }

    /**
     * @param array $tablePushSelection
     * @return void
     */
    public function setTablePushSelection(array $tablePushSelection)
    {
        $this->tablePushSelection = $tablePushSelection;
    }

    public function getSiteName(): string
    {
        return empty($this->cloneName) ? $this->directoryName : $this->cloneName;
    }

    public function getIsCustomDatabaseConnection(): bool
    {
        return !empty($this->databaseDatabase) && !empty($this->databaseUser);
    }

    public function getIsExternalDatabase(): bool
    {
        return $this->getIsCustomDatabaseConnection()
            && ($this->getDatabaseName() !== DB_NAME
            || $this->getDatabaseServer() !== DB_HOST);
    }

    public function getOwnerName(): string
    {
        if (empty($this->ownerId)) {
            return 'N/A';
        }

        $owner = get_userdata($this->ownerId);
        if (empty($owner)) {
            return 'N/A';
        }

        return isset($owner->user_login) ? $owner->user_login : 'N/A';
    }

    /**
     * Current Production Site Database Name if no external database is used,
     * otherwise external database name.
     *
     * @return string
     */
    public function getDatabaseName(): string
    {
        return empty($this->databaseDatabase) ? DB_NAME : $this->databaseDatabase;
    }

    /**
     * If current production database, return value from $this->prefix,
     * otherwise return value from $this->databasePrefix.
     *
     * @return string
     */
    public function getUsedPrefix(): string
    {
        return $this->getIsExternalDatabase() ? $this->getDatabasePrefix() : $this->getPrefix();
    }

    /**
     * @param bool $isAutoUpdatePlugins
     * @return void
     */
    public function setIsAutoUpdatePlugins(bool $isAutoUpdatePlugins)
    {
        $this->isAutoUpdatePlugins = $isAutoUpdatePlugins;
    }

    /**
     * @return bool
     */
    public function getIsAutoUpdatePlugins(): bool
    {
        return $this->isAutoUpdatePlugins;
    }
}
