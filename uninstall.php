<?php


if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Handles plugin uninstallation and cleanup of WP Staging data
 *
 * This class manages the complete uninstallation process including:
 * - Detecting single site vs multisite/network uninstall scenarios
 * - Distinguishing between Basic and Pro version uninstallation
 * - Preserving data when both versions are installed
 * - Cleaning up options, transients, and cron events
 * - Removing plugin directories (except those containing backups)
 * - Respecting user's "Remove Data on Uninstall" setting
 *
 * The class runs in standalone context without the plugin's autoloader,
 * so it must be self-contained with no external dependencies.
 *
 * Note: Avoids using class constants to prevent loading the whole plugin.
 * @package     WPSTG
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.0
 */
class Uninstall
{
    /**
     * Options we want to preserve.
     * These should remain until we have a staging site deletion routine.
     */
    private $preserveOptions = [
        'wpstg_existing_clones',
        'wpstg_existing_clones_beta',
        'wpstg_staging_sites',
        'wpstg_connection',
    ];

    public function __construct()
    {
        if (!is_multisite()) {
            $this->runForSingleSite(); // Normal single-site uninstall
            return;
        }

        if ($this->isNetworkUninstall()) {
            $this->runForNetwork(); // Full cleanup across all sites + network data
        } else {
            $this->runForSingleSite(); // Only clean current subsite
        }
    }

    /**
     * @return void
     */
    private function runForNetwork()
    {
        $siteIds = get_sites(['fields' => 'ids']);
        foreach ($siteIds as $siteId) {
            switch_to_blog($siteId);
            $this->runForSingleSite();
            restore_current_blog();
        }

        $this->deleteNetworkOptions();
    }

    /**
     * @return void
     */
    private function runForSingleSite()
    {
        $settings = $this->getSettings();

        if (empty($settings['unInstallOnDelete']) || $settings['unInstallOnDelete'] !== '1') {
            return;
        }

        // If Pro is installed, no matter if active or not, and we're uninstalling Basic, do nothing to preserve all data.
        // This is to make sure pro version still works once user installs free version again in case he only temporary uninstalled it.
        if ($this->isProInstalled() && $this->isUninstallingBasic()) {
            return;
        }

        // If Basic is installed, and we're uninstalling Pro, remove only Pro data
        if ($this->isBasicInstalled() && $this->isUninstallingPro()) {
            $this->deleteOptions($this->getProOptions());
            return;
        }

        // If Basic not installed, and we're uninstalling Pro, remove all data
        if (!$this->isBasicInstalled() && $this->isUninstallingPro()) {
            $this->performCompleteCleanup(true);
            return;
        }

        // If Pro not installed, and we're uninstalling Basic, remove all data
        if (!$this->isProInstalled() && $this->isUninstallingBasic()) {
            $this->performCompleteCleanup(false);
        }
    }

    /**
     * @param bool $isPro
     * @return void
     */
    private function performCompleteCleanup(bool $isPro)
    {
        $this->deleteOptions($this->getBasicOptions());
        if ($isPro) {
            $this->deleteOptions($this->getProOptions());
        }

        $this->deleteTransients();
        $this->cleanupEmptyPreserveOptions();
        $this->clearCronEvents();
        $this->cleanupWpStagingDirectories();
    }

    /**
     * @return bool
     */
    private function isNetworkUninstall(): bool
    {
        return (is_multisite() && is_network_admin());
    }

    /**
     * @return bool
     */
    private function isUninstallingBasic(): bool
    {
        $pluginDirs = ['wp-staging', 'wp-staging-1'];
        return $this->isUninstallingPlugin($pluginDirs);
    }

    /**
     * @return bool
     */
    private function isUninstallingPro(): bool
    {
        $pluginDirs = ['wp-staging-pro', 'wp-staging-pro-1'];
        return $this->isUninstallingPlugin($pluginDirs);
    }

    /**
     * @param array $pluginDirs
     * @return bool
     */
    private function isUninstallingPlugin(array $pluginDirs): bool
    {
        return in_array(basename(__DIR__), $pluginDirs);
    }

    /**
     * @return bool
     */
    private function isProInstalled(): bool
    {
        // First try header-based detection (more robust)
        if ($this->isProInstalledByHeaders()) {
            return true;
        }

        // Fallback to file-based detection for backward compatibility
        $plugins = [
            'wp-staging-pro-1/wp-staging-pro.php',
            'wp-staging-pro/wp-staging-pro.php',
        ];
        foreach ($plugins as $plugin) {
            if ($this->isPluginInstalled($plugin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isBasicInstalled(): bool
    {
        // First try header-based detection (more robust)
        if ($this->isBasicInstalledByHeaders()) {
            return true;
        }

        // Fallback to file-based detection for backward compatibility
        $plugins = [
            'wp-staging-1/wp-staging.php',
            'wp-staging/wp-staging.php',
        ];
        foreach ($plugins as $plugin) {
            if ($this->isPluginInstalled($plugin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $pluginName
     * @return bool
     */
    private function isPluginInstalled($pluginName): bool
    {
        return file_exists( WP_PLUGIN_DIR . '/' . $pluginName );
    }

    /**
     * @param array $identifiers
     * @return bool
     */
    private function findPluginByIdentifiers(array $identifiers): bool
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins        = get_plugins();
        $searchCriteria = array_change_key_case($identifiers, CASE_LOWER);

        foreach ($plugins as $file => $data) {
            $name       = strtolower($data['Name'] ?? '');
            $slug       = strtolower(dirname($file));
            $mainFile   = strtolower(basename($file, '.php'));

            if (isset($searchCriteria['file']) && strtolower($searchCriteria['file']) === strtolower($file)) {
                return true;
            }

            if (isset($searchCriteria['slug']) && ($slug === $searchCriteria['slug'] || $mainFile === $searchCriteria['slug'])) {
                return true;
            }

            if (isset($searchCriteria['name']) && $name === strtolower($searchCriteria['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isBasicInstalledByHeaders(): bool
    {
        return $this->findPluginByIdentifiers([
            'slug' => 'wp-staging',
            'name' => 'WP Staging',
            'file' => 'wp-staging/wp-staging.php',
        ]);
    }

    /**
     * @return bool
     */
    private function isProInstalledByHeaders(): bool
    {
        return $this->findPluginByIdentifiers([
            'slug' => 'wp-staging-pro',
            'name' => 'WP Staging Pro',
            'file' => 'wp-staging-pro/wp-staging-pro.php',
        ]);
    }

    /**
     * @return array
     */
    private function getSettings(): array
    {
        return json_decode(json_encode(get_option('wpstg_settings', [])), true) ?? [];
    }

    /**
     * @return string[]
     */
    private function getBasicOptions(): array
    {
        return [
            'wpstg_settings',
            'wpstg_clone_settings',
            'wpstg_free_install_date',
            'wpstg_installDate',
            'wpstg_version',
            'wpstg_version_upgraded_from',
            'wpstg_free_upgrade_date',
            'wpstg_unique_identifier',
            'wpstg_is_staging_site',
            'wpstg_resave_permalinks_executed',
            'wpstg_rmpermalinks_executed',
            'wpstg_connection',
            'wpstg_staging_sites',
            'wpstg_existing_clones',
            'wpstg_existing_clones_beta',
            'wpstg_execute',
            'wpstg_emails_disabled',
            'wpstg_woo_scheduler_disabled',
            'wpstg_clone_excluded_files_list',
            'wpstg_clone_excluded_gd_files_list',
            'wpstg_freemius_notice',
            'wpstg_queue_table_structure_version',
            'wpstg_q_feature_detection_ajax_available',
            'wpstg_analytics_has_consent',
            'wpstg_analytics_modal_dismissed',
            'wpstg_analytics_notice_dismissed',
            'wpstg_analytics_consent_remind_me',
            'wpstg_default_color_mode',
            'wpstg_default_os_color_mode',
            'wpstg_last_backup_info',
            'wpstg_backups_retention',
            'wpstg_otps',
            'wpstg_access_token',
            'wpstg_disabled_notice',
            'wpstg_send_email_as_html',
        ];
    }

    /**
     * @return string[]
     */
    private function getProOptions(): array
    {
        return [
            'wpstgpro_version',
            'wpstgpro_version_upgraded_from',
            'wpstgpro_install_date',
            'wpstgpro_upgrade_date',
            'wpstg_license_key',
            'wpstg_license_status',
            'wpstg_pro_latest_version',
            'wpstg_googledrive',
            'wpstg_dropbox',
            'wpstg_one-drive',
            'wpstg_pcloud',
            'wpstg_amazons3',
            'wpstg_sftp',
            'wpstg_digitalocean-spaces',
            'wpstg_wasabi',
            'wpstg_generic-s3',
            'wpstg_backup_schedules',
            'wpstg_backup_schedules_send_error_report',
            'wpstg_backup_schedules_report_email',
            'wpstg_backup_schedules_send_slack_error_report',
            'wpstg_backup_schedules_report_slack_webhook',
            'wpstg_current_site_login_links',
            'wpstg_remote_sync_api_token',
            'wpstg_remote_sync_password',
        ];
    }

    /**
     * @return string[]
     */
    private function getAllTransients(): array
    {
        return [
            'wpstg_current_job',
            'wpstg_rest_url',
            'wpstg.run_daily',
            'wpstg_show_login_notice',
            'wpstg_user_logged_in_status',
            'wpstg_auto_login_failed',
            'wpstg_auto_login_failed_reason',
            'wpstg_failed_auto_login_attempts',
            'wpstg_otp_sent',
            'wpstg_otp_consecutive_failures',
            'wpstg_otp_locked',
            'wpstg_redirect_url',
            'wpstg_remote_sync_session',
            'wpstg_remote_sync_session_data',
            'wpstg_remote_sync_session_events_offset',
            'wpstg.queue.request.get_method',
            'is_invalid_backup_file_index',
            'wpstg_permalinks_do_purge',
            'wpstg_purge_litespeed_cache',
            'wpstg_activation_redirect',
            'wpstg_pro_activation_redirect',
            'wpstg_weekly_version_update',
            'wpstg_rate_limit_update_check',
            'wpstg_issue_report_submitted',
            'wpstg.backup.schedules.slack_report_sent',
            'wpstg_email_notification_access_token',
            'wpstg.directory_listing.last_checked',
        ];
    }

    /**
     * @param array $optionNames
     * @return void
     */
    private function deleteOptions(array $optionNames)
    {
        foreach ($optionNames as $optionName) {
            // Skip if this option should be preserved
            if (in_array($optionName, $this->preserveOptions, true)) {
                continue;
            }

            delete_option($optionName);
        }
    }

    /**
     * @return void
     */
    private function deleteTransients()
    {
        $transients = $this->getAllTransients();
        foreach ($transients as $transientName) {
            delete_transient($transientName);
        }
    }

    /**
     * @return void
     */
    private function cleanupEmptyPreserveOptions()
    {
        $this->cleanupEmptyOptions($this->preserveOptions);
    }

    /**
     * @param array $options
     * @param bool $isSiteOptions
     * @return void
     */
    private function cleanupEmptyOptions(array $options, bool $isSiteOptions = false)
    {
        foreach ($options as $option) {
            $value = $isSiteOptions ? get_site_option($option): get_option($option);
            if (empty($value)) {
                $isSiteOptions ? delete_site_option($option): delete_option($option);
            }
        }
    }

    /**
     * @return void
     */
    private function clearCronEvents()
    {
        // @see WPStaging\Core\Cron\Cron::ACTION_WEEKLY_EVENT
        wp_clear_scheduled_hook('wpstg_weekly_event');
    }

    /**
     * @return void
     */
    private function cleanupWpStagingDirectories()
    {
        $uploadsBase        = $this->getUploadsDirectory() . 'wp-staging/';
        $directoriesToClean = [
            $this->getWpContentDirectory() . 'wp-staging',
        ];

        // Delete wp-staging uploads dir if it does not contain .wpstg files
        if (!$this->isDirectoryContainsWpstgFiles($uploadsBase . 'backups')) {
            $directoriesToClean[] = $uploadsBase;
        } else {
            $directoriesToClean[] = $uploadsBase . 'cache';
            $directoriesToClean[] = $uploadsBase . 'logs';
            $directoriesToClean[] = $uploadsBase . 'tmp';
        }

        foreach ($directoriesToClean as $directory) {
            $this->deleteDirectoryRecursively($directory);
        }
    }

    /**
     * @param string $directory
     * @return void
     */
    private function deleteDirectoryRecursively(string $directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $absPath = trailingslashit(ABSPATH);
        if ($directory === $absPath || $directory === dirname($absPath)) {
            return;
        }

        foreach (new \DirectoryIterator($directory) as $item) {
            if ($item->isDot()) {
                continue;
            }

            $itemPath = $item->getPathname();
            if ($item->isDir()) {
                $this->deleteDirectoryRecursively($itemPath);
            } else {
                @unlink($itemPath);
            }
        }

        @rmdir($directory);
    }

    /**
     * @return void
     */
    private function deleteNetworkOptions()
    {
        delete_site_option('wpstg_license_key');
        delete_site_option('wpstg_license_status');
        delete_site_option('wpstgDisableLicenseNotice');
        $this->cleanupEmptyOptions($this->preserveOptions, true);
    }

    /**
     * @return string
     */
    private function getUploadsDirectory(): string
    {
        $uploadDir = wp_upload_dir();
        return trailingslashit($uploadDir['basedir']);
    }

    /**
     * @return string
     */
    private function getWpContentDirectory(): string
    {
        return trailingslashit(WP_CONTENT_DIR);
    }

    /**
     * @param string $backupsDir
     * @return bool
     */
    private function isDirectoryContainsWpstgFiles(string $backupsDir): bool
    {
        if (!is_dir($backupsDir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && strcasecmp($item->getExtension(), 'wpstg') === 0) {
                return true;
            }
        }

        return false;
    }
}

new Uninstall();
