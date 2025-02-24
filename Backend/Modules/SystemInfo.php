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
 * Class SystemInfo
 * @package WPStaging\Backend\Modules
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

    public function __construct()
    {
        $this->isMultiSite   = is_multisite();
        $this->urlsHelper    = WPStaging::make(Urls::class);
        $this->database      = WPStaging::make(Database::class);
        $this->wpOptionsInfo = WPStaging::make(WpOptionsInfo::class);
        $this->siteInfo      = WPStaging::make(SiteInfo::class);
    }

    /**
     * Magic method
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }

    /**
     * Get System Information as text
     * @return string
     */
    public function get(): string
    {
        $output = $this->server();
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

    /**
     * @param string $string
     * @return string
     */
    public function header(string $string): string
    {
        return PHP_EOL . "### {$string} ###" . PHP_EOL . PHP_EOL;
    }

    /**
     * Formatting title and the value
     * @param string $title
     * @param string|array $value
     * @return string
     */
    public function info(string $title, $value): string
    {
        return str_pad($title, 56, ' ', STR_PAD_RIGHT) . print_r($value, true) . PHP_EOL;
    }

    /**
     * WordPress Configuration
     * @return string
     */
    public function wp(): string
    {
        $output = $this->header("WordPress");
        $output .= $this->info("Site:", ($this->isMultiSite) ? 'Multi Site' : 'Single Site');
        $output .= $this->info("WP Version:", get_bloginfo("version"));
        $output .= $this->info("Installed in subdir:", ($this->isSubDir() ? 'Yes' : 'No'));
        $output .= $this->info("Database Name:", $this->database->getWpdb()->dbname);
        $output .= $this->info("Table Prefix:", $this->getTablePrefix());
        $output .= $this->info("site_url():", site_url());
        $output .= $this->info("home_url():", $this->urlsHelper->getHomeUrl());
        $output .= $this->info("get_home_path():", get_home_path());
        $output .= $this->info("ABSPATH:", ABSPATH);

        $permissions = fileperms(ABSPATH);

        $output .= $this->info("ABSPATH Fileperms:", $permissions);

        $permissions = substr(sprintf('%o', $permissions), -4);

        $output .= $this->info("ABSPATH Permissions:", $permissions);

        $absPathStat = stat(ABSPATH);
        if (!$absPathStat) {
            $absPathStat = "";
        } else {
            $absPathStat = json_encode($absPathStat);
        }

        $output .= $this->info("ABSPATH Stat:", $absPathStat);
        $output .= $this->constantInfo('WP_PLUGIN_DIR');
        $output .= $this->constantInfo('WP_CONTENT_DIR');

        $output .= $this->info("Is wp-content link:", is_link(WP_CONTENT_DIR) ? 'Yes' : 'No');
        $output .= $this->info("Is symlink disabled:", Info::canUse('symlink') === false ? 'Yes' : 'No');
        if (is_link(WP_CONTENT_DIR)) {
            $output .= $this->info("wp-content link target:", readlink(WP_CONTENT_DIR));
            $output .= $this->info("wp-content realpath:", realpath(WP_CONTENT_DIR));
        }

        $output .= $this->constantInfo('UPLOADS');

        $uploads = wp_upload_dir();
        $output .= $this->info("uploads['path']:", $uploads['path']);
        $output .= $this->info("uploads['subdir']:", $uploads['subdir']);
        $output .= $this->info("uploads['basedir']:", $uploads['basedir']);
        $output .= $this->info("uploads['baseurl']:", $uploads['baseurl']);
        $output .= $this->info("uploads['url']:", $uploads['url']);

        $output .= $this->info("UPLOAD_PATH in wp-config.php:", (defined("UPLOAD_PATH")) ? UPLOAD_PATH : self::NOT_SET_LABEL);
        $output .= $this->info("upload_path in " . $this->database->getPrefix() . 'options:', get_option("upload_path") ?: self::NOT_SET_LABEL);
        $output .= $this->getPrimaryKeyInfo();

        $output .= $this->constantInfo('WP_TEMP_DIR');

        $output .= $this->info("WP_DEBUG:", (defined("WP_DEBUG")) ? (WP_DEBUG ? "Enabled" : "Disabled") : self::NOT_SET_LABEL);
        $output .= $this->constantInfo('WP_MEMORY_LIMIT');
        $output .= $this->constantInfo('WP_MAX_MEMORY_LIMIT');
        $output .= $this->constantInfo('FS_CHMOD_DIR');
        $output .= $this->constantInfo('FS_CHMOD_FILE');
        $output .= $this->info("Active Theme:", $this->theme());
        $output .= $this->info("Permalink Structure:", get_option("permalink_structure") ?: "Default");
        $output .= $this->wpRemotePost();
        $output .= $this->info("WPLANG:", (defined("WPLANG") && WPLANG) ? WPLANG : "en_US");
        $output .= $this->info("Wordpress cron:", wp_json_encode(get_option('cron', [])));

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
            return "{$themeData["Name"]} {$themeData["Version"]}";
        }

        $themeData = wp_get_theme();
        return "{$themeData->Name} {$themeData->Version}";
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

        $multisite = new Multisite();

        $output = $this->info("Multisite:", "Yes");
        $output .= $this->info("Multisite Blog ID:", get_current_blog_id());
        $output .= $this->info("MultiSite URL:", $multisite->getHomeURL());
        $output .= $this->info("MultiSite URL without scheme:", $multisite->getHomeUrlWithoutScheme());
        $output .= $this->info("MultiSite is Main Site:", is_main_site() ? 'Yes' : 'No');

        $output .= $this->constantInfo('SUBDOMAIN_INSTALL');
        $output .= $this->constantInfo('DOMAIN_CURRENT_SITE');
        $output .= $this->constantInfo('PATH_CURRENT_SITE');
        $output .= $this->constantInfo('SITE_ID_CURRENT_SITE');
        $output .= $this->constantInfo('BLOG_ID_CURRENT_SITE');

        $networkSites = get_sites();
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
        $optionBackupScheduleReportEmail        = get_option(Notifications::OPTION_BACKUP_SCHEDULE_REPORT_EMAIL);
        $optionBackupScheduleSlackErrorReport   = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_SLACK_ERROR_REPORT);
        $optionBackupScheduleReportSlackWebhook = get_option(BackupScheduler::OPTION_BACKUP_SCHEDULE_REPORT_SLACK_WEBHOOK);
        $wpStagingFreeVersion                   = wpstgGetPluginData('wp-staging.php');
        $output                                 = PHP_EOL . "## WP Staging ##" . PHP_EOL . PHP_EOL;

        $output .= $this->info("Pro Version:", get_option('wpstgpro_version', self::NOT_SET_LABEL));
        $output .= $this->info("Pro License Key:", $this->getLicenseKey() ?: self::NOT_SET_LABEL);
        // @see \WPStaging\Backend\Pro\Upgrade\Upgrade::OPTION_INSTALL_DATE
        $output .= $this->info("Pro Install Date:", get_option('wpstgpro_install_date', self::NOT_SET_LABEL));
        // @see \WPStaging\Backend\Pro\Upgrade\Upgrade::OPTION_UPGRADE_DATE
        $output .= $this->info("Pro Update Date:", get_option('wpstgpro_upgrade_date', self::NOT_SET_LABEL));
        $output .= $this->info("Free or Pro Install Date (legacy):", get_option('wpstg_installDate', self::NOT_SET_LABEL));
        $output .= $this->info("Free Version:", empty($wpStagingFreeVersion['Version']) ? self::NOT_SET_LABEL : $wpStagingFreeVersion['Version']);
        $output .= $this->info("Free Install Date:", get_option(Upgrade::OPTION_INSTALL_DATE, self::NOT_SET_LABEL));
        $output .= $this->info("Free Update Date:", get_option(Upgrade::OPTION_UPGRADE_DATE, self::NOT_SET_LABEL));
        $output .= $this->info("Updated from Pro Version:", get_option('wpstgpro_version_upgraded_from') ?: self::NOT_SET_LABEL);
        $output .= $this->info("Updated from Free Version:", get_option('wpstg_version_upgraded_from') ?: self::NOT_SET_LABEL);
        $output .= $this->info("Is Staging Site:", $this->siteInfo->isStagingSite() ? 'true' : 'false');
        $output .= $this->getBackupDetails();
        $output .= $this->getScheduleInfo();
        $output .= $this->info("DB Query Limit:", isset($settings->queryLimit) ? $settings->queryLimit : self::NOT_SET_LABEL);
        $output .= $this->info("DB Search & Replace Limit:", isset($settings->querySRLimit) ? $settings->querySRLimit : self::NOT_SET_LABEL);
        $output .= $this->info("File Copy Limit:", isset($settings->fileLimit) ? $settings->fileLimit : self::NOT_SET_LABEL);
        $output .= $this->info("Maximum File Size:", isset($settings->maxFileSize) ? $settings->maxFileSize : self::NOT_SET_LABEL);
        $output .= $this->info("File Copy Batch Size:", isset($settings->batchSize) ? $settings->batchSize : self::NOT_SET_LABEL);
        $output .= $this->info("CPU Load Priority:", isset($settings->cpuLoad) ? $settings->cpuLoad : self::NOT_SET_LABEL);
        $output .= $this->info("Keep Permalinks:", isset($settings->keepPermalinks) ? $settings->keepPermalinks : self::NOT_SET_LABEL);
        $output .= $this->info("Debug Mode:", isset($settings->debugMode) ? $settings->debugMode : self::NOT_SET_LABEL);
        $output .= $this->info("Optimize Active:", isset($settings->optimizer) ? $settings->optimizer : self::NOT_SET_LABEL);
        $output .= $this->info("Delete on Uninstall:", isset($settings->unInstallOnDelete) ? $settings->unInstallOnDelete : self::NOT_SET_LABEL);
        $output .= $this->info("Check Directory Size:", isset($settings->checkDirectorySize) ? $settings->checkDirectorySize : self::NOT_SET_LABEL);
        $output .= $this->info("Access Permissions:", isset($settings->userRoles) ? $settings->userRoles : self::NOT_SET_LABEL);
        $output .= $this->info("Users With Staging Access:", isset($settings->usersWithStagingAccess) ? $settings->usersWithStagingAccess : self::NOT_SET_LABEL);
        $output .= $this->info("Admin Bar Color:", isset($settings->adminBarColor) ? $settings->adminBarColor : self::NOT_SET_LABEL);
        $analyticsHasConsent = get_option('wpstg_analytics_has_consent');
        $output .= $this->info("Send Usage Information:", !empty($analyticsHasConsent) ? 'true' : 'false');
        $output .= $this->info("Send Backup Errors via E-Mail:", !empty($optionBackupScheduleErrorReport) && $optionBackupScheduleErrorReport === 'true' ? 'true' : 'false');
        $output .= $this->info("E-Mail Address:", !empty($optionBackupScheduleReportEmail) && is_email($optionBackupScheduleReportEmail) ? $optionBackupScheduleReportEmail : self::NOT_SET_LABEL);
        $output .= $this->info("Send Backup Errors via Slack Webhook:", !empty($optionBackupScheduleSlackErrorReport) && $optionBackupScheduleSlackErrorReport === 'true' ? 'true' : ( WPStaging::isPro() ? 'false' : self::NOT_SET_LABEL ));
        $output .= $this->info("Slack Webhook URL:", WPStaging::isPro() && !empty($optionBackupScheduleReportSlackWebhook) ? self::REMOVED_LABEL : self::NOT_SET_LABEL);
        $output .= $this->info("Backup Compression:", isset($settings->enableCompression) ? ($settings->enableCompression ? 'On' : 'Off') : self::NOT_SET_LABEL);

        $output .= $this->formatStorageSettings('wpstg_googledrive', 'Google Drive Settings');
        $output .= $this->formatStorageSettings('wpstg_dropbox', 'Dropbox Settings');
        $output .= $this->formatStorageSettings('wpstg_one-drive', 'Microsoft OneDrive Settings');
        $output .= $this->formatStorageSettings('wpstg_amazons3', 'Amazon S3 Settings');
        $output .= $this->formatStorageSettings('wpstg_digitalocean-spaces', 'DigitalOcean Spaces Settings');
        $output .= $this->formatStorageSettings('wpstg_wasabi-s3', 'Wasabi Settings');
        $output .= $this->formatStorageSettings('wpstg_generic-s3', 'Generic S3 Settings');
        $output .= $this->formatStorageSettings('wpstg_sftp', 'SFTP Settings');

        $output .= PHP_EOL . "-- Existing Staging Sites" . PHP_EOL . PHP_EOL;

        // Clones data version > 2.x
        // old name wpstg_existing_clones_beta
        // New name since version 4.0.3 wpstg_staging_sites
        $stagingSites = get_option(Sites::STAGING_SITES_OPTION, []);
        if (is_array($stagingSites)) {
            foreach ($stagingSites as $key => $clone) {
                $path = !empty($clone['path']) ? $clone['path'] : self::NOT_SET_LABEL;

                $output .= $this->info("Number:", isset($clone['number']) ? $clone['number'] : self::NOT_SET_LABEL);
                $output .= $this->info("directoryName:", isset($clone['directoryName']) ? $clone['directoryName'] : self::NOT_SET_LABEL);
                $output .= $this->info("Path:", $path);
                $output .= $this->info("URL:", isset($clone['url']) ? $clone['url'] : self::NOT_SET_LABEL);
                $output .= $this->info("DB Prefix:", isset($clone['prefix']) ? $clone['prefix'] : self::NOT_SET_LABEL);
                $output .= $this->info("DB Prefix wp-config.php:", $this->getStagingPrefix($clone));
                $output .= $this->info("WP STAGING Version:", isset($clone['version']) ? $clone['version'] : self::NOT_SET_LABEL);
                $output .= $this->info("WP Version:", $this->getStagingWpVersion($path)) . PHP_EOL . PHP_EOL;

                if (!empty($clone['databasePassword'])) {
                    $clone['databasePassword'] = self::REMOVED_LABEL;
                }

                if (!empty($clone['adminPassword'])) {
                    $clone['adminPassword'] = self::REMOVED_LABEL;
                }

                $stagingSites[$key] = $clone;
            }
        }

        $stagingSitesOptionBackup = (array)get_option(Sites::BACKUP_STAGING_SITES_OPTION, []);
        foreach ($stagingSitesOptionBackup as $key => $clone) {
            if (!empty($clone['databasePassword'])) {
                $clone['databasePassword'] = self::REMOVED_LABEL;
            }

            if (!empty($clone['adminPassword'])) {
                $clone['adminPassword'] = self::REMOVED_LABEL;
            }

            $stagingSitesOptionBackup[$key] = $clone;
        }

        $output .= $this->info(Sites::STAGING_SITES_OPTION . ": ", serialize($stagingSites));
        $output .= $this->info(Sites::BACKUP_STAGING_SITES_OPTION . ": ", serialize($stagingSitesOptionBackup));
        $output .= PHP_EOL;

        $output .= "-- Legacy Options" . PHP_EOL . PHP_EOL;

        $output .= $this->info("wpstg_existing_clones: ", serialize(get_option('wpstg_existing_clones')));
        $output .= $this->info(Sites::OLD_STAGING_SITES_OPTION . ": ", serialize(get_option(Sites::OLD_STAGING_SITES_OPTION, [])));

        return $output;
    }

    /**
     * @return string
     */
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
        $output = $this->header("User Browser");
        $output .= (new Browser());

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
                "body"       => ["cmd" => "_notify-validate"]
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
        if ($this->isMultiSite) {
            $output = $this->header("Active Plugins on this Site");
        } else {
            $output = $this->header("Active Plugins");
        }

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
        $output = $this->header("Start System Info");
        $output .= $this->info("Webserver:", isset($_SERVER["SERVER_SOFTWARE"]) ? Sanitize::sanitizeString($_SERVER["SERVER_SOFTWARE"]) : '');
        $output .= $this->info("OS architecture:", $this->siteInfo->getOSArchitecture());
        $output .= $this->info("PHP architecture:", $this->siteInfo->getPhpArchitecture());

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
        $output .= $this->info("Lower Table Name Settings:", $lowerTablesNameSettings);

        $output .= $this->info("MySQL Server Type:", $this->database->getServerType());
        $output .= $this->info("MySQL Version:", $this->database->getSqlVersion($compact = true));
        $output .= $this->info("MySQL Version Full Info:", $this->database->getSqlVersion());
        $output .= $this->info("PHP Version:", PHP_VERSION);

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
        $output = $this->info("PHP memory_limit:", ini_get("memory_limit"));
        $output .= $this->info("PHP memory_limit in Bytes:", wp_convert_hr_to_bytes(ini_get("memory_limit")));
        $output .= $this->info("PHP max_execution_time:", ini_get("max_execution_time"));
        $output .= $this->info("PHP Safe Mode:", ($this->isSafeModeEnabled() ? "Enabled" : "Disabled"));
        $output .= $this->info("PHP Upload Max File Size:", ini_get("upload_max_filesize"));
        $output .= $this->info("PHP Post Max Size:", ini_get("post_max_size"));
        $output .= $this->info("PHP Upload Max Filesize:", ini_get("upload_max_filesize"));
        $output .= $this->info("PHP Max Input Vars:", ini_get("max_input_vars"));
        $displayErrors = ini_get("display_errors");
        $output .= $this->info("PHP display_errors:", ($displayErrors) ? "On ({$displayErrors})" : "N/A");
        $output .= $this->info("PHP User:", $this->getPHPUser());

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
            'CURL_VERSION_LIBZ'
        ];

        $output = $this->header("PHP Extensions");

        $output .= $this->info("cURL:", $this->isSupported("curl_init"));
        $output .= $this->info("cURL version:", $version['version']);
        $output .= $this->info("cURL ssl version number:", $version['ssl_version']);
        $output .= $this->info("cURL host:", $version['host']);

        foreach ($version['protocols'] as $protocols) {
            $output .= $this->info("cURL protocols:", $protocols);
        }

        foreach ($bitfields as $feature) {
            $output .= $this->info($feature . ":", (defined($feature) && $version['features'] & constant($feature) ? 'yes' : 'no'));
        }


        $output .= $this->info("fsockopen:", $this->isSupported("fsockopen"));
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
    private function getScheduleInfo(): string
    {
        $output          = '';
        $backupSchedules = get_option('wpstg_backup_schedules', []);
        if (!empty($backupSchedules)) {
            foreach ($backupSchedules as $key => $value) {
                $output .= $this->info('Schedule ' . !empty($key) ? $key : '', empty($value) ? self::NOT_SET_LABEL : print_r($value, true));
            }
        } else {
            $output .= $this->info('wpstg_backup_schedules ', self::NOT_SET_LABEL);
        }

        /** @var Queue */
        $queue = WPStaging::make(Queue::class);

        $output .= $this->info("Backup All Actions in DB:", $queue->count());
        $output .= $this->info("Backup Pending Actions (ready):", $queue->count(Queue::STATUS_READY));
        $output .= $this->info("Backup Processing Actions (processing):", $queue->count(Queue::STATUS_PROCESSING));
        $output .= $this->info("Backup Completed Actions (completed):", $queue->count(Queue::STATUS_COMPLETED));
        $output .= $this->info("Backup Failed Actions (failed):", $queue->count(Queue::STATUS_FAILED));

        return $output;
    }

    /**
     * @return string
     */
    private function getTablePrefix(): string
    {
        $tablePrefix = "DB Prefix: " . $this->database->getPrefix() . ' ';
        $tablePrefix .= "Length: " . strlen($this->database->getPrefix()) . "   Status: ";
        $tablePrefix .= (strlen($this->database->getPrefix()) > 16) ? " ERROR: Too long" : " Acceptable";

        return $tablePrefix;
    }

    /**
     * @return string
     */
    private function getBackupDetails(): string
    {
        $backups = WPStaging::make(ListableBackupsCollection::class)->getListableBackups();

        $output = $this->info("Number of Backups:", count($backups));

        $totalBackupSize = 0;
        foreach ($backups as $backup) {
            $totalBackupSize += (float)$backup->size;
        }

        $output .= $this->info("Backup Total File Size:", esc_html($totalBackupSize) . 'M');

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
     * @return string
     */
    private function getPrimaryKeyInfo(): string
    {
        $tableName           = $this->database->getPrefix() . 'options';
        $isPrimaryKeyMissing = $this->wpOptionsInfo->isOptionTablePrimaryKeyMissing($tableName);
        if ($isPrimaryKeyMissing) {
            return $this->info("{$tableName} primary key:", self::NOT_SET_LABEL);
        }

        $isPrimaryKeyIsOptionName = $this->wpOptionsInfo->isPrimaryKeyIsOptionName($tableName);
        if ($isPrimaryKeyIsOptionName) {
            return $this->info("{$tableName} primary key:", 'option_name');
        }

        return $this->info("{$tableName} primary key:", 'option_id');
    }

    /**
     * @return void
     */
    public function setEncodeProLicense(bool $isEncodeProLicense = false)
    {
        $this->isEncodeProLicense = $isEncodeProLicense;
    }

    /**
     * @return bool
     */
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
        $output  = $this->header("Drop-Ins");
        $dropIns = get_dropins();
        foreach ($dropIns as $dropIn) {
            $output .= $this->info($dropIn["Name"] . ":", $dropIn["Version"]);
        }

        return $output;
    }
}
