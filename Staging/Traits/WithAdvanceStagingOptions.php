<?php

namespace WPStaging\Staging\Traits;

trait WithAdvanceStagingOptions
{
 
    private $useNewAdminAccount = false;

 
    private $adminEmail = '';

 
    private $adminPassword = '';

 
    private $useCustomDatabase = false;

 
    private $databaseServer = '';

 
    private $databaseName = '';

 
    private $databaseUser = '';

 
    private $databasePassword = '';

 
    private $databasePrefix = '';

 
    private $databaseSsl = false;

 
    private $customUrl = '';

 
    private $customPath = '';

 
    private $isEmailsAllowed = true;

 
    private $isUploadsSymlinked = false;

 
    private $isCronEnabled = true;

 
    private $isWooSchedulerEnabled = true;

 
    private $isEmailsReminderEnabled = false;

 
    private $isAutoUpdatePlugins = false;

 
    private $tmpExcludedFullPaths = [];

 
    private $tmpExcludedGoDaddyFiles = [];





    public function setUseNewAdminAccount(bool $useNewAdminAccount)
    {
        $this->useNewAdminAccount = $useNewAdminAccount;
    }




    public function getUseNewAdminAccount(): bool
    {
        return $this->useNewAdminAccount;
    }





    public function setAdminEmail(string $adminEmail)
    {
        $this->adminEmail = $adminEmail;
    }




    public function getAdminEmail(): string
    {
        return $this->adminEmail;
    }





    public function setAdminPassword(string $adminPassword)
    {
        $this->adminPassword = $adminPassword;
    }




    public function getAdminPassword(): string
    {
        return $this->adminPassword;
    }





    public function setUseCustomDatabase(bool $useCustomDatabase)
    {
        $this->useCustomDatabase = $useCustomDatabase;
    }




    public function getUseCustomDatabase(): bool
    {
        return $this->useCustomDatabase;
    }





    public function setDatabaseServer(string $databaseServer)
    {
        $this->databaseServer = $databaseServer;
    }




    public function getDatabaseServer(): string
    {
        return $this->databaseServer;
    }





    public function setDatabaseName(string $databaseName)
    {
        $this->databaseName = $databaseName;
    }




    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }





    public function setDatabaseUser(string $databaseUser)
    {
        $this->databaseUser = $databaseUser;
    }




    public function getDatabaseUser(): string
    {
        return $this->databaseUser;
    }





    public function setDatabasePassword(string $databasePassword)
    {
        $this->databasePassword = $databasePassword;
    }




    public function getDatabasePassword(): string
    {
        return $this->databasePassword;
    }





    public function setDatabasePrefix(string $databasePrefix)
    {
        $this->databasePrefix = $databasePrefix;
    }




    public function getDatabasePrefix(): string
    {
        return $this->databasePrefix;
    }





    public function setDatabaseSsl(bool $databaseSsl)
    {
        $this->databaseSsl = $databaseSsl;
    }




    public function getDatabaseSsl(): bool
    {
        return $this->databaseSsl;
    }





    public function setCustomUrl(string $customUrl)
    {
        $this->customUrl = $customUrl;
    }




    public function getCustomUrl(): string
    {
        return $this->customUrl;
    }





    public function setCustomPath(string $customPath)
    {
        $this->customPath = $customPath;
    }




    public function getCustomPath(): string
    {
        return $this->customPath;
    }





    public function setIsEmailsAllowed(bool $isEmailsAllowed)
    {
        $this->isEmailsAllowed = $isEmailsAllowed;
    }




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





    public function setIsCronEnabled(bool $isCronEnabled)
    {
        $this->isCronEnabled = $isCronEnabled;
    }




    public function getIsCronEnabled(): bool
    {
        return $this->isCronEnabled;
    }





    public function setIsWooSchedulerEnabled(bool $isWooSchedulerEnabled)
    {
        $this->isWooSchedulerEnabled = $isWooSchedulerEnabled;
    }




    public function getIsWooSchedulerEnabled(): bool
    {
        return $this->isWooSchedulerEnabled;
    }





    public function setIsEmailsReminderEnabled(bool $isEmailsReminderEnabled)
    {
        $this->isEmailsReminderEnabled = $isEmailsReminderEnabled;
    }




    public function getIsEmailsReminderEnabled(): bool
    {
        return $this->isEmailsReminderEnabled;
    }





    public function setIsAutoUpdatePlugins(bool $isAutoUpdatePlugins)
    {
        $this->isAutoUpdatePlugins = $isAutoUpdatePlugins;
    }




    public function getIsAutoUpdatePlugins(): bool
    {
        return $this->isAutoUpdatePlugins;
    }





    public function setTmpExcludedFullPaths(array $tmpExcludedFullPaths)
    {
        $this->tmpExcludedFullPaths = $tmpExcludedFullPaths;
    }




    public function getTmpExcludedFullPaths(): array
    {
        return $this->tmpExcludedFullPaths;
    }





    public function setTmpExcludedGoDaddyFiles(array $tmpExcludedGoDaddyFiles)
    {
        $this->tmpExcludedGoDaddyFiles = $tmpExcludedGoDaddyFiles;
    }




    public function getTmpExcludedGoDaddyFiles(): array
    {
        return $this->tmpExcludedGoDaddyFiles;
    }
}
