<?php

namespace WPStaging\Backend\Modules;

use WPStaging\Backend\Upgrade\Upgrade;
use WPStaging\Core\Utils\Browser;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils;
use WPStaging\Core\Utils\Multisite;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\BackgroundProcessing\Queue;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\SiteInfo;

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
     * @var bool
     */
    private $isMultiSite;

    /**
     *
     * @var object
     */
    private $helper;

    public function __construct()
    {
        $this->isMultiSite = is_multisite();
        $this->helper      = new Utils\Helper();
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
    public function get()
    {
        $output = "### Begin System Info ###" . PHP_EOL . PHP_EOL;

        $output .= $this->wpstaging();

        $output .= $this->wp();

        $output .= $this->site();

        $output .= $this->getMultisiteInfo();

        $output .= $this->plugins();

        $output .= $this->multiSitePlugins();

        $output .= $this->server();

        $output .= $this->php();

        $output .= $this->phpExtensions();

        $output .= $this->browser();

        $output .= PHP_EOL . "### End System Info ###";

        return $output;
    }

    /**
     * @param string $string
     * @return string
     */
    public function header($string)
    {
        return PHP_EOL . "-- {$string}" . PHP_EOL . PHP_EOL;
    }

    /**
     * Formating title and the value
     * @param string $title
     * @param string $value
     * @return string
     */
    public function info($title, $value)
    {
        return str_pad($title, 56, ' ', STR_PAD_RIGHT) . print_r($value, true) . PHP_EOL;
    }

    /**
     * Theme Information
     * @return string
     */
    public function theme()
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
     * Site Information
     * @return string
     */
    public function site()
    {
        $output = $this->header("-- Site Info");
        $output .= $this->info("Site URL:", site_url());
        $output .= $this->info("Home URL:", $this->helper->getHomeUrl());
        $output .= $this->info("Home Path:", get_home_path());
        $output .= $this->info("ABSPATH:", ABSPATH);
        $output .= $this->info("Installed in subdir:", ($this->isSubDir() ? 'Yes' : 'No'));

        return apply_filters("wpstg_sysinfo_after_site_info", $output);
    }

    /**
     * Multisite information
     * @return string
     */
    private function getMultisiteInfo()
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

        return apply_filters("wpstg_sysinfo_after_multisite_info", $output);
    }

    /**
     * Wp Staging plugin Information
     * @return string
     */
    public function wpstaging()
    {
        $settings = (object)get_option('wpstg_settings', []);

        $output              = "-- WP STAGING Settings" . PHP_EOL . PHP_EOL;
        $output              .= $this->info("DB Query Limit:", isset($settings->queryLimit) ? $settings->queryLimit : '[not set]');
        $output              .= $this->info("DB Search & Replace Limit:", isset($settings->querySRLimit) ? $settings->querySRLimit : '[not set]');
        $output              .= $this->info("File Copy Limit:", isset($settings->fileLimit) ? $settings->fileLimit : '[not set]');
        $output              .= $this->info("Maximum File Size:", isset($settings->maxFileSize) ? $settings->maxFileSize : '[not set]');
        $output              .= $this->info("File Copy Batch Size:", isset($settings->batchSize) ? $settings->batchSize : '[not set]');
        $output              .= $this->info("CPU Load Priority:", isset($settings->cpuLoad) ? $settings->cpuLoad : '[not set]');
        $output              .= $this->info("Keep Permalinks:", isset($settings->keepPermalinks) ? $settings->keepPermalinks : '[not set]');
        $output              .= $this->info("Debug Mode:", isset($settings->debugMode) ? $settings->debugMode : '[NOT SET]');
        $output              .= $this->info("Optimize Active:", isset($settings->optimizer) ? $settings->optimizer : '[not set]');
        $output              .= $this->info("Delete on Uninstall:", isset($settings->unInstallOnDelete) ? $settings->unInstallOnDelete : '[not set]');
        $output              .= $this->info("Check Directory Size:", isset($settings->checkDirectorySize) ? $settings->checkDirectorySize : '[not set]');
        $output              .= $this->info("Access Permissions:", isset($settings->userRoles) ? $settings->userRoles : '[not set]');
        $output              .= $this->info("Users With Staging Access:", isset($settings->usersWithStagingAccess) ? $settings->usersWithStagingAccess : '[not set]');
        $output              .= $this->info("Admin Bar Color:", isset($settings->adminBarColor) ? $settings->adminBarColor : '[not set]');
        $analyticsHasConsent = get_option('wpstg_analytics_has_consent');
        $output              .= $this->info("Send Usage Information:", !empty($analyticsHasConsent) ? 'true' : 'false');
        $output              .= $this->info("Send Backup Errors via E-Mail:", isset($settings->schedulesErrorReport) ? $settings->schedulesErrorReport : '[not set]');
        $output              .= $this->info("E-Mail Address:", isset($settings->schedulesReportEmail) ? $settings->schedulesReportEmail : '[not set]');

        $output .= PHP_EOL . PHP_EOL . "-- Google Drive Settings" . PHP_EOL . PHP_EOL;

        $googleDriveSettings = (object)get_option('wpstg_googledrive', []);
        if (!empty($googleDriveSettings)) {
            foreach ($googleDriveSettings as $key => $value) {
                $output .= $this->info($key, empty($value) ? '[not set]' : $this->removeCredentials($key, $value));
            }
        }

        $output .= PHP_EOL . PHP_EOL . "-- Amazon S3 Settings" . PHP_EOL . PHP_EOL;

        $amazonS3Settings = (object)get_option('wpstg_amazons3', []);
        if (!empty($amazonS3Settings)) {
            foreach ($amazonS3Settings as $key => $value) {
                $output .= $this->info($key, empty($value) ? '[not set]' : $this->removeCredentials($key, $value));
            }
        }

        $output .= PHP_EOL . PHP_EOL . "-- DigitalOcean Spaces Settings" . PHP_EOL . PHP_EOL;

        $digitalOceanSpacesSettings = (object)get_option('wpstg_digitalocean-spaces', []);
        foreach ($digitalOceanSpacesSettings as $key => $value) {
            $output .= $this->info($key, empty($value) ? 'not set' : $this->removeCredentials($key, $value));
        }

        $output .= PHP_EOL . PHP_EOL . "-- Wasabi Settings" . PHP_EOL . PHP_EOL;

        $wasabiSettings = (object)get_option('wpstg_wasabi-s3', []);
        foreach ($wasabiSettings as $key => $value) {
            $output .= $this->info($key, empty($value) ? 'not set' : $this->removeCredentials($key, $value));
        }

        $output .= PHP_EOL . PHP_EOL . "-- Generic S3 Settings" . PHP_EOL . PHP_EOL;

        $genericS3Settings = (object)get_option('wpstg_generic-s3', []);
        foreach ($genericS3Settings as $key => $value) {
            $output .= $this->info($key, empty($value) ? 'not set' : $this->removeCredentials($key, $value));
        }

        $output .= PHP_EOL . PHP_EOL . "-- SFTP Settings" . PHP_EOL . PHP_EOL;

        $sftpSettings = (object)get_option('wpstg_sftp', []);
        if (!empty($sftpSettings)) {
            foreach ($sftpSettings as $key => $value) {
                $output .= $this->info($key, empty($value) ? '[not set]' : $this->removeCredentials($key, $value));
            }
        }

        $output .= $this->getScheduleInfo();

        $output .= PHP_EOL . PHP_EOL . "-- Available Sites. Version < 1.1.6.x" . PHP_EOL . PHP_EOL;

        // Clones data < 1.1.6.x
        $clones = (object)get_option('wpstg_existing_clones', []);
        if (!empty($clones)) {
            foreach ($clones as $key => $value) {
                $output .= $this->info("Site name & subfolder :", $value);
            }
        }

        $output .= PHP_EOL . PHP_EOL . "-- Available Sites. Version > 2.0.x" . PHP_EOL . PHP_EOL;

        // Clones data version > 2.x
        // old name wpstg_existing_clones_beta
        // New name since version 4.0.3 wpstg_staging_sites
        $stagingSites = get_option(Sites::STAGING_SITES_OPTION, []);
        // make sure $stagingSites is an array
        if (is_array($stagingSites)) {
            foreach ($stagingSites as $key => $clone) {
                $path = !empty($clone['path']) ? $clone['path'] : '[not set]';

                $output .= $this->info("Number:", isset($clone['number']) ? $clone['number'] : '[not set]');
                $output .= $this->info("directoryName:", isset($clone['directoryName']) ? $clone['directoryName'] : '[not set]');
                $output .= $this->info("Path:", $path);
                $output .= $this->info("URL:", isset($clone['url']) ? $clone['url'] : '[not set]');
                $output .= $this->info("DB Prefix:", isset($clone['prefix']) ? $clone['prefix'] : '[not set]');
                $output .= $this->info("DB Prefix wp-config.php:", $this->getStagingPrefix($clone));
                $output .= $this->info("WP STAGING Version:", isset($clone['version']) ? $clone['version'] : '[not set]');
                $output .= $this->info("WP Version:", $this->getStagingWpVersion($path)) . PHP_EOL . PHP_EOL;
            }
        }

        $output .= $this->info(Sites::STAGING_SITES_OPTION . ": ", serialize(get_option(Sites::STAGING_SITES_OPTION, [])));
        $output .= '' . PHP_EOL;

        $output .= $this->info(Sites::OLD_STAGING_SITES_OPTION . ": ", serialize(get_option(Sites::OLD_STAGING_SITES_OPTION, [])));
        $output .= '' . PHP_EOL;

        $output .= $this->info(Sites::BACKUP_STAGING_SITES_OPTION . ": ", serialize(get_option(Sites::BACKUP_STAGING_SITES_OPTION, [])));
        $output .= '' . PHP_EOL;


        $output .= $this->info("Pro Version:", get_option('wpstgpro_version', '[not set]'));
        $output .= $this->info("Pro License Key:", get_option('wpstg_license_key'));
        // @see \WPStaging\Backend\Pro\Upgrade\Upgrade::OPTION_INSTALL_DATE
        $output .= $this->info("Pro Install Date:", get_option('wpstgpro_install_date', '[not set]'));
        // @see \WPStaging\Backend\Pro\Upgrade\Upgrade::OPTION_UPGRADE_DATE
        $output .= $this->info("Pro Update Date:", get_option('wpstgpro_upgrade_date', '[not set]'));
        $output .= $this->info("Free or Pro Install Date (deprecated):", get_option('wpstg_installDate', '[not set]'));
        $output .= $this->info("Free Version:", get_option('wpstg_version', '[not set]'));
        $output .= $this->info("Free Install Date:", get_option(Upgrade::OPTION_INSTALL_DATE, '[not set]'));
        $output .= $this->info("Free Update Date:", get_option(Upgrade::OPTION_UPGRADE_DATE, '[not set]'));
        $output .= $this->info("Updated from Pro Version:", get_option('wpstgpro_version_upgraded_from', '[not set]'));
        $output .= $this->info("Updated from Free Version:", get_option('wpstg_version_upgraded_from', '[not set]'));
        $output .= $this->info("Is Staging Site:", (new SiteInfo())->isStagingSite() ? 'true' : 'false') . PHP_EOL . PHP_EOL;

        return apply_filters("wpstg_sysinfo_after_wpstaging_info", $output);
    }

    /**
     * Browser Information
     * @return string
     */
    public function browser()
    {
        $output = $this->header("User Browser");
        $output .= (new Browser());

        return apply_filters("wpstg_sysinfo_after_user_browser", $output);
    }

    /**
     * Frontpage Information when frontpage is set to "page"
     * @return string
     */
    public function frontPage()
    {
        if (get_option("show_on_front") !== "page") {
            return '';
        }

        $frontPageID = get_option("page_on_front");
        $blogPageID  = get_option("page_for_posts");

        // Front Page
        $pageFront = ($frontPageID != 0) ? get_the_title($frontPageID) . " (#{$frontPageID})" : "Unset";
        // Blog Page ID
        $pageBlog = ($blogPageID != 0) ? get_the_title($blogPageID) . " (#{$blogPageID})" : "Unset";

        $output = $this->info("Page On Front:", $pageFront);
        $output .= $this->info("Page For Posts:", $pageBlog);

        return $output;
    }

    /**
     * Check wp_remote_post() functionality
     * @return string
     */
    public function wpRemotePost()
    {
        // Make sure wp_remote_post() is working
        $wpRemotePost = "wp_remote_post() does not work";

        // Send request
        $response = wp_remote_post(
            "https://www.paypal.com/cgi-bin/webscr",
            [
                "sslverify" => false,
                "timeout" => 60,
                "user-agent" => "WPSTG/" . WPStaging::getVersion(),
                "body" => ["cmd" => "_notify-validate"]
            ]
        );

        // Validate it worked
        if (!is_wp_error($response) && $response["response"]["code"] >= 200 && $response["response"]["code"] < 300) {
            $wpRemotePost = "wp_remote_post() works";
        }

        return $this->info("Remote Post:", $wpRemotePost);
    }

    /**
     * WordPress Configuration
     * @return string
     */
    public function wp()
    {
        $output = $this->header("WordPress Configuration");
        $output .= $this->info("Version:", get_bloginfo("version"));
        $output .= $this->info("WPLANG:", (defined("WPLANG") && WPLANG) ? WPLANG : "en_US");

        $permalinkStructure = get_option("permalink_structure");
        $output             .= $this->info("Permalink Structure:", ($permalinkStructure) ?: "Default");

        $output .= $this->info("Active Theme:", $this->theme());
        $output .= $this->info("Show On Front:", get_option("show_on_front"));

        $output .= $this->info("Installed in Subdir:", $this->isSubDir() ? 'true' : 'false');

        // Frontpage information
        $output .= $this->frontPage();

        // WP Remote Post
        $output .= $this->wpRemotePost();

        // Table Prefix
        $wpDB        = WPStaging::getInstance()->get("wpdb");
        $tablePrefix = "DB Prefix: " . $wpDB->prefix . ' ';
        $tablePrefix .= "Length: " . strlen($wpDB->prefix) . "   Status: ";
        $tablePrefix .= (strlen($wpDB->prefix) > 16) ? " ERROR: Too long" : " Acceptable";

        $output .= $this->info("Table Prefix:", $tablePrefix);

        // Constants
        $output .= $this->info("WP_CONTENT_DIR:", WP_CONTENT_DIR);
        $output .= $this->info("WP_PLUGIN_DIR:", WP_PLUGIN_DIR);
        if (defined('UPLOADS')) {
            $output .= $this->info("WP UPLOADS CONST:", UPLOADS);
        }
        $uploads = wp_upload_dir();
        $output  .= $this->info("uploads['basedir']:", $uploads['basedir']);
        if (defined('WP_TEMP_DIR')) {
            $output .= $this->info("WP_TEMP_DIR:", WP_TEMP_DIR);
        }

        // WP Debug
        $output .= $this->info("WP_DEBUG:", (defined("WP_DEBUG")) ? WP_DEBUG ? "Enabled" : "Disabled" : "Not set");
        $output .= $this->info("WP_MEMORY_LIMIT:", WP_MEMORY_LIMIT);
        $output .= $this->info("Registered Post Stati:", implode(", ", \get_post_stati()));

        // WP upload path
        $output .= $this->info("UPLOAD_PATH in wp-config.php:", (defined("UPLOAD_PATH")) ? UPLOAD_PATH : '[not set]');
        $output .= $this->info("upload_path in " . $wpDB->prefix . 'options:', get_option('upload_path', '[not set]'));
        $output .= $this->info("Wordpress cron:", wp_json_encode(get_option('cron', [])));

        return apply_filters("wpstg_sysinfo_after_wpstg_config", $output);
    }

    /**
     * List of Active Plugins
     * @param array $plugins
     * @param array $activePlugins
     * @return string
     */
    public function activePlugins($plugins, $activePlugins)
    {
        $output = $this->header("WordPress Active Plugins");

        foreach ($plugins as $path => $plugin) {
            if (!in_array($path, $activePlugins)) {
                continue;
            }

            $output .= "{$plugin["Name"]}: {$plugin["Version"]}" . PHP_EOL;
        }

        return apply_filters("wpstg_sysinfo_after_wordpress_plugins", $output);
    }

    /**
     * List of Inactive Plugins
     * @param array $plugins
     * @param array $activePlugins
     * @return string
     */
    public function inactivePlugins($plugins, $activePlugins)
    {
        $output = $this->header("WordPress Inactive Plugins");

        foreach ($plugins as $path => $plugin) {
            if (in_array($path, $activePlugins)) {
                continue;
            }

            $output .= "{$plugin["Name"]}: {$plugin["Version"]}" . PHP_EOL;
        }

        return apply_filters("wpstg_sysinfo_after_wordpress_plugins_inactive", $output);
    }

    /**
     * Get list of active and inactive plugins
     * @return string
     */
    public function plugins()
    {
        // Get plugins and active plugins
        $plugins       = get_plugins();
        $activePlugins = get_option("active_plugins", []);

        // Active plugins
        $output = $this->activePlugins($plugins, $activePlugins);
        $output .= $this->inactivePlugins($plugins, $activePlugins);

        return $output;
    }

    /**
     * Multisite Plugins
     * @return string
     */
    public function multiSitePlugins()
    {
        if (!$this->isMultiSite) {
            return '';
        }

        $output = $this->header("Network Active Plugins");

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
    public function server()
    {
        // Server Configuration
        $output = $this->header("Webserver Configuration");

        /** @var Database */
        $database = WPStaging::make(Database::class);

        $output .= $this->info("PHP Version:", PHP_VERSION);
        $output .= $this->info("MySQL Server Type:", $database->getServerType());
        $output .= $this->info("MySQL Version:", $database->getSqlVersion($compact = true));
        $output .= $this->info("MySQL Version Full Info:", $database->getSqlVersion());
        $output .= $this->info("Webserver Info:", isset($_SERVER["SERVER_SOFTWARE"]) ? Sanitize::sanitizeString($_SERVER["SERVER_SOFTWARE"]) : '');

        return apply_filters("wpstg_sysinfo_after_webserver_config", $output);
    }

    /**
     * PHP Configuration
     * @return string
     */
    public function php()
    {
        $output = $this->header("PHP Configuration");
        $output .= $this->info("PHP Max Memory Limit value (memory_limit):", ini_get("memory_limit"));
        $output .= $this->info("PHP Max Memory Limit in Bytes:", wp_convert_hr_to_bytes(ini_get("memory_limit")));
        $output .= $this->info("Max Execution Time:", ini_get("max_execution_time"));
        $output .= $this->info("Safe Mode:", ($this->isSafeModeEnabled() ? "Enabled" : "Disabled"));
        $output .= $this->info("Upload Max File Size:", ini_get("upload_max_filesize"));
        $output .= $this->info("Post Max Size:", ini_get("post_max_size"));
        $output .= $this->info("Upload Max Filesize:", ini_get("upload_max_filesize"));
        $output .= $this->info("Max Input Vars:", ini_get("max_input_vars"));
        $output .= $this->info("PHP User:", $this->getPHPUser());

        $displayErrors = ini_get("display_errors");
        $output        .= $this->info("Display Errors:", ($displayErrors) ? "On ({$displayErrors})" : "N/A");

        return apply_filters("wpstg_sysinfo_after_php_config", $output);
    }

    /**
     *
     * @return string
     */
    public function getPHPUser()
    {

        $user = '';

        if (extension_loaded('posix') && function_exists('posix_getpwuid')) {
            $file = WPSTG_PLUGIN_DIR . 'Core/WPStaging.php';
            $user = posix_getpwuid(fileowner($file));
            return isset($user['name']) ? $user['name'] : 'can not detect PHP user name';
        }

        if (function_exists('exec') && @exec('echo EXEC') == 'EXEC') {
            return exec('whoami');
        }

        return $user;
    }

    /**
     * Check if PHP is on Safe Mode
     * @return bool
     */
    public function isSafeModeEnabled()
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
    public function isSupported($functionName)
    {
        return (function_exists($functionName)) ? "Supported" : "Not Supported";
    }

    /**
     * Checks if class or extension is loaded / exists to determine if it is installed or not
     * @param string $name
     * @param bool $isClass
     * @return string
     */
    public function isInstalled($name, $isClass = true)
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
    public function phpExtensions()
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
            $output .= $feature . ($version['features'] & constant($feature) ? ' yes' : ' no') . PHP_EOL;
        }


        $output .= $this->info("fsockopen:", $this->isSupported("fsockopen"));
        $output .= $this->info("SOAP Client:", $this->isInstalled("SoapClient"));
        $output .= $this->info("Suhosin:", $this->isInstalled("suhosin", false));

        return apply_filters("wpstg_sysinfo_after_php_ext", $output);
    }

    /**
     * Check if WP is installed in subdir
     * @return bool
     */
    private function isSubDir()
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
     * Check and return prefix of the staging site
     */

    /**
     * Try to get the staging prefix from wp-config.php of staging site
     * @param array $clone
     * @return string
     */
    private function getStagingPrefix($clone = [])
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
     * @return string
     */
    private function getStagingWpVersion($path)
    {

        if ($path === '[not set]') {
            return "Error: Cannot detect WP version";
        }

        // Get version number of wp staging
        $file = trailingslashit($path) . 'wp-includes/version.php';

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
            return '[REMOVED]';
        }
        return empty($value) ? '[not set]' : $value;
    }

    private function getScheduleInfo()
    {
        $output = PHP_EOL . PHP_EOL . "-- Backup Schedules" . PHP_EOL . PHP_EOL;

        $backupSchedules = get_option('wpstg_backup_schedules', []);
        if (!empty($backupSchedules)) {
            foreach ($backupSchedules as $key => $value) {
                $output .= $this->info('Schedule ' . !empty($key) ? $key : '', empty($value) ? '[not set]' : print_r($value, true));
            }
        }

        /** @var Queue */
        $queue = WPStaging::make(Queue::class);

        $output .= $this->info("All Actions:", $queue->count());
        $output .= $this->info("Pending Actions:", $queue->count(Queue::STATUS_READY));
        $output .= $this->info("Processing Actions:", $queue->count(Queue::STATUS_PROCESSING));
        $output .= $this->info("Completed Actions:", $queue->count(Queue::STATUS_COMPLETED));
        $output .= $this->info("Failed Actions:", $queue->count(Queue::STATUS_FAILED));

        return $output;
    }
}
