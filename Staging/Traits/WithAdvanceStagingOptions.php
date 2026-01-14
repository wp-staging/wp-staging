<?php

namespace WPStaging\Staging\Traits;

trait WithAdvanceStagingOptions
{
    /** @var bool */
    private $useNewAdminAccount = false;

    /** @var string */
    private $adminEmail = '';

    /** @var string */
    private $adminPassword = '';

    /** @var bool */
    private $useCustomDatabase = false;

    /** @var string */
    private $databaseServer = '';

    /** @var string */
    private $databaseName = '';

    /** @var string */
    private $databaseUser = '';

    /** @var string */
    private $databasePassword = '';

    /** @var string */
    private $databasePrefix = '';

    /** @var bool */
    private $databaseSsl = false;

    /** @var string */
    private $customUrl = '';

    /** @var string */
    private $customPath = '';

    /** @var bool */
    private $isEmailsAllowed = true;

    /** @var bool */
    private $isUploadsSymlinked = false;

    /** @var bool */
    private $isCronEnabled = true;

    /** @var bool */
    private $isWooSchedulerEnabled = true;

    /** @var bool */
    private $isEmailsReminderEnabled = false;

    /** @var bool */
    private $isAutoUpdatePlugins = false;

    /** @var string[] */
    private $tmpExcludedFullPaths = [];

    /** @var string[] */
    private $tmpExcludedGoDaddyFiles = [];

    /**
     * @param bool $useNewAdminAccount
     * @return void
     */
    public function setUseNewAdminAccount(bool $useNewAdminAccount)
    {
        $this->useNewAdminAccount = $useNewAdminAccount;
    }

    /**
     * @return bool
     */
    public function getUseNewAdminAccount(): bool
    {
        return $this->useNewAdminAccount;
    }

    /**
     * @param string $adminEmail
     * @return void
     */
    public function setAdminEmail(string $adminEmail)
    {
        $this->adminEmail = $adminEmail;
    }

    /**
     * @return string
     */
    public function getAdminEmail(): string
    {
        return $this->adminEmail;
    }

    /**
     * @param string $adminPassword
     * @return void
     */
    public function setAdminPassword(string $adminPassword)
    {
        $this->adminPassword = $adminPassword;
    }

    /**
     * @return string
     */
    public function getAdminPassword(): string
    {
        return $this->adminPassword;
    }

    /**
     * @param bool $useCustomDatabase
     * @return void
     */
    public function setUseCustomDatabase(bool $useCustomDatabase)
    {
        $this->useCustomDatabase = $useCustomDatabase;
    }

    /**
     * @return bool
     */
    public function getUseCustomDatabase(): bool
    {
        return $this->useCustomDatabase;
    }

    /**
     * @param string $databaseServer
     * @return void
     */
    public function setDatabaseServer(string $databaseServer)
    {
        $this->databaseServer = $databaseServer;
    }

    /**
     * @return string
     */
    public function getDatabaseServer(): string
    {
        return $this->databaseServer;
    }

    /**
     * @param string $databaseName
     * @return void
     */
    public function setDatabaseName(string $databaseName)
    {
        $this->databaseName = $databaseName;
    }

    /**
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * @param string $databaseUser
     * @return void
     */
    public function setDatabaseUser(string $databaseUser)
    {
        $this->databaseUser = $databaseUser;
    }

    /**
     * @return string
     */
    public function getDatabaseUser(): string
    {
        return $this->databaseUser;
    }

    /**
     * @param string $databasePassword
     * @return void
     */
    public function setDatabasePassword(string $databasePassword)
    {
        $this->databasePassword = $databasePassword;
    }

    /**
     * @return string
     */
    public function getDatabasePassword(): string
    {
        return $this->databasePassword;
    }

    /**
     * @param string $databasePrefix
     * @return void
     */
    public function setDatabasePrefix(string $databasePrefix)
    {
        $this->databasePrefix = $databasePrefix;
    }

    /**
     * @return string
     */
    public function getDatabasePrefix(): string
    {
        return $this->databasePrefix;
    }

    /**
     * @param bool $databaseSsl
     * @return void
     */
    public function setDatabaseSsl(bool $databaseSsl)
    {
        $this->databaseSsl = $databaseSsl;
    }

    /**
     * @return bool
     */
    public function getDatabaseSsl(): bool
    {
        return $this->databaseSsl;
    }

    /**
     * @param string $customUrl
     * @return void
     */
    public function setCustomUrl(string $customUrl)
    {
        $this->customUrl = $customUrl;
    }

    /**
     * @return string
     */
    public function getCustomUrl(): string
    {
        return $this->customUrl;
    }

    /**
     * @param string $customPath
     * @return void
     */
    public function setCustomPath(string $customPath)
    {
        $this->customPath = $customPath;
    }

    /**
     * @return string
     */
    public function getCustomPath(): string
    {
        return $this->customPath;
    }

    /**
     * @param bool $isEmailsAllowed
     * @return void
     */
    public function setIsEmailsAllowed(bool $isEmailsAllowed)
    {
        $this->isEmailsAllowed = $isEmailsAllowed;
    }

    /**
     * @return bool
     */
    public function getIsEmailsAllowed(): bool
    {
        return $this->isEmailsAllowed;
    }

    public function setIsUploadsSymlinked(bool $isUploadsSymlinked)
    {
        $this->isUploadsSymlinked = $isUploadsSymlinked;
    }

    public function getIsUploadsSymlinked(): bool
    {
        return $this->isUploadsSymlinked;
    }

    /**
     * @param bool $isCronEnabled
     * @return void
     */
    public function setIsCronEnabled(bool $isCronEnabled)
    {
        $this->isCronEnabled = $isCronEnabled;
    }

    /**
     * @return bool
     */
    public function getIsCronEnabled(): bool
    {
        return $this->isCronEnabled;
    }

    /**
     * @param bool $isWooSchedulerEnabled
     * @return void
     */
    public function setIsWooSchedulerEnabled(bool $isWooSchedulerEnabled)
    {
        $this->isWooSchedulerEnabled = $isWooSchedulerEnabled;
    }

    /**
     * @return bool
     */
    public function getIsWooSchedulerEnabled(): bool
    {
        return $this->isWooSchedulerEnabled;
    }

    /**
     * @param bool $isEmailsReminderEnabled
     * @return void
     */
    public function setIsEmailsReminderEnabled(bool $isEmailsReminderEnabled)
    {
        $this->isEmailsReminderEnabled = $isEmailsReminderEnabled;
    }

    /**
     * @return bool
     */
    public function getIsEmailsReminderEnabled(): bool
    {
        return $this->isEmailsReminderEnabled;
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

    /**
     * @param string[] $tmpExcludedFullPaths
     * @return void
     */
    public function setTmpExcludedFullPaths(array $tmpExcludedFullPaths)
    {
        $this->tmpExcludedFullPaths = $tmpExcludedFullPaths;
    }

    /**
     * @return string[]
     */
    public function getTmpExcludedFullPaths(): array
    {
        return $this->tmpExcludedFullPaths;
    }

    /**
     * @param string[] $tmpExcludedGoDaddyFiles
     * @return void
     */
    public function setTmpExcludedGoDaddyFiles(array $tmpExcludedGoDaddyFiles)
    {
        $this->tmpExcludedGoDaddyFiles = $tmpExcludedGoDaddyFiles;
    }

    /**
     * @return string[]
     */
    public function getTmpExcludedGoDaddyFiles(): array
    {
        return $this->tmpExcludedGoDaddyFiles;
    }
}
