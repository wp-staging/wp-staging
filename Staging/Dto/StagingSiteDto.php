<?php

namespace WPStaging\Staging\Dto;

use WPStaging\Framework\Traits\ArrayableTrait;
use WPStaging\Framework\Traits\HydrateTrait;








class StagingSiteDto implements \JsonSerializable
{
    use HydrateTrait;
    use ArrayableTrait;




    const STATUS_FINISHED = 'finished';

    const STATUS_UNFINISHED_BROKEN = 'unfinished or broken (?)';

 
    protected $cloneId = '';

 
    protected $cloneName = '';

 
    protected $directoryName = '';

 
    protected $path = '';

 
    protected $url = '';

 
    protected $number = 0;

 
    protected $version = '';

 
    protected $status = '';

 
    protected $prefix = '';

 
    protected $datetime = 0;

 
    protected $databaseUser = '';

 
    protected $databasePassword = '';

 
    protected $databaseDatabase = '';

 
    protected $databaseServer = '';

 
    protected $databasePrefix = '';

 
    protected $databaseSsl = false;

 
    protected $isEmailsAllowed = true;

 
    protected $uploadsSymlinked = false;

 
    protected $includedTables = [];

 
    protected $excludeSizeRules = [];

 
    protected $excludeGlobRules = [];

 
    protected $excludedDirectories = [];

 
    protected $extraDirectories = [];

 
    protected $networkClone = false;






    protected $sourceBlogId = 0;

 
    protected $isCronEnabled = true;

 
    protected $isWooSchedulerEnabled = true;

 
    protected $isEmailsReminderEnabled = false;

 
    protected $ownerId = 0;

 
    protected $useNewAdminAccount = false;

 
    protected $adminEmail = '';

 
    protected $adminPassword = '';

 
    protected $excludedDirs = [];

 
    protected $tablePushSelection = false;

 
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





    public function setCloneId($cloneId)
    {
        $this->cloneId = (string)$cloneId;
    }

    public function getCloneName(): string
    {
        return $this->cloneName;
    }





    public function setCloneName(string $cloneName)
    {
        $this->cloneName = $cloneName;
    }

    public function getDirectoryName(): string
    {
        return $this->directoryName;
    }





    public function setDirectoryName(string $directoryName)
    {
        $this->directoryName = $directoryName;
    }

    public function getPath(): string
    {
        return $this->path;
    }





    public function setPath(string $path)
    {
        $this->path = $path;
    }

    public function getUrl(): string
    {
        return $this->url;
    }





    public function setUrl(string $url)
    {
        $this->url = $url;
    }

    public function getNumber(): int
    {
        return $this->number;
    }





    public function setNumber(int $number)
    {
        $this->number = $number;
    }

    public function getVersion(): string
    {
        return $this->version;
    }





    public function setVersion(string $version)
    {
        $this->version = $version;
    }

    public function getStatus(): string
    {
        return $this->status;
    }





    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }





    public function setPrefix(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function getDatetime(): int
    {
        return $this->datetime;
    }





    public function setDatetime(int $datetime)
    {
        $this->datetime = $datetime;
    }

    public function getDatabaseUser(): string
    {
        return $this->databaseUser;
    }





    public function setDatabaseUser(string $databaseUser)
    {
        $this->databaseUser = $databaseUser;
    }

    public function getDatabasePassword(): string
    {
        return $this->databasePassword;
    }





    public function setDatabasePassword(string $databasePassword)
    {
        $this->databasePassword = $databasePassword;
    }

    public function getDatabaseDatabase(): string
    {
        return $this->databaseDatabase;
    }





    public function setDatabaseDatabase(string $databaseDatabase)
    {
        $this->databaseDatabase = $databaseDatabase;
    }

    public function getDatabaseServer(): string
    {
        return $this->databaseServer;
    }





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





    public function setDatabasePrefix(string $databasePrefix)
    {
        $this->databasePrefix = $databasePrefix;
    }

    public function getDatabaseSsl(): bool
    {
        return $this->databaseSsl;
    }





    public function setDatabaseSsl(bool $databaseSsl)
    {
        $this->databaseSsl = $databaseSsl;
    }

    public function getIsEmailsAllowed(): bool
    {
        return $this->isEmailsAllowed;
    }





    public function setIsEmailsAllowed(bool $isEmailsAllowed)
    {
        $this->isEmailsAllowed = $isEmailsAllowed;
    }

    public function getUploadsSymlinked(): bool
    {
        return $this->uploadsSymlinked;
    }





    public function setUploadsSymlinked(bool $uploadsSymlinked)
    {
        $this->uploadsSymlinked = $uploadsSymlinked;
    }

    public function getIncludedTables(): array
    {
        return $this->includedTables;
    }





    public function setIncludedTables(array $includedTables)
    {
        $this->includedTables = $includedTables;
    }

    public function getExcludeSizeRules(): array
    {
        return $this->excludeSizeRules;
    }





    public function setExcludeSizeRules(array $excludeSizeRules)
    {
        $this->excludeSizeRules = $excludeSizeRules;
    }

    public function getExcludeGlobRules(): array
    {
        return $this->excludeGlobRules;
    }





    public function setExcludeGlobRules(array $excludeGlobRules)
    {
        $this->excludeGlobRules = $excludeGlobRules;
    }

    public function getExcludedDirectories(): array
    {
        return $this->excludedDirectories;
    }





    public function setExcludedDirectories(array $excludedDirectories)
    {
        $this->excludedDirectories = $excludedDirectories;
    }

    public function getExtraDirectories(): array
    {
        return $this->extraDirectories;
    }





    public function setExtraDirectories(array $extraDirectories)
    {
        $this->extraDirectories = $extraDirectories;
    }

    public function getNetworkClone(): bool
    {
        return $this->networkClone;
    }





    public function setNetworkClone(bool $networkClone)
    {
        $this->networkClone = $networkClone;
    }

    public function getSourceBlogId(): int
    {
        return $this->sourceBlogId;
    }





    public function setSourceBlogId(int $sourceBlogId)
    {
        $this->sourceBlogId = $sourceBlogId;
    }

    public function getIsCronEnabled(): bool
    {
        return $this->isCronEnabled;
    }





    public function setIsCronEnabled(bool $isCronEnabled)
    {
        $this->isCronEnabled = $isCronEnabled;
    }

    public function getIsWooSchedulerEnabled(): bool
    {
        return $this->isWooSchedulerEnabled;
    }





    public function setIsWooSchedulerEnabled(bool $isWooSchedulerEnabled)
    {
        $this->isWooSchedulerEnabled = $isWooSchedulerEnabled;
    }

    public function getIsEmailsReminderEnabled(): bool
    {
        return $this->isEmailsReminderEnabled;
    }





    public function setIsEmailsReminderEnabled(bool $isEmailsReminderEnabled)
    {
        $this->isEmailsReminderEnabled = $isEmailsReminderEnabled;
    }

    public function getOwnerId(): int
    {
        return $this->ownerId;
    }





    public function setOwnerId(int $ownerId)
    {
        $this->ownerId = $ownerId;
    }

    public function getUseNewAdminAccount(): bool
    {
        return $this->useNewAdminAccount;
    }





    public function setUseNewAdminAccount(bool $useNewAdminAccount)
    {
        $this->useNewAdminAccount = $useNewAdminAccount;
    }

    public function getAdminEmail(): string
    {
        return $this->adminEmail;
    }





    public function setAdminEmail(string $adminEmail)
    {
        $this->adminEmail = $adminEmail;
    }

    public function getAdminPassword(): string
    {
        return $this->adminPassword;
    }





    public function setAdminPassword(string $adminPassword)
    {
        $this->adminPassword = $adminPassword;
    }

    public function getExcludedDirs(): array
    {
        return $this->excludedDirs;
    }





    public function setExcludedDirs(array $excludedDirs)
    {
        $this->excludedDirs = $excludedDirs;
    }




    public function getTablePushSelection()
    {
        return $this->tablePushSelection;
    }





    public function setTablePushSelection($tablePushSelection)
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







    public function getDatabaseName(): string
    {
        return empty($this->databaseDatabase) ? DB_NAME : $this->databaseDatabase;
    }







    public function getUsedPrefix(): string
    {
        return $this->getIsExternalDatabase() ? $this->getDatabasePrefix() : $this->getPrefix();
    }





    public function setIsAutoUpdatePlugins(bool $isAutoUpdatePlugins)
    {
        $this->isAutoUpdatePlugins = $isAutoUpdatePlugins;
    }




    public function getIsAutoUpdatePlugins(): bool
    {
        return $this->isAutoUpdatePlugins;
    }

    public function getIsUploadsSymlink(): bool
    {
        $uploadsDirectory = $this->getPath() . '/wp-content/uploads';

        return is_link($uploadsDirectory);
    }






    public function setIsUploadsSymlink(bool $isUploadsSymlink)
    {
        $this->uploadsSymlinked = $isUploadsSymlink;
    }





    public function isFromSubsite(): bool
    {
        return $this->sourceBlogId > 1;
    }
}
