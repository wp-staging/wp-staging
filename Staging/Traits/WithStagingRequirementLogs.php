<?php

namespace WPStaging\Staging\Traits;

use WPStaging\Core\Utils\Logger;
use WPStaging\Pro\License\Licensing;
use WPStaging\Staging\Service\StagingSetup;

/**
 * Adds common staging requirement log sections.
 */
trait WithStagingRequirementLogs
{
    /**
     * @return void
     */
    protected function writeAdvancedSettingsToLogs()
    {
        $settingsToLog = $this->getAdvancedSettingsToLog();
        if (empty($settingsToLog)) {
            return;
        }

        $this->logger->add('Advanced Settings', Logger::TYPE_INFO);

        foreach ($settingsToLog as $setting) {
            $this->writeAdvancedSettingToLogs($setting);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAdvancedSettingsToLog(): array
    {
        if (!$this->isProRequirementLog() || !method_exists($this->jobDataDto, 'getJobType')) {
            return [];
        }

        switch ($this->jobDataDto->getJobType()) {
            case StagingSetup::JOB_NEW_STAGING_SITE:
                return $this->getCreateAdvancedSettingsToLog();
            case StagingSetup::JOB_UPDATE:
                return $this->getUpdateAdvancedSettingsToLog();
            case StagingSetup::JOB_PUSH:
                return $this->getPushAdvancedSettingsToLog();
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCreateAdvancedSettingsToLog(): array
    {
        $settingsToLog = [
            $this->booleanSetting('New Admin Account', 'getUseNewAdminAccount'),
            $this->stringSetting('Email', 'getAdminEmail'),
            $this->sensitiveStringSetting('Password', 'getAdminPassword'),
            $this->stringSetting('Database Server', 'getDatabaseServer'),
            $this->stringSetting('Database User', 'getDatabaseUser'),
            $this->sensitiveStringSetting('Database Password', 'getDatabasePassword'),
            $this->stringSetting('Database', 'getDatabaseName'),
            $this->stringSetting('Database Prefix', 'getDatabasePrefix'),
            $this->booleanSetting('Database SSL', 'getDatabaseSsl'),
            $this->stringSetting('Clone Directory', 'getCustomPath'),
            $this->stringSetting('Clone Host', 'getCustomUrl'),
            $this->booleanSetting('Symlink Uploads Folder', 'getIsUploadsSymlinked'),
            $this->booleanSetting('WP CRON Enabled', 'getIsCronEnabled'),
            $this->booleanSetting('Emails Sending Allowed', 'getIsEmailsAllowed'),
            $this->booleanSetting('Email Reminder Enabled', 'getIsEmailsReminderEnabled'),
            $this->booleanSetting('Auto Update Plugins Enabled', 'getIsAutoUpdatePlugins'),
        ];

        if ($this->isWooSchedulerSettingRendered()) {
            $settingsToLog[] = $this->booleanSetting(
                'WooCommerce Scheduler Enabled',
                'getIsWooSchedulerEnabled'
            );
        }

        return $settingsToLog;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUpdateAdvancedSettingsToLog(): array
    {
        return [
            $this->booleanSetting('Emails Sending Allowed', 'getIsEmailsAllowed'),
            $this->booleanSetting('Email Reminder Enabled', 'getIsEmailsReminderEnabled'),
            $this->booleanSetting('Auto Update Plugins Enabled', 'getIsAutoUpdatePlugins'),
            $this->booleanSetting('Clean Plugins/Themes', 'getIsCleanPluginsThemes'),
            $this->booleanSetting('Clean Uploads', 'getIsCleanUploads'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPushAdvancedSettingsToLog(): array
    {
        return [
            $this->booleanSetting('Clean Plugins/Themes', 'getIsCleanPluginsThemes'),
            $this->booleanSetting('Clean Uploads', 'getIsCleanUploads'),
            $this->booleanSetting('Backup Uploads', 'getIsBackupUploads'),
            $this->booleanSetting('Create Database Backup', 'getIsCreateDatabaseBackup'),
        ];
    }

    /**
     * @param array<string, mixed> $setting
     * @return void
     */
    private function writeAdvancedSettingToLogs(array $setting)
    {
        switch ($setting['type']) {
            case 'boolean':
                $this->writeBooleanSettingToLogs($setting['label'], $setting['methods']);
                break;
            case 'sensitive':
                $this->writeSensitiveStringSettingToLogs($setting['label'], $setting['methods']);
                break;
            default:
                $this->writeStringSettingToLogs($setting['label'], $setting['methods']);
                break;
        }
    }

    /**
     * @param string $label
     * @param string|string[] $methods
     * @return array<string, mixed>
     */
    private function stringSetting(string $label, $methods): array
    {
        return [
            'type'    => 'string',
            'label'   => $label,
            'methods' => $methods,
        ];
    }

    /**
     * @param string $label
     * @param string|string[] $methods
     * @return array<string, mixed>
     */
    private function sensitiveStringSetting(string $label, $methods): array
    {
        return [
            'type'    => 'sensitive',
            'label'   => $label,
            'methods' => $methods,
        ];
    }

    /**
     * @param string $label
     * @param string|string[] $methods
     * @return array<string, mixed>
     */
    private function booleanSetting(string $label, $methods): array
    {
        return [
            'type'    => 'boolean',
            'label'   => $label,
            'methods' => $methods,
        ];
    }

    /**
     * @return bool
     */
    private function isProRequirementLog(): bool
    {
        return strpos(static::class, '\\Pro\\') !== false;
    }

    /**
     * @return bool
     */
    private function isWooSchedulerSettingRendered(): bool
    {
        if (!class_exists(Licensing::class)) {
            return false;
        }

        $licenseData     = get_option('wpstg_license_status');
        $licensePriceId  = !empty($licenseData->price_id) ? $licenseData->price_id : '';
        $acceptablePlans = [
            Licensing::AGENCY_LICENSE_PLAN_KEY,
            Licensing::DEVELOPER_LICENSE_PLAN_KEY,
            Licensing::DEVELOPER_LEGACY_LICENSE_PLAN_KEY,
            Licensing::DEVELOPER_30_SITES_LICENSE_PLAN_KEY,
            Licensing::DEVELOPER_NON_RECURRING_LICENSE_PLAN_KEY,
            Licensing::AGENCY_NON_RECURRING_LICENSE_PLAN_KEY,
            Licensing::DEVELOPER_UNLIMITED_SITES_LICENSE_PLAN_KEY,
        ];

        return in_array($licensePriceId, $acceptablePlans, true);
    }

    /**
     * @param string $label
     * @param string|string[] $methods
     * @return void
     */
    private function writeStringSettingToLogs(string $label, $methods)
    {
        $value = $this->getLogSettingValue((array)$methods);
        if ($value === null) {
            return;
        }

        $this->logger->add(sprintf('- %s : %s', $label, $value !== '' ? $value : 'Not Set'), Logger::TYPE_INFO_SUB);
    }

    /**
     * @param string $label
     * @param string|string[] $methods
     * @return void
     */
    private function writeSensitiveStringSettingToLogs(string $label, $methods)
    {
        $value = $this->getLogSettingValue((array)$methods);
        if ($value === null) {
            return;
        }

        $value = $value !== '' ? '**************' : 'Not Set';

        $this->logger->add(sprintf('- %s : %s', $label, $value), Logger::TYPE_INFO_SUB);
    }

    /**
     * @param string $label
     * @param string|string[] $methods
     * @return void
     */
    private function writeBooleanSettingToLogs(string $label, $methods)
    {
        $value = $this->getLogSettingValue((array)$methods);
        if ($value === null) {
            return;
        }

        $value = $value ? 'True' : 'False';

        $this->logger->add(sprintf('- %s : %s', $label, $value), Logger::TYPE_INFO_SUB);
    }

    /**
     * @param string[] $methods
     * @return mixed|null
     */
    private function getLogSettingValue(array $methods)
    {
        $fallbackValue = null;
        foreach ($this->getLogSettingSources() as $source) {
            foreach ($methods as $method) {
                if (!method_exists($source, $method)) {
                    continue;
                }

                $value = $source->{$method}();
                if ($value !== '' && $value !== null) {
                    return $value;
                }

                $fallbackValue = $value;
            }
        }

        return $fallbackValue;
    }

    /**
     * @return object[]
     */
    private function getLogSettingSources(): array
    {
        return [$this->jobDataDto];
    }
}
