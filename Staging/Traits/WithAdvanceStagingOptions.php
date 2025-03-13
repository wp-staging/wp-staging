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

    /** @var string */
    private $stagingSiteUrl = '';

    /** @var string */
    private $stagingSitePath = '';

    /** @var bool */
    private $emailsAllowed = true;

    /** @var bool */
    private $cronDisabled = false;

    /** @var bool */
    private $wooSchedulerDisabled = false;

    /** @var bool */
    private $emailsReminderAllowed = false;

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
     * @param bool $emailsAllowed
     * @return void
     */
    public function setEmailsAllowed(bool $emailsAllowed)
    {
        $this->emailsAllowed = $emailsAllowed;
    }

    /**
     * @return bool
     */
    public function getEmailsAllowed(): bool
    {
        return $this->emailsAllowed;
    }

    /**
     * @param bool $cronDisabled
     * @return void
     */
    public function setCronDisabled(bool $cronDisabled)
    {
        $this->cronDisabled = $cronDisabled;
    }

    /**
     * @return bool
     */
    public function getCronDisabled(): bool
    {
        return $this->cronDisabled;
    }

    /**
     * @param bool $wooSchedulerDisabled
     * @return void
     */
    public function setWooSchedulerDisabled(bool $wooSchedulerDisabled)
    {
        $this->wooSchedulerDisabled = $wooSchedulerDisabled;
    }

    /**
     * @return bool
     */
    public function getWooSchedulerDisabled(): bool
    {
        return $this->wooSchedulerDisabled;
    }

    /**
     * @param bool $emailsReminderAllowed
     * @return void
     */
    public function setEmailsReminderAllowed(bool $emailsReminderAllowed)
    {
        $this->emailsReminderAllowed = $emailsReminderAllowed;
    }

    /**
     * @return bool
     */
    public function getEmailsReminderAllowed(): bool
    {
        return $this->emailsReminderAllowed;
    }
}
