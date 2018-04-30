<?php

namespace WPStaging\Backend\Modules;

use WPStaging\DI\InjectionAware;
use WPStaging\Library\Browser;
use WPStaging\WPStaging;
use WPStaging\Utils;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class SystemInfo
 * @package WPStaging\Backend\Modules
 */
class SystemInfo extends InjectionAware
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

   /**
     * Initialize class
     */
    public function initialize()
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
   public function get() {
      $output = "### Begin System Info ###" . PHP_EOL . PHP_EOL;

        $output .= $this->wpstaging();
        
        $output .= $this->site();

        $output .= $this->browser();

        $output .= $this->wp();

        $output .= $this->plugins();

        $output .= $this->multiSitePlugins();

        $output .= $this->server();

        $output .= $this->php();

        $output .= $this->phpExtensions();

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
        if (get_bloginfo("version") < "3.4" )
        {
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
   public function site() {
      $output = "-- Site Info" . PHP_EOL . PHP_EOL;
      $output .= $this->info( "Site URL:", site_url() );
      $output .= $this->info( "Home URL:", $this->helper->get_home_url() );
      $output .= $this->info( "Home Path:", get_home_path() );
      $output .= $this->info( "ABSPATH:", ABSPATH );
      $output .= $this->info( "Installed in subdir:", ( $this->isSubDir() ? 'Yes' : 'No' ) );
      $output .= $this->info( "Multisite:", ($this->isMultiSite ? "Yes" : "No" ) );

        return apply_filters("wpstg_sysinfo_after_site_info", $output);
    }
    
    /**
     * Wp Staging plugin Information
     * @return string
     */
    public function wpstaging() {
      // Get wpstg settings
      $settings = ( object ) get_option( 'wpstg_settings', array() );

      // Clones data < 1.6.x
      $clones = ( object ) get_option( 'wpstg_existing_clones', array() );
      // Clones data version > 2.x
      $clonesBeta = get_option( 'wpstg_existing_clones_beta' );


      $output = "-- WP Staging Settings" . PHP_EOL . PHP_EOL;
      $output .= $this->info( "Query Limit:", isset( $settings->queryLimit ) ? $settings->queryLimit : 'undefined' );
      $output .= $this->info( "File Copy Limit:", isset( $settings->fileLimit ) ? $settings->fileLimit : 'undefined' );
      $output .= $this->info( "Batch Size:", isset( $settings->batchSize ) ? $settings->batchSize : 'undefined' );
      $output .= $this->info( "CPU Load:", isset( $settings->cpuLoad ) ? $settings->cpuLoad : 'undefined' );
      $output .= $this->info( "WP in Subdir:", isset( $settings->wpSubDirectory ) ? $settings->wpSubDirectory : 'false' );
      $output .= $this->info( "Login Custom Link:", isset( $settings->loginSlug ) ? $settings->loginSlug : 'false' );

      $output .= PHP_EOL . PHP_EOL . "-- Available Sites Version < 1.6.x" . PHP_EOL . PHP_EOL;

      $i = 1;
      foreach ( $clones as $key => $value ) {
         $output .= $this->info( "Site name & subfolder :", $value );
      }
      $output .= PHP_EOL . PHP_EOL . "-- Available Sites Version > 2.0.x" . PHP_EOL . PHP_EOL;

      foreach ( $clonesBeta as $key => $clone ) {
         $output .= $this->info( "Number:", isset( $clone['number'] ) ? $clone['number'] : 'undefined' );
         $output .= $this->info( "directoryName:", isset( $clone['directoryName'] ) ? $clone['directoryName'] : 'undefined' );
         $output .= $this->info( "Path:", isset( $clone['path'] ) ? $clone['path'] : 'undefined' );
         $output .= $this->info( "URL:", isset( $clone['url'] ) ? $clone['url'] : 'undefined' );
         $output .= $this->info( "DB Prefix:", isset( $clone['prefix'] ) ? $clone['prefix'] : 'undefined' );
         $output .= $this->info( "DB Prefix wp-config.php:", $this->getStagingPrefix($clone));
         $output .= $this->info( "Version:", isset( $clone['version'] ) ? $clone['version'] : 'undefined' ) . PHP_EOL . PHP_EOL;
      }


      $output .= $this->info( "Raw Clones Data:", json_encode( get_option( 'wpstg_existing_clones_beta', 'undefined' ) ) );

      $output .= '' . PHP_EOL;


      //$output .= PHP_EOL . PHP_EOL;
      
      $output .= $this->info( "Plugin Version:", get_option('wpstg_version', 'undefined') );
      $output .= $this->info( "Install Date:", get_option('wpstg_installDate', 'undefined') );
      $output .= $this->info( "Upgraded from:", get_option('wpstg_version_upgraded_from', 'undefined') );
      $output .= $this->info( "Is Staging Site:", get_option('wpstg_is_staging_site', 'undefined') )  . PHP_EOL . PHP_EOL;


      return apply_filters( "wpstg_sysinfo_after_wpstaging_info", $output );
   }

   /**
     * Browser Information
     * @return string
     */
    public function browser()
    {
        $output  = $this->header("User Browser");
        $output .= (new Browser);

        return apply_filters("wpstg_sysinfo_after_user_browser", $output);
    }

    /**
     * Frontpage Information when frontpage is set to "page"
     * @return string
     */
    public function frontPage()
    {
        if (get_option("show_on_front") !== "page")
        {
            return '';
        }

        $frontPageID  = get_option("page_on_front");
        $blogPageID   = get_option("page_for_posts");

        // Front Page
        $pageFront    = ($frontPageID != 0) ? get_the_title($frontPageID) . " (#{$frontPageID})" : "Unset";
        // Blog Page ID
        $pageBlog     = ($blogPageID != 0) ? get_the_title($blogPageID) . " (#{$blogPageID})" : "Unset";

        $output  = $this->info("Page On Front:", $pageFront);
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
            array(
                "sslverify"     => false,
                "timeout"       => 60,
                "user-agent"    => "WPSTG/" . WPStaging::VERSION,
                "body"          => array("cmd" => "_notify-validate")
            )
        );

        // Validate it worked
        if (!is_wp_error($response) && 200 <= $response["response"]["code"] && 300> $response["response"]["code"])
        {
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
        $output  = $this->header("WordPress Configuration");
        $output .= $this->info("Version:", get_bloginfo("version"));
        $output .= $this->info("Language:", (defined("WPLANG") && WPLANG) ? WPLANG : "en_US");

        $permalinkStructure = get_option("permalink_structure");;
        $output .= $this->info("Permalink Structure:", ($permalinkStructure) ? $permalinkStructure : "Default");

        $output .= $this->info("Active Theme:", $this->theme());
        $output .= $this->info("Show On Front:", get_option("show_on_front"));

        // Frontpage information
        $output .= $this->frontPage();

        // WP Remote Post
        $output .= $this->wpRemotePost();

        // Table Prefix
        $wpDB           = $this->di->get("wpdb");
        $tablePrefix    = "DB Prefix: " . $wpDB->prefix . ' ';
        $tablePrefix   .= "Length: " . strlen($wpDB->prefix) . "   Status: ";
        $tablePrefix   .= (strlen($wpDB->prefix) > 16) ? " ERROR: Too long" : " Acceptable";

        $output .= $this->info("Table Prefix:", $tablePrefix);

      // Constants
      $output .= $this->info( "WP Content Path:", WP_CONTENT_DIR );
      $output .= $this->info( "WP Plugin Dir:", WP_PLUGIN_DIR );
      if (defined('UPLOADS'))
      $output .= $this->info( "WP UPLOADS CONST:", UPLOADS );
      $uploads = wp_upload_dir();
      $output .= $this->info( "WP Uploads Dir:", wp_basename( $uploads['baseurl'] ) );
      if (defined('WP_TEMP_DIR'))
      $output .= $this->info( "WP Temp Dir:", WP_TEMP_DIR );

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
        $output  = $this->header("WordPress Active Plugins");

        foreach ($plugins as $path => $plugin)
        {
            if (!in_array($path, $activePlugins))
            {
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
        $output  = $this->header("WordPress Inactive Plugins");

        foreach ($plugins as $path => $plugin)
        {
            if (in_array($path, $activePlugins))
            {
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
        $plugins        = get_plugins();
        $activePlugins  = get_option("active_plugins", array());

        // Active plugins
        $output  = $this->activePlugins($plugins, $activePlugins);
        $output .= $this->inactivePlugins($plugins, $activePlugins);

        return $output;
    }

    /**
     * Multisite Plugins
     * @return string
     */
    public function multiSitePlugins()
    {
        if (!$this->isMultiSite)
        {
            return '';
        }

        $output = $this->header("Network Active Plugins");

        $plugins        = wp_get_active_network_plugins();
        $activePlugins  = get_site_option("active_sitewide_plugins", array());

        foreach ($plugins as $pluginPath)
        {
            $pluginBase = plugin_basename($pluginPath);

            if (!array_key_exists($pluginBase, $activePlugins))
            {
                continue;
            }

            $plugin  = get_plugin_data($pluginPath);

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
        $output  = $this->header("Webserver Configuration");

        $output .= $this->info("PHP Version:", PHP_VERSION);
        $output .= $this->info("MySQL Version:", $this->di->get("wpdb")->db_version());
        $output .= $this->info("Webserver Info:", $_SERVER["SERVER_SOFTWARE"]);

        return apply_filters("wpstg_sysinfo_after_webserver_config", $output);
    }

    /**
     * PHP Configuration
     * @return string
     */
    public function php()
    {
        $output  = $this->header("PHP Configuration");
        $output .= $this->info("Safe Mode:", ($this->isSafeModeEnabled() ? "Enabled" : "Disabled"));
        $output .= $this->info("Memory Limit:", ini_get("memory_limit"));
        $output .= $this->info("Upload Max Size:", ini_get("upload_max_filesize"));
        $output .= $this->info("Post Max Size:", ini_get("post_max_size"));
        $output .= $this->info("Upload Max Filesize:", ini_get("upload_max_filesize"));
        $output .= $this->info("Time Limit:", ini_get("max_execution_time"));
        $output .= $this->info("Max Input Vars:", ini_get("max_input_vars"));

        $displayErrors = ini_get("display_errors");
        $output .= $this->info("Display Errors:", ($displayErrors) ? "On ({$displayErrors})" : "N/A");

        return apply_filters("wpstg_sysinfo_after_php_config", $output);
    }

    /**
     * Check if PHP is on Safe Mode
     * @return bool
     */
    public function isSafeModeEnabled()
    {
        return (
            version_compare(PHP_VERSION, "5.4.0", '<') &&
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
        if (true === $isClass)
        {
            return (class_exists($name)) ? "Installed" : "Not Installed";
        }
        else
        {
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
        $output  = $this->header("PHP Extensions");
        $output .= $this->info("cURL:", $this->isSupported("curl_init"));
        $output .= $this->info("fsockopen:", $this->isSupported("fsockopen"));
        $output .= $this->info("SOAP Client:", $this->isInstalled("SoapClient"));
        $output .= $this->info("Suhosin:", $this->isInstalled("suhosin", false));

        return apply_filters("wpstg_sysinfo_after_php_ext", $output);
    }
    
    /**
     * Check if WP is installed in subdir
     * @return boolean
     */
    private function isSubDir(){
        if ( get_option( 'siteurl' ) !== get_option( 'home' ) ) { 
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
    private function getStagingPrefix($clone=array()) {
            // Throw error
            $path = ABSPATH . $clone['directoryName'] . "/wp-config.php";
            if (false === ($content = @file_get_contents($path))) {
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
}