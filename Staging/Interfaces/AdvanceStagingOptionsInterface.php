<?php

namespace WPStaging\Staging\Interfaces;

interface AdvanceStagingOptionsInterface
{
    public function setUseNewAdminAccount(bool $useNewAdminAccount);

    public function getUseNewAdminAccount(): bool;

    public function setAdminEmail(string $adminEmail);

    public function getAdminEmail(): string;

    public function setAdminPassword(string $adminPassword);

    public function getAdminPassword(): string;

    public function setUseCustomDatabase(bool $useCustomDatabase);

    public function getUseCustomDatabase(): bool;

    public function setDatabaseServer(string $databaseServer);

    public function getDatabaseServer(): string;

    public function setDatabaseName(string $databaseName);

    public function getDatabaseName(): string;

    public function setDatabaseUser(string $databaseUser);

    public function getDatabaseUser(): string;

    public function setDatabasePassword(string $databasePassword);

    public function getDatabasePassword(): string;

    public function setDatabasePrefix(string $databasePrefix);

    public function getDatabasePrefix(): string;

    public function setDatabaseSsl(bool $databaseSsl);

    public function getDatabaseSsl(): bool;

    public function setCustomUrl(string $customUrl);

    public function getCustomUrl(): string;

    public function setCustomPath(string $customPath);

    public function getCustomPath(): string;

    public function setIsEmailsAllowed(bool $emailsAllowed);

    public function getIsEmailsAllowed(): bool;

    public function setIsUploadsSymlinked(bool $uploadsSymlinked);

    public function getIsUploadsSymlinked(): bool;

    public function setIsCronEnabled(bool $cronEnabled);

    public function getIsCronEnabled(): bool;

    public function setIsWooSchedulerEnabled(bool $wooSchedulerEnabled);

    public function getIsWooSchedulerEnabled(): bool;

    public function setIsEmailsReminderEnabled(bool $emailsReminderEnabled);

    public function getIsEmailsReminderEnabled(): bool;

    public function setIsAutoUpdatePlugins(bool $autoUpdatePlugins);

    public function getIsAutoUpdatePlugins(): bool;

    public function getTmpExcludedFullPaths(): array;

    public function setTmpExcludedFullPaths(array $tmpExcludedFullPaths);

    public function getTmpExcludedGoDaddyFiles(): array;

    public function setTmpExcludedGoDaddyFiles(array $tmpExcludedGoDaddyFiles);
}
