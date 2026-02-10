<?php

namespace WPStaging\Backend\Modules;

use WPStaging\Backend\Upgrade\Upgrade;
use WPStaging\Backup\Ajax\FileList\ListableBackupsCollection;
use WPStaging\Core\Utils\Browser;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Multisite;
use WPStaging\Framework\Facades\Info;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Notifications\Notifications;
use WPStaging\Staging\Sites;
use WPStaging\Framework\SiteInfo;
use WPStaging\Framework\Database\WpOptionsInfo;
use WPStaging\Framework\Security\DataEncryption;
use WPStaging\Backup\BackupScheduler;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

/**
 * System Info
 * Generates system information for debugging and support
 */
class SystemInfo
{
    /**
     * @var string
     */
    const REMOVED_LABEL = '[REMOVED]';

    /**
     * @var string
     */
    const NOT_SET_LABEL = '[not set]';

    /**
     * @var bool
     */
    private $isMultiSite;

    /**
     * @var mixed|Database
     */
    private $database;

    /**
     * @var Urls
     */
    private $urlsHelper;

    /**
     * @var WpOptionsInfo
     */
    private $wpOptionsInfo;

    /**
     * @var bool
     */
    private $isEncodeProLicense = false;

    /** @var SiteInfo */
    private $siteInfo;

    /**
     * @var bool Enable structured data output
     */
    private $enableStructuredOutput = false;

    /**
     * @var array Structured data storage
     */
    private $structuredData = [];

    /**
     * @var string Current section name
     */
    private $currentSection = null;

    public function __construct()
    {
        $this->isMultiSite   = is_multisite();
        $this->urlsHelper    = WPStaging::make(Urls::class);
        $this->database      = WPStaging::make(Database::class);
        $this->wpOptionsInfo = WPStaging::make(WpOptionsInfo::class);
        $this->siteInfo      = WPStaging::make(SiteInfo::class);
    }

    public function __toString(): string
    {
        return $this->get();
    }

    public function setStructuredOutput(bool $enable = true)
    {
        $this->enableStructuredOutput = $enable;
        if ($enable) {
            $this->structuredData = [];
        }
    }

    public function getSections(): array
    {
        $this->setStructuredOutput(true);
        $this->get(); // This will populate structuredData
        return $this->getStructuredDataWithDisplayNames();
    }

    public function get(): string
    {
        $output  = $this->server();
        $output .= $this->php();
        $output .= $this->wp();
        $output .= $this->getMultisiteInfo();
        $output .= $this->wpstaging();
        $output .= $this->plugins();
        $output .= $this->muPlugins();
        $output .= $this->dropIns();
        $output .= $this->multiSitePlugins();
        $output .= $this->phpExtensions();
        $output .= $this->browser();
        $output .= PHP_EOL . "### End System Info ###";

        return $output;
    }

    public function header(string $string): string
    {
        $this->currentSection = $string;
        return PHP_EOL . "### {$string} ###" . PHP_EOL . PHP_EOL;
    }

    /**
     * @param string|array $value
     */
    public function info(string $title, $value): string
    {
        // Store structured data if enabled
        if ($this->enableStructuredOutput) {
            if (!isset($this->structuredData[$this->currentSection])) {
                $this->structuredData[$this->currentSection] = [];
            }

            $this->structuredData[$this->currentSection][] = [
                'label' => rtrim($title, ':'),
                'value' => $value,
            ];
        }

        return str_pad($title, 56, ' ', STR_PAD_RIGHT) . print_r($value, true) . PHP_EOL;
    }

    /**
     * Get structured data with display names
     *
     * @return array Structured data with section display names as keys
     */
    public function getStructuredDataWithDisplayNames(): array
    {
        $data = [];
        foreach ($this->structuredData as $sectionId => $items) {
            $displayName        = SystemInfoParser::getDisplayName($sectionId);
            $data[$displayName] = $items;
        }

        return $data;
    }

    /**
     * WordPress Configuration
     * @return string
     */
    public function wp(): string
    {
        // WordPress Environment
        $this->currentSection = SystemInfoParser::SECTIONS['WORDPRESS_ENVIRONMENT']['id'];
        $output  = $this->info("Site Type:", ($this->isMultiSite) ? 'Multi Site' : 'Single Site');
        $output .= $this->info("WordPress Version:", get_bloginfo("version"));
        $output .= $this->info("Installed in Subdirectory:", ($this->isSubDir() ? 'Yes' : 'No'));
        $output .= $this->info("WP_DEBUG:", (defined("WP_DEBUG")) ? (WP_DEBUG ? "Enabled" : "Disabled") : self::NOT_SET_LABEL);
        $output .= $this->info("WPLANG:", (defined("WPLANG") && WPLANG) ? WPLANG : "en_US");
        $output .= $this->wpRemotePost();

        // URLs & Paths
        $this->currentSection = SystemInfoParser::SECTIONS['URLS_PATHS']['id'];
        $output .= $this->info("Site URL:", site_url());
        $output .= $this->info("Home URL:", $this->urlsHelper->getHomeUrl());
        $output .= $this->info("Home Path:", get_home_path());
        $output .= $this->info("ABSPATH:", ABSPATH);
        $permissions = fileperms(ABSPATH);
        if ($permissions !== false) {
            $output .= $this->info("ABSPATH Fileperms:", (string)$permissions);
            $permissions = substr(sprintf('%o', $permissions), -4);
            $output .= $this->info("ABSPATH Permissions:", $permissions);
        } else {
            $output .= $this->info("ABSPATH Permissions:", "N/A");
        }

        $absPathStat = stat(ABSPATH);
        if (!$absPathStat) {
            $absPathStat = "";
        }

        if ($this->enableStructuredOutput) {
            $output .= $this->info("ABSPATH Stat:", $absPathStat);
        } else {
            $output .= $this->info("ABSPATH Stat:", json_encode($absPathStat));
        }

        // WordPress Directories
        $this->currentSection = SystemInfoParser::SECTIONS['WORDPRESS_DIRECTORIES']['id'];
        $output .= $this->constantInfo('WP_CONTENT_DIR');
        $output .= $this->constantInfo('WP_PLUGIN_DIR');
        $output .= $this->info("Is wp-content Symlink:", is_link(WP_CONTENT_DIR) ? 'Yes' : 'No');
        $output .= $this->info("Symlinks Disabled:", Info::canUse('symlink') === false ? 'Yes' : 'No');
        if (is_link(WP_CONTENT_DIR)) {
            $output .= $this->info("wp-content link target:", readlink(WP_CONTENT_DIR));
            $output .= $this->info("wp-content realpath:", realpath(WP_CONTENT_DIR));
        }

        $output .= $this->constantInfo('WP_TEMP_DIR');

        // Media & Uploads
        $this->currentSection = SystemInfoParser::SECTIONS['MEDIA_UPLOADS']['id'];
        $output .= $this->constantInfo('UPLOADS');
        $uploads = wp_upload_dir();
        $output .= $this->info("Uploads Base Dir:", $uploads['basedir']);
        $output .= $this->info("Uploads URL:", $uploads['url']);
        $output .= $this->info("Uploads Path:", $uploads['path']);
        $output .= $this->info("Uploads Subdir:", $uploads['subdir']);
        $output .= $this->info("Uploads Base URL:", $uploads['baseurl']);
        $output .= $this->info("UPLOADS Constant:", (defined("UPLOADS")) ? UPLOADS : self::NOT_SET_LABEL);
        $output .= $this->info("UPLOAD_PATH (wp-config.php):", (defined("UPLOAD_PATH")) ? UPLOAD_PATH : self::NOT_SET_LABEL);
        $tableName  = $this->database->getPrefix() . 'options';
        $output .= $this->info("upload_path ($tableName):", get_option("upload_path") ?: self::NOT_SET_LABEL);

        // WordPress Memory Settings
        $this->currentSection = SystemInfoParser::SECTIONS['WORDPRESS_MEMORY_SETTINGS']['id'];
        $output .= $this->constantInfo('WP_MEMORY_LIMIT');
        $output .= $this->constantInfo('WP_MAX_MEMORY_LIMIT');

        // Filesystem & Permissions
        $this->currentSection = SystemInfoParser::SECTIONS['FILESYSTEM_PERMISSIONS']['id'];
        $output .= $this->constantInfo('FS_CHMOD_DIR');
        $output .= $this->constantInfo('FS_CHMOD_FILE');

        // Theme & Permalinks
        $this->currentSection = SystemInfoParser::SECTIONS['THEME_PERMALINKS']['id'];
        $settings = (object)get_option('wpstg_settings', []);
        $output  .= $this->info("Active Theme:", $this->theme());
        $output  .= $this->info("Permalink Structure:", get_option("permalink_structure") ?: "Default");
        $output  .= $this->info("Keep Permalinks:", isset($settings->keepPermalinks) ? $settings->keepPermalinks : self::NOT_SET_LABEL);

        // WordPress Cron Jobs
        $this->currentSection = SystemInfoParser::SECTIONS['WORDPRESS_CRON_JOBS']['id'];
        $cron    = get_option('cron', []);
        $output .= $this->info("WP-Cron Enabled:", (!defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON) ? 'Yes' : 'No');
        $output .= $this->info("Scheduled Events:", !empty($cron) ? count($cron) . ' events' : 'No events');
        if ($this->enableStructuredOutput) {
            $output .= $this->info("Wordpress cron:", $cron);
        } else {
            $output .= $this->info("Wordpress cron:", wp_json_encode($cron));
        }

        $backupSchedules = get_option('wpstg_backup_schedules', []);
        if (!empty($backupSchedules)) {
            $output .= $this->info("Backup Schedule:", $backupSchedules);
        } else {
            $output .= $this->info('Backup Schedule:', self::NOT_SET_LABEL);
        }

        return $output;
    }

    /**
     * Theme Information
     * @return string
     */
    public function theme(): string
    {
        // Versions earlier than 3.4
        if (get_bloginfo("version") < "3.4") {
            $themeData = get_theme_data(get_stylesheet_directory() . "/style.css");
            return "{$themeData["Name"]} (v{$themeData["Version"]})";
        }

        $themeData = wp_get_theme();
        return "{$themeData->Name} (v{$themeData->Version})";
    }

    /**
     * Multisite information
     * @return string
     */
    private function getMultisiteInfo(): string
    {
        if (!$this->isMultiSite) {
            return '';
        }

        $this->currentSection = SystemInfoParser::SECTIONS['MULTISITE']['id'];
        $multisite = new Multisite();

        $output = $this->info("Multisite:", "Yes");
        $output .= $this->info("Multisite Blog ID:", (string)get_current_blog_id());
        $output .= $this->info("MultiSite URL:", $multisite->getHomeURL());
        $output .= $this->info("MultiSite URL without scheme:", $multisite->getHomeUrlWithoutScheme());
        $output .= $this->info("MultiSite is Main Site:", is_main_site() ? 'Yes' : 'No');

        $output .= $this->constantInfo('SUBDOMAIN_INSTALL');
        $output .= $this->constantInfo('DOMAIN_CURRENT_SITE');
        $output .= $this->constantInfo('PATH_CURRENT_SITE');
        $output .= $this->constantInfo('SITE_ID_CURRENT_SITE');
        $output .= $this->constantInfo('BLOG_ID_CURRENT_SITE');

        $networkSites = get_sites();
        if ($this->enableStructuredOutput) {
            $output .= $this->info("Network Sites", $networkSites);
            return $output;
        }

        $output .= PHP_EOL . $this->info("Network Sites:", count($networkSites)) . PHP_EOL;
        foreach ($networkSites as $site) {
            $siteDetails = get_blog_details($site->blog_id);
            if (!$siteDetails) {
                continue;
            }

            $output .= $this->info("Blog ID:", $site->blog_id);
            $output .= $this->info("Home URL:", get_home_url($site->blog_id));
            $output .= $this->info("Site URL:", get_site_url($site->blog_id));
            $output .= $this->info("Domain:", $site->domain);
            $output .= $this->info("Path:", $site->path);
            $output .= PHP_EOL;
        }

        return $output;
    }

    /**
     * Wp Staging plugin Information
     * @return string
     */
    public function wpstaging(): string
    {
        $settings                               = (object)get_option('wpstg_settings', []);
        $optionBackupScheduleErrorReport        = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_ERROR_REPORT);
        $optionBackupScheduleWarningReport      = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_WARNING_REPORT);
        $optionBackupScheduleGeneralReport      = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_GENERAL_REPORT);
        $optionBackupScheduleReportEmail        = get_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL);
        $optionBackupScheduleSlackErrorReport   = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT);
        $optionBackupScheduleReportSlackWebhook = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK);
        $wpStagingFreeVersion                   = wpstgGetPluginData('wp-staging.php');
        $output                                 = PHP_EOL . "## WP Staging ##" . PHP_EOL . PHP_EOL;

        // WP Staging – Plugin Information
        $this->currentSection = SystemInfoParser::SECTIONS['WP_STAGING_PLUGIN_INFO']['id'];
        $output .= $this->info("Pro License Key:", $this->getLicenseKey() ?: self::NOT_SET_LABEL);
        $output .= $this->info("Pro Version:", get_option('wpstgpro_version', self::NOT_SET_LABEL));
        $output .= $this->info("Pro Install Date:", get_option('wpstgpro_install_date', self::NOT_SET_LABEL));
        $output .= $this->info("Updated from Pro Version:", get_option('wpstgpro_version_upgraded_from') ?: self::NOT_SET_LABEL);
        $output .= $this->info("Pro Update Date:", get_option('wpstgpro_upgrade_date', self::NOT_SET_LABEL));
        $output .= $this->info("Free Version:", empty($wpStagingFreeVersion['Version']) ? self::NOT_SET_LABEL : $wpStagingFreeVersion['Version']);
        $output .= $this->info("Free Install Date:", get_option(Upgrade::OPTION_INSTALL_DATE, self::NOT_SET_LABEL));
        $output .= $this->info("Free Update Date:", get_option(Upgrade::OPTION_UPGRADE_DATE, self::NOT_SET_LABEL));
        $output .= $this->info("Updated from Free Version:", get_option('wpstg_version_upgraded_from') ?: self::NOT_SET_LABEL);
        $output .= $this->info("Free or Pro Install Date (legacy):", get_option('wpstg_installDate', self::NOT_SET_LABEL));
        $output .= $this->info("Is Staging Site:", $this->siteInfo->isStagingSite() ? 'Yes' : 'No');

        $this->currentSection = SystemInfoParser::SECTIONS['WP_STAGING_BACKUP_STATUS']['id'];
        $output .= $this->getBackupDetails();
        $output .= $this->getQueueInfo();

        // WP Staging – Performance & Limits
        $this->currentSection = SystemInfoParser::SECTIONS['WP_STAGING_PERFORMANCE']['id'];
        $output .= $this->info("DB Query Limit:", $this->getSettingValue($settings, 'queryLimit', true));
        $output .= $this->info("Search & Replace Limit:", $this->getSettingValue($settings, 'querySRLimit', true));
        $output .= $this->info("File Copy Limit:", $this->getSettingValue($settings, 'fileLimit', true));
        $output .= $this->info("Maximum File Size:", $this->getSettingValue($settings, 'maxFileSize'));
        $output .= $this->info("File Copy Batch Size:", $this->getSettingValue($settings, 'batchSize'));
        $cpuLoad = $this->getSettingValue($settings, 'cpuLoad');
        $output .= $this->info("CPU Load Priority:", $cpuLoad !== self::NOT_SET_LABEL ? ucfirst(strtolower($cpuLoad)) : $cpuLoad);
        $output .= $this->info("Optimizer Enabled:", isset($settings->optimizer) && $settings->optimizer ? 'Yes' : 'No');
        $output .= $this->info("Backup Compression:", isset($settings->enableCompression) ? ($settings->enableCompression ? 'On' : 'Off') : self::NOT_SET_LABEL);
        $output .= $this->info("Debug Mode Enabled:", isset($settings->debugMode) && $settings->debugMode ? 'Yes' : 'No');
        // WP Staging – Access & Permissions
        $this->currentSection = SystemInfoParser::SECTIONS['WP_STAGING_ACCESS']['id'];
        $userRoles = isset($settings->userRoles) ? $settings->userRoles : [];
        if (is_array($userRoles) && !empty($userRoles)) {
            $rolesList = implode(', ', array_map('ucfirst', $userRoles));
        } else {
            $rolesList = self::NOT_SET_LABEL;
        }

        $output .= $this->info("Allowed Roles:", $rolesList);
        $usersWithAccess = isset($settings->usersWithStagingAccess) ? $settings->usersWithStagingAccess : [];
        if (is_array($usersWithAccess) && !empty($usersWithAccess)) {
            $usersList = implode(', ', $usersWithAccess);
        } else {
            $usersList = 'Not listed';
        }

        $output .= $this->info("Users with Staging Access:", $usersList);
        $output .= $this->info("Delete Data on Uninstall:", isset($settings->unInstallOnDelete) ? ($settings->unInstallOnDelete ? 'Yes' : 'No') : self::NOT_SET_LABEL);
        $output .= $this->info("Send Usage Information:", !empty(get_option('wpstg_analytics_has_consent')) ? 'true' : 'false');
        $output .= $this->info("Send Backup Errors via Email:", $this->formatBooleanOption($optionBackupScheduleErrorReport));
        $output .= $this->info("Send Backup Warnings via Email:", $this->formatBooleanOption($optionBackupScheduleWarningReport));
        $output .= $this->info("Send Backup General Report via Email:", $this->formatBooleanOption($optionBackupScheduleGeneralReport));
        $output .= $this->info("Email Address:", !empty($optionBackupScheduleReportEmail) && is_email($optionBackupScheduleReportEmail) ? $optionBackupScheduleReportEmail : self::NOT_SET_LABEL);
        $output .= $this->info("Send Backup Errors via Slack Webhook:", $this->formatBooleanOption($optionBackupScheduleSlackErrorReport) === 'true' ? 'true' : (WPStaging::isPro() ? 'false' : self::NOT_SET_LABEL));
        $output .= $this->info("Slack Webhook URL:", WPStaging::isPro() && !empty($optionBackupScheduleReportSlackWebhook) ? self::REMOVED_LABEL : self::NOT_SET_LABEL);

        $this->currentSection = SystemInfoParser::SECTIONS['WP_STAGING_STORAGE_PROVIDER']['id'];
        // Use consolidated storage provider configuration
        $parser            = WPStaging::make(SystemInfoParser::class);
        $storageProviders = $parser->getStorageProvidersForSystemInfo();
        foreach ($storageProviders as $provider) {
            $output .= $this->formatStorageSettings($provider['optionName'], $provider['title']);
        }

        $this->currentSection = SystemInfoParser::SECTIONS['WP_STAGING_EXISTING_SITES']['id'];
        if (!$this->enableStructuredOutput) {
            $output .= PHP_EOL . "-- Existing Staging Sites" . PHP_EOL . PHP_EOL;
        }

        $stagingSites = get_option(Sites::STAGING_SITES_OPTION, []);
        if (is_array($stagingSites)) {
            foreach ($stagingSites as $key => $clone) {
                $path = !empty($clone['path']) ? $clone['path'] : self::NOT_SET_LABEL;
                if ($this->enableStructuredOutput) {
                    $clone['wpVersion'] = $this->getStagingWpVersion($path);
                    $stagingSites[$key] = $clone;
                    continue;
                }

                $output .= $this->info("Number:", isset($clone['number']) ? $clone['number'] : self::NOT_SET_LABEL);
                $output .= $this->info("directoryName:", isset($clone['directoryName']) ? $clone['directoryName'] : self::NOT_SET_LABEL);
                $output .= $this->info("Path:", $path);
                $output .= $this->info("URL:", isset($clone['url']) ? $clone['url'] : self::NOT_SET_LABEL);
                $output .= $this->info("DB Prefix:", isset($clone['prefix']) ? $clone['prefix'] : self::NOT_SET_LABEL);
                $output .= $this->info("DB Prefix wp-config.php:", $this->getStagingPrefix($clone));
                $output .= $this->info("WP STAGING Version:", isset($clone['version']) ? $clone['version'] : self::NOT_SET_LABEL);
                $output .= $this->info("WP Version:", $this->getStagingWpVersion($path)) . PHP_EOL . PHP_EOL;
            }
        }

        $stagingSites = $this->sanitizeSitePasswords(is_array($stagingSites) ? $stagingSites : []);
        $stagingSitesOptionBackup = $this->sanitizeSitePasswords((array)get_option(Sites::BACKUP_STAGING_SITES_OPTION, []));

        $output .= $this->info(Sites::STAGING_SITES_OPTION . ": ", serialize($stagingSites));
        $output .= $this->info(Sites::BACKUP_STAGING_SITES_OPTION . ": ", serialize($stagingSitesOptionBackup));
        return $output;
    }

    public function getWpStagingVersion(): string
    {
        if (defined('WPSTGPRO_VERSION')) {
            return 'Pro ' . WPSTGPRO_VERSION;
        }

        if (defined('WPSTG_VERSION')) {
            return WPSTG_VERSION;
        }

        return 'unknown';
    }

    /**
     * Browser Information
     * @return string
     */
    public function browser(): string
    {
        $this->currentSection = SystemInfoParser::SECTIONS['CLIENT_BROWSER_INFO']['id'];
        $output = $this->header("Client / Browser Information");
        $browser = new Browser();
        $browserInfo = (string)$browser;

        // Parse browser info into structured format if enabled
        // Browser class returns formatted text with str_pad format: "Label:                    Value"
        if ($this->enableStructuredOutput) {
            $lines = explode("\n", trim($browserInfo));
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Browser info uses str_pad format: "Label:                    Value"
                if (preg_match('/^(.{1,56})\s+(.+)$/', $line, $matches)) {
                    $label = trim($matches[1]);
                    $value = trim($matches[2]);
                    $this->info($label, $value);
                } else {
                    $this->info('', $line);
                }
            }
        }

        $output .= $browserInfo;
        return $output;
    }

    /**
     * Check wp_remote_post() functionality
     * @return string
     */
    public function wpRemotePost(): string
    {
        // Make sure wp_remote_post() is working
        $wpRemotePost = "does not work";

        // Check if has valid IP address
        // to avoid error on php-wasm
        $hostName = 'www.paypal.com';
        $hostIp   = gethostbyname($hostName);
        if (preg_match('@\.0$@', $hostIp)) {
            return $this->info("wp_remote_post():", $wpRemotePost);
        }

        // Send request
        $response = wp_remote_post(
            "https://" . $hostName . "/cgi-bin/webscr",
            [
                "sslverify"  => false,
                "timeout"    => 60,
                "user-agent" => "WPSTG/" . WPStaging::getVersion(),
                "body"       => ["cmd" => "_notify-validate"],
            ]
        );

        // Validate it worked
        if (!is_wp_error($response) && $response["response"]["code"] >= 200 && $response["response"]["code"] < 300) {
            $wpRemotePost = "works";
        }

        return $this->info("wp_remote_post():", $wpRemotePost);
    }

    /**
     * List of Active Plugins
     * @param array $allAvailablePlugins
     * @param array $activePlugins
     * @return string
     */
    public function activePlugins(array $allAvailablePlugins, array $activePlugins): string
    {
        $this->currentSection = SystemInfoParser::SECTIONS['PLUGINS_OVERVIEW']['id'];
        $output = $this->header("Active Plugins");

        foreach ($allAvailablePlugins as $path => $plugin) {
            if (!in_array($path, $activePlugins)) {
                continue;
            }

            $output .= $this->info($plugin["Name"] . ":", $plugin["Version"]);
        }

        return $output;
    }

    /**
     * List of Inactive Plugins
     * @param array $allAvailablePlugins
     * @param array $activePlugins
     * @return string
     */
    public function inactivePlugins(array $allAvailablePlugins, array $activePlugins): string
    {
        $this->currentSection = SystemInfoParser::SECTIONS['PLUGINS_OVERVIEW']['id'];
        if ($this->isMultiSite) {
            $output = $this->header("Inactive Plugins (Includes this and other sites in the same network)");
        } else {
            $output = $this->header("Inactive Plugins");
        }

        foreach ($allAvailablePlugins as $path => $plugin) {
            if (in_array($path, $activePlugins)) {
                continue;
            }

            $output .= $this->info($plugin["Name"] . ":", $plugin["Version"]);
        }

        return $output;
    }

    /**
     * Get list of active and inactive plugins
     * @return string
     */
    public function plugins(): string
    {
        // Get plugins and active plugins
        $allAvailablePlugins = get_plugins();
        $activePlugins       = get_option("active_plugins", []);

        $activePluginsToGetInactive = $activePlugins;
        if ($this->isMultiSite) {
            $networkActivePlugins       = array_keys(get_site_option("active_sitewide_plugins", []));
            $activePluginsToGetInactive = array_merge($activePluginsToGetInactive, $networkActivePlugins);
        }

        // Active plugins
        $output = $this->activePlugins($allAvailablePlugins, $activePlugins);
        $output .= $this->inactivePlugins($allAvailablePlugins, $activePluginsToGetInactive);

        return $output;
    }

    /**
     * Multisite Plugins
     * @return string
     */
    public function multiSitePlugins(): string
    {
        if (!$this->isMultiSite) {
            return '';
        }

        $this->currentSection = SystemInfoParser::SECTIONS['PLUGINS_OVERVIEW']['id'];
        $output = $this->header("Active Network Plugins (Includes this and other sites in the same network)");

        $plugins       = wp_get_active_network_plugins();
        $activePlugins = get_site_option("active_sitewide_plugins", []);

        foreach ($plugins as $pluginPath) {
            $pluginBase = plugin_basename($pluginPath);

            if (!array_key_exists($pluginBase, $activePlugins)) {
                continue;
            }

            $plugin = get_plugin_data($pluginPath);

            $output .= "{$plugin["Name"]}: {$plugin["Version"]}" . PHP_EOL;
        }

        unset($plugins, $activePlugins);

        return $output;
    }

    /**
     * Server Information
     * @return string
     */
    public function server(): string
    {
        // Server & Operating System
        $this->currentSection = SystemInfoParser::SECTIONS['SERVER_AND_OS']['id'];
        $output = $this->header("Server & Operating System");
        $output .= $this->info("Web Server:", isset($_SERVER["SERVER_SOFTWARE"]) ? Sanitize::sanitizeString($_SERVER["SERVER_SOFTWARE"]) : '');
        $output .= $this->info("OS Architecture:", $this->siteInfo->getOSArchitecture());
        $output .= $this->info("Server User:", $this->getPHPUser());

        // Reference: https://dev.mysql.com/doc/refman/9.1/en/identifier-case-sensitivity.html
        switch ($this->database->getLowerTablesNameSettings()) {
            case '0':
                $lowerTablesNameSettings = 'case-sensitive';
                break;
            case '1':
            case '2':
                $lowerTablesNameSettings = 'case-insensitive';
                break;
            default:
                $lowerTablesNameSettings = 'N/A';
        }

        // Database (MySQL / MariaDB)
        $this->currentSection = SystemInfoParser::SECTIONS['DATABASE_MYSQL_MARIADB']['id'];
        $output .= $this->info("Database Type:", $this->database->getServerType());
        $output .= $this->info("Version:", $this->database->getSqlVersion($compact = true));
        $output .= $this->info("Full Version:", $this->database->getSqlVersion());
        $output .= $this->info("Database Name:", $this->database->getWpdb()->dbname);
        $output .= $this->info("Table Prefix:", $this->getTablePrefix());
        $output .= $this->info("lower_case_table_names:", $lowerTablesNameSettings);
        $output .= $this->getPrimaryKeyInfo();

        // PHP Environment
        $this->currentSection = SystemInfoParser::SECTIONS['PHP_ENVIRONMENT']['id'];
        $output .= $this->info("PHP Version:", PHP_VERSION);
        $output .= $this->info("PHP Architecture:", $this->siteInfo->getPhpArchitecture());
        $output .= $this->info("PHP Safe Mode:", ($this->isSafeModeEnabled() ? "Enabled" : "Disabled"));
        $displayErrors = ini_get("display_errors");
        $output .= $this->info("display_errors:", ($displayErrors) ? "On ({$displayErrors})" : "N/A");

        return $output;
    }

    /**
     * @return string
     */
    public function getMySqlServerType(): string
    {
        return $this->database->getServerType();
    }

    /**
     * @return string
     */
    public function getMySqlFullVersion(): string
    {
        return $this->database->getSqlVersion();
    }

    /**
     * @return string
     */
    public function getMySqlVersionCompact(): string
    {
        return $this->database->getSqlVersion($compact = true);
    }

    /**
     * @return string
     */
    public function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * @return string
     */
    public function getWebServerInfo(): string
    {
        return isset($_SERVER["SERVER_SOFTWARE"]) ? Sanitize::sanitizeString($_SERVER["SERVER_SOFTWARE"]) : '';
    }

    /**
     * PHP Configuration
     * @return string
     */
    public function php(): string
    {
        // PHP Limits
        $this->currentSection = SystemInfoParser::SECTIONS['PHP_LIMITS']['id'];
        $memoryLimit = ini_get("memory_limit");
        $output = $this->info("memory_limit:", $memoryLimit . ' (' . number_format(wp_convert_hr_to_bytes($memoryLimit)) . ' bytes)');
        $output .= $this->info("max_execution_time:", ini_get("max_execution_time"));
        $output .= $this->info("max_input_vars:", ini_get("max_input_vars"));
        $output .= $this->info("upload_max_filesize:", ini_get("upload_max_filesize"));
        $output .= $this->info("post_max_size:", ini_get("post_max_size"));

        return $output;
    }

    /**
     * @return string
     */
    public function getPHPUser(): string
    {

        $user = '';

        if (extension_loaded('posix') && function_exists('posix_getpwuid')) {
            $file = WPSTG_PLUGIN_DIR . 'Core/WPStaging.php';
            $user = posix_getpwuid(fileowner($file));
            return isset($user['name']) ? $user['name'] : 'can not detect PHP user name';
        }

        if (function_exists('exec') && in_array('exec', explode(',', ini_get('disable_functions')))) {
            $user = exec('whoami');
        }

        return empty($user) ? 'can not detect PHP user name' : $user;
    }

    /**
     * Check if PHP is on Safe Mode
     * @return bool
     */
    public function isSafeModeEnabled(): bool
    {
        return (
            version_compare(PHP_VERSION, "5.4.0", '<') &&
            // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
            @ini_get("safe_mode")
        );
    }

    /**
     * Checks if function exists or not
     * @param string $functionName
     * @return string
     */
    public function isSupported(string $functionName): string
    {
        return (function_exists($functionName)) ? "Supported" : "Not Supported";
    }

    /**
     * Checks if class or extension is loaded / exists to determine if it is installed or not
     * @param string $name
     * @param bool $isClass
     * @return string
     */
    public function isInstalled(string $name, bool $isClass = true): string
    {
        if ($isClass === true) {
            return (class_exists($name)) ? "Installed" : "Not Installed";
        } else {
            return (extension_loaded($name)) ? "Installed" : "Not Installed";
        }
    }

     /**
     * Gets Installed Important PHP Extensions
     * @return string
     */
    public function phpExtensions(): string
    {
        // Important PHP Extensions
        $version = function_exists('curl_version') ? curl_version() : ['version' => 'Error: not available', 'ssl_version' => 'Error: not available', 'host' => 'Error: not available', 'protocols' => [], 'features' => []];

        $bitfields = [
            'CURL_VERSION_IPV6',
            'CURL_VERSION_KERBEROS4',
            'CURL_VERSION_SSL',
            'CURL_VERSION_LIBZ',
        ];

        $this->currentSection = SystemInfoParser::SECTIONS['CURL_ENVIRONMENT']['id'];
        $output = $this->header("cURL Environment");

        $output .= $this->info("cURL Support:", $this->isSupported("curl_init") ? 'Yes' : 'No');
        $output .= $this->info("cURL Version:", $version['version']);
        $output .= $this->info("cURL Host:", $version['host']);
        $output .= $this->info("SSL Library:", $version['ssl_version']);
        $this->currentSection = SystemInfoParser::SECTIONS['CURL_FEATURES']['id'];
        foreach ($bitfields as $feature) {
            $output .= $this->info($feature . ":", (defined($feature) && $version['features'] & constant($feature) ? 'Supported' : 'Not Supported'));
        }

        $httpProtocols  = ['http', 'https', 'ftp', 'ftps', 'file', 'dict', 'gopher'];
        $emailProtocols = ['imap', 'imaps', 'pop3', 'pop3s', 'smtp', 'smtps'];
        $allProtocols = $version['protocols'];

        $httpProtocolsFound  = array_values(array_intersect($allProtocols, $httpProtocols));
        $emailProtocolsFound = array_values(array_intersect($allProtocols, $emailProtocols));

        $otherProtocols = array_values(array_diff(
            $allProtocols,
            $httpProtocols,
            $emailProtocols
        ));
        $this->currentSection = SystemInfoParser::SECTIONS['SUPPORTED_PROTOCOLS']['id'];
        if (!empty($httpProtocolsFound)) {
            $output .= $this->info('HTTP Protocols:', implode(', ', $httpProtocolsFound));
        }

        if (!empty($emailProtocolsFound)) {
            $output .= $this->info('Email Protocols:', implode(', ', $emailProtocolsFound));
        }

        if (!empty($otherProtocols)) {
            $output .= $this->info('Other Protocols:', implode(', ', $otherProtocols));
        }

        $this->currentSection = SystemInfoParser::SECTIONS['PHP_NETWORK_EXTENSIONS']['id'];
        $output .= $this->info("fsockopen:", $this->isSupported("fsockopen") ? 'Supported' : 'Not Supported');
        $output .= $this->info("SOAP Client:", $this->isInstalled("SoapClient"));
        $output .= $this->info("Suhosin:", $this->isInstalled("suhosin", false));

        return $output;
    }

    /**
     * Check if WP is installed in subdir
     * @return bool
     */
    private function isSubDir(): bool
    {
        // Compare names without scheme to bypass cases where siteurl and home have different schemes http / https
        // This is happening much more often than you would expect
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home    = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

        if ($home !== $siteurl) {
            return true;
        }

        return false;
    }

    /**
     * Try to get the staging prefix from wp-config.php of staging site
     * @param array $clone
     * @return string
     */
    private function getStagingPrefix(array $clone = []): string
    {
        // Throw error
        $path = ABSPATH . $clone['directoryName'] . DIRECTORY_SEPARATOR . "wp-config.php";

        if (!file_exists($path)) {
            return 'File does not exist in: ' . $path;
        }

        if (($content = @file_get_contents($path)) === false) {
            return 'Can\'t find staging wp-config.php';
        } else {
            // Get prefix from wp-config.php
            //preg_match_all("/table_prefix\s*=\s*'(\w*)';/", $content, $matches);
            preg_match("/table_prefix\s*=\s*'(\w*)';/", $content, $matches);
            //wp_die(var_dump($matches));

            if (!empty($matches[1])) {
                return $matches[1];
            } else {
                return 'No table_prefix in wp-config.php';
            }
        }
    }

    /**
     * Get staging site wordpress version number
     * @param string $path
     * @return string
     */
    private function getStagingWpVersion(string $path): string
    {

        if ($path === self::NOT_SET_LABEL) {
            return "Error: Cannot detect WP version";
        }

        // Get version number of wp staging
        $file = trailingslashit($path) . 'wp-includes/version.php';

        if (!file_exists($file)) {
            return "Error: Cannot detect WP version. File does not exist: $file";
        }

        $version = @file_get_contents($file);

        $versionStaging = empty($version) ? 'unknown' : $version;

        preg_match("/\\\$wp_version.*=.*'(.*)';/", $versionStaging, $matches);

        if (empty($matches[1])) {
            return "Error: Cannot detect WP version";
        }

        return $matches[1];
    }

    /**
     * @param $key
     * @param $value
     * @return mixed|string
     */
    private function removeCredentials($key, $value)
    {
        $protectedFields = ['accessToken', 'refreshToken', 'accessKey', 'secretKey', 'password', 'passphrase'];
        if (!empty($value) && in_array($key, $protectedFields)) {
            return self::REMOVED_LABEL;
        }

        return empty($value) ? self::NOT_SET_LABEL : $value;
    }

    /**
     * @return string
     */
    private function getQueueInfo(): string
    {
        $output = '';

        /** @var Queue */
        $queue = WPStaging::make(Queue::class);

        $output .= $this->info("Backup All Actions in DB:", (string)$queue->count());
        $output .= $this->info("Pending Actions:", (string)$queue->count(Queue::STATUS_READY));
        $output .= $this->info("Processing Actions:", (string)$queue->count(Queue::STATUS_PROCESSING));
        $output .= $this->info("Completed Actions:", (string)$queue->count(Queue::STATUS_COMPLETED));
        $output .= $this->info("Failed Actions:", (string)$queue->count(Queue::STATUS_FAILED));

        return $output;
    }

    /**
     * @return string
     */
    private function getTablePrefix(): string
    {
        $prefix = $this->database->getPrefix();
        $length = strlen($prefix);
        $status = ($length > 16) ? "ERROR: Too long" : "Acceptable";
        return $prefix . ' (Length: ' . $length . ' — ' . $status . ')';
    }

    /**
     * @return string
     */
    private function getBackupDetails(): string
    {
        $backups = WPStaging::make(ListableBackupsCollection::class)->getListableBackups();

        $output = $this->info("Number of Backups:", (string)count($backups));

        $totalBackupSize = 0;
        foreach ($backups as $backup) {
            $totalBackupSize += (float)$backup->size;
        }

        $output .= $this->info("Total Backups Size:", esc_html((string)size_format($totalBackupSize, 2)));

        return $output;
    }

    /**
     * @param string $constantName
     * @return string
     */
    protected function constantInfo(string $constantName): string
    {
        if (!defined($constantName)) {
            return $this->info($constantName . ':', self::NOT_SET_LABEL);
        }

        $constantValue = constant($constantName);
        if (is_bool($constantValue)) {
            $constantValue = $constantValue ? 'Yes' : 'No';
        }

        return $this->info($constantName . ':', $constantValue);
    }

    /**
     * Get setting value with NOT_SET_LABEL fallback
     *
     * @param object $settings Settings object
     * @param string $property Property name
     * @param bool $format Whether to number_format the value
     * @return string|int
     */
    protected function getSettingValue($settings, string $property, bool $format = false)
    {
        if (!isset($settings->$property)) {
            return self::NOT_SET_LABEL;
        }

        return $format ? number_format($settings->$property) : $settings->$property;
    }

    /**
     * Format boolean option value for display
     *
     * @param mixed $option Option value
     * @return string 'true' or 'false'
     */
    protected function formatBooleanOption($option): string
    {
        return !empty($option) && $option === 'true' ? 'true' : 'false';
    }

    /**
     * Sanitize passwords in staging site data
     *
     * @param array $sites Array of staging sites
     * @return array Sanitized sites with passwords replaced
     */
    protected function sanitizeSitePasswords(array $sites): array
    {
        foreach ($sites as $key => $clone) {
            if (!empty($clone['databasePassword'])) {
                $sites[$key]['databasePassword'] = self::REMOVED_LABEL;
            }

            if (!empty($clone['adminPassword'])) {
                $sites[$key]['adminPassword'] = self::REMOVED_LABEL;
            }
        }

        return $sites;
    }

    private function getPrimaryKeyInfo(): string
    {
        $tableName           = $this->database->getPrefix() . 'options';
        $isPrimaryKeyMissing = $this->wpOptionsInfo->isOptionTablePrimaryKeyMissing($tableName);
        if ($isPrimaryKeyMissing) {
            return $this->info("Primary Key in {$tableName} :", self::NOT_SET_LABEL);
        }

        $isPrimaryKeyIsOptionName = $this->wpOptionsInfo->isPrimaryKeyIsOptionName($tableName);
        if ($isPrimaryKeyIsOptionName) {
            return $this->info("Primary Key in {$tableName}:", 'option_name');
        }

        return $this->info("Primary Key in {$tableName}:", 'option_id');
    }

    public function setEncodeProLicense(bool $isEncodeProLicense = false)
    {
        $this->isEncodeProLicense = $isEncodeProLicense;
    }

    public function getEncodeProLicense(): bool
    {
        return $this->isEncodeProLicense;
    }

    private function getLicenseKey()
    {
        $licenseKey = get_option('wpstg_license_key');
        if (empty($licenseKey) || !$this->getEncodeProLicense()) {
            return $licenseKey;
        }

        /** @var DataEncryption @dataEncryption */
        $dataEncryption = WPStaging::make(DataEncryption::class);
        // If phpseclib does not exist, return license key as it is
        if (!$dataEncryption->isPhpSecLibAvailable()) {
            return $licenseKey;
        }

        $publicKey = $dataEncryption->getPublicKey();
        if (empty($publicKey)) {
            return $licenseKey;
        }

        return $dataEncryption->rsaEncrypt($licenseKey, $publicKey);
    }

    /**
     * @param string $optionName The name of the WP option to retrieve.
     * @param string $title The title to display before the settings.
     * @return string The formatted output for the settings.
     */
    protected function formatStorageSettings(string $optionName, string $title): string
    {
        $output = PHP_EOL . "-- " . $title . PHP_EOL;

        $settings = (array) get_option($optionName, []);
        if (!empty($settings)) {
            // Add provider header as info item for structured output
            if ($this->enableStructuredOutput) {
                $this->info($title, '');
            }

            foreach ($settings as $key => $value) {
                $output .= $this->info($key, empty($value) ? self::NOT_SET_LABEL : $this->removeCredentials($key, $value));
            }
        }

        return $output;
    }

    /**
     * @return string
     */
    protected function muPlugins(): string
    {
        $this->currentSection = SystemInfoParser::SECTIONS['PLUGINS_OVERVIEW']['id'];
        $output    = $this->header("Must-Use Plugins");
        $muPlugins = get_mu_plugins();
        foreach ($muPlugins as $pluginData) {
            $output .= $this->info($pluginData["Name"] . ":", $pluginData["Version"]);
        }

        return $output;
    }

    /**
     * @return string
     */
    protected function dropIns(): string
    {
        $this->currentSection = SystemInfoParser::SECTIONS['PLUGINS_OVERVIEW']['id'];
        $output  = $this->header("Drop-Ins");
        $dropIns = get_dropins();
        foreach ($dropIns as $dropIn) {
            $output .= $this->info($dropIn["Name"] . ":", $dropIn["Version"]);
        }

        return $output;
    }
}
