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

    public function setEmailsAllowed(bool $emailsAllowed);

    public function getEmailsAllowed(): bool;

    public function setCronDisabled(bool $cronDisabled);

    public function getCronDisabled(): bool;

    public function setWooSchedulerDisabled(bool $wooSchedulerDisabled);

    public function getWooSchedulerDisabled(): bool;

    public function setEmailsReminderAllowed(bool $emailsReminderAllowed);

    public function getEmailsReminderAllowed(): bool;
}
