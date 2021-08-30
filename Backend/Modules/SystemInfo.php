<?php

namespace WPStaging\Backend\Modules;

use WPStaging\Core\DI\InjectionAware;
use WPStaging\Core\Utils\Browser;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils;
use WPStaging\Core\Utils\Multisite;
use WPStaging\Core\Utils\Helper;
use WPStaging\Framework\Staging\Sites;

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
     * @var obj
     */
    private $helper;

    public function __construct()
    {
        $this->isMultiSite = is_multisite();
        $this->helper = new Utils\Helper();
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
        return str_pad($title, 56, ' ', STR_PAD_RIGHT) . $value . PHP_EOL;
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

        $output = $this->info("Multisite:", ($this->isMultiSite ? "Yes" : "No"));
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
        // Get wpstg settings
        $settings = (object)get_option('wpstg_settings', []);

        // Clones data < 1.1.6.x
        $clones = (object)get_option('wpstg_existing_clones', []);
        // Clones data version > 2.x
        // old name wpstg_existing_clones_beta
        // New name since version 4.0.3
        $stagingSites = get_option(Sites::STAGING_SITES_OPTION, []);


        $output = "-- WP Staging Settings" . PHP_EOL . PHP_EOL;
        $output .= $this->info("Query Limit:", isset($settings->queryLimit) ? $settings->queryLimit : 'undefined');
        $output .= $this->info("DB Search & Replace Limit:", isset($settings->querySRLimit) ? $settings->querySRLimit : 'undefined');
        $output .= $this->info("File Copy Limit:", isset($settings->fileLimit) ? $settings->fileLimit : 'undefined');
        $output .= $this->info("Batch Size:", isset($settings->batchSize) ? $settings->batchSize : 'undefined');
        $output .= $this->info("CPU Load:", isset($settings->cpuLoad) ? $settings->cpuLoad : 'undefined');
        $output .= $this->info("WP in Subdir:", $this->isSubDir() ? 'true' : 'false');

        $output .= PHP_EOL . PHP_EOL . "-- Available Sites Version < 1.1.6.x" . PHP_EOL . PHP_EOL;

        foreach ($clones as $key => $value) {
            $output .= $this->info("Site name & subfolder :", $value);
        }
        $output .= PHP_EOL . PHP_EOL . "-- Available Sites Version > 2.0.x" . PHP_EOL . PHP_EOL;

        foreach ($stagingSites as $key => $clone) {
            $path = !empty($clone['path']) ? $clone['path'] : 'undefined';

            $output .= $this->info("Number:", isset($clone['number']) ? $clone['number'] : 'undefined');
            $output .= $this->info("directoryName:", isset($clone['directoryName']) ? $clone['directoryName'] : 'undefined');
            $output .= $this->info("Path:", $path);
            $output .= $this->info("URL:", isset($clone['url']) ? $clone['url'] : 'undefined');
            $output .= $this->info("DB Prefix:", isset($clone['prefix']) ? $clone['prefix'] : 'undefined');
            $output .= $this->info("DB Prefix wp-config.php:", $this->getStagingPrefix($clone));
            $output .= $this->info("WP Staging Version:", isset($clone['version']) ? $clone['version'] : 'undefined');
            $output .= $this->info("WP Version:", $this->getStagingWpVersion($path)) . PHP_EOL . PHP_EOL;
        }


        $output .= $this->info("Raw Clones Data:", json_encode(get_option(Sites::STAGING_SITES_OPTION, 'undefined')));

        $output .= '' . PHP_EOL;


        //$output .= PHP_EOL . PHP_EOL;

        $output .= $this->info("Plugin Pro Version:", get_option('wpstgpro_version', 'undefined'));
        $output .= $this->info("Plugin Pro License Key:", get_option('wpstg_license_key'));
        $output .= $this->info("Plugin Free Version:", get_option('wpstg_version', 'undefined'));
        $output .= $this->info("Install Date:", get_option('wpstg_installDate', 'undefined'));
        $output .= $this->info("Upgraded from Pro:", get_option('wpstgpro_version_upgraded_from', 'undefined'));
        $output .= $this->info("Upgraded from Free:", get_option('wpstg_version_upgraded_from', 'undefined'));
        $output .= $this->info("Is Staging Site:", wpstg_is_stagingsite() ? 'true' : 'false') . PHP_EOL . PHP_EOL;


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
        $blogPageID = get_option("page_for_posts");

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
        $output .= $this->info("Language:", (defined("WPLANG") && WPLANG) ? WPLANG : "en_US");

        $permalinkStructure = get_option("permalink_structure");
        ;
        $output .= $this->info("Permalink Structure:", ($permalinkStructure) ? $permalinkStructure : "Default");

        $output .= $this->info("Active Theme:", $this->theme());
        $output .= $this->info("Show On Front:", get_option("show_on_front"));

        // Frontpage information
        $output .= $this->frontPage();

        // WP Remote Post
        $output .= $this->wpRemotePost();

        // Table Prefix
        $wpDB = WPStaging::getInstance()->get("wpdb");
        $tablePrefix = "DB Prefix: " . $wpDB->prefix . ' ';
        $tablePrefix .= "Length: " . strlen($wpDB->prefix) . "   Status: ";
        $tablePrefix .= (strlen($wpDB->prefix) > 16) ? " ERROR: Too long" : " Acceptable";

        $output .= $this->info("Table Prefix:", $tablePrefix);

        // Constants
        $output .= $this->info("WP Content Path:", WP_CONTENT_DIR);
        $output .= $this->info("WP Plugin Dir:", WP_PLUGIN_DIR);
        if (defined('UPLOADS')) {
            $output .= $this->info("WP UPLOADS CONST:", UPLOADS);
        }
        $uploads = wp_upload_dir();
        $output .= $this->info("WP Uploads Dir:", $uploads['basedir']);
        if (defined('WP_TEMP_DIR')) {
            $output .= $this->info("WP Temp Dir:", WP_TEMP_DIR);
        }

        // WP Debug
        $output .= $this->info("WP_DEBUG:", (defined("WP_DEBUG")) ? WP_DEBUG ? "Enabled" : "Disabled" : "Not set");
        $output .= $this->info("Memory Limit:", WP_MEMORY_LIMIT);
        $output .= $this->info("Registered Post Stati:", implode(", ", \get_post_stati()));

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
        $plugins = get_plugins();
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

        $plugins = wp_get_active_network_plugins();
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

        $output .= $this->info("PHP Version:", PHP_VERSION);
        $output .= $this->info("MySQL Version:", WPStaging::getInstance()->get("wpdb")->db_version());
        $output .= $this->info("Webserver Info:", $_SERVER["SERVER_SOFTWARE"]);

        return apply_filters("wpstg_sysinfo_after_webserver_config", $output);
    }

    /**
     * PHP Configuration
     * @return string
     */
    public function php()
    {
        $output = $this->header("PHP Configuration");
        $output .= $this->info("Safe Mode:", ($this->isSafeModeEnabled() ? "Enabled" : "Disabled"));
        $output .= $this->info("PHP Max Memory Limit:", ini_get("memory_limit"));
        $output .= $this->info("Upload Max Size:", ini_get("upload_max_filesize"));
        $output .= $this->info("Post Max Size:", ini_get("post_max_size"));
        $output .= $this->info("Upload Max Filesize:", ini_get("upload_max_filesize"));
        $output .= $this->info("Time Limit:", ini_get("max_execution_time"));
        $output .= $this->info("Max Input Vars:", ini_get("max_input_vars"));
        $output .= $this->info("PHP User:", $this->getPHPUser());

        $displayErrors = ini_get("display_errors");
        $output .= $this->info("Display Errors:", ($displayErrors) ? "On ({$displayErrors})" : "N/A");

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
            $user = exec('whoami');
            return $user;
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
     * @return boolean
     */
    private function isSubDir()
    {
        // Compare names without scheme to bypass cases where siteurl and home have different schemes http / https
        // This is happening much more often than you would expect
        $siteurl = preg_replace('#^https?://#', '', rtrim(get_option('siteurl'), '/'));
        $home = preg_replace('#^https?://#', '', rtrim(get_option('home'), '/'));

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
     * @return sting
     */
    private function getStagingPrefix($clone = [])
    {
        // Throw error
        $path = ABSPATH . $clone['directoryName'] . DIRECTORY_SEPARATOR . "wp-config.php";
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

        if ($path === 'undefined') {
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
}
