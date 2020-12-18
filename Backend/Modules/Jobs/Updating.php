<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Core\Utils\Helper;
use WPStaging\Framework\Utils\WpDefaultDirectories;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Updating extends Job
{

    /**
     * External Database Used
     * @var bool
     */
    public $isExternal;

    /**
     * @var mixed|null
     */
    private $db;

    /**
     * Initialize is called in \Job
     */
    public function initialize()
    {
        $this->db = WPStaging::getInstance()->get("wpdb");
    }

    /**
     * Save Chosen Cloning Settings
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        if (!isset($_POST) || !isset($_POST["cloneID"])) {
            return false;
        }

        // Delete files to copy listing
        $this->cache->delete("files_to_copy");

        // Generate Options
        // Clone
        //$this->options->clone                 = $_POST["cloneID"];
        $this->options->clone = preg_replace("#\W+#", '-', strtolower($_POST["cloneID"]));
        $this->options->cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($this->options->clone));
        $this->options->cloneNumber = 1;
        $this->options->includedDirectories = [];
        $this->options->excludedDirectories = [];
        $this->options->extraDirectories = [];
        $this->options->excludedFiles = [
            '.htaccess',
            '.DS_Store',
            '*.git',
            '*.svn',
            '*.tmp',
            'desktop.ini',
            '.gitignore',
            '*.log',
            'object-cache.php',
            'web.config' // Important: Windows IIS configuration file. Do not copy this to the staging site is staging site is placed into subfolder

        ];

        $this->options->excludedFilesFullPath = [
            'wp-content' . DIRECTORY_SEPARATOR . 'db.php',
            'wp-content' . DIRECTORY_SEPARATOR . 'object-cache.php',
            'wp-content' . DIRECTORY_SEPARATOR . 'advanced-cache.php'
        ];

        // Define mainJob to differentiate between cloning, updating and pushing
        $this->options->mainJob = 'updating';

        // Job
        $this->options->job = new \stdClass();

        // Check if clone data already exists and use that one
        if (isset($this->options->existingClones[$this->options->clone])) {
            $this->options->cloneNumber = $this->options->existingClones[$this->options->clone]['number'];
            $this->options->databaseUser = $this->options->existingClones[$this->options->clone]['databaseUser'];
            $this->options->databasePassword = $this->options->existingClones[$this->options->clone]['databasePassword'];
            $this->options->databaseDatabase = $this->options->existingClones[$this->options->clone]['databaseDatabase'];
            $this->options->databaseServer = $this->options->existingClones[$this->options->clone]['databaseServer'];
            $this->options->databasePrefix = $this->options->existingClones[$this->options->clone]['databasePrefix'];
            $this->options->destinationHostname = $this->options->existingClones[$this->options->clone]['url'];
            $this->options->uploadsSymlinked = isset($this->options->existingClones[strtolower($this->options->current)]['uploadsSymlinked']) ? $this->options->existingClones[strtolower($this->options->current)]['uploadsSymlinked'] : false;
            $this->options->prefix = $this->getStagingPrefix();
            $helper = new Helper();
            $this->options->homeHostname = $helper->getHomeUrlWithoutScheme();
        } else {
            wp_die('Fatal Error: Can not update clone because there is no clone data.');
        }

        $this->isExternal = !(empty($this->options->databaseUser) && empty($this->options->databasePassword));

        // Included Tables
        if (isset($_POST["includedTables"]) && is_array($_POST["includedTables"])) {
            $this->options->tables = $_POST["includedTables"];
        } else {
            $this->options->tables = [];
        }

/*        $uploadsSymlinked = isset($_POST['uploadsSymlinked']) && $_POST['uploadsSymlinked'] === 'true';
        if ($uploadsSymlinked !== $this->options->uploadsSymlinked) {
            $this->returnException('Symlink Option cannot be changed at the moment. Updating Stopped!');
        }*/

        // Add upload folder to list of excluded directories for push if symlink option is enabled
        if ($this->options->uploadsSymlinked) {
            $wpUploadsFolder = (new WpDefaultDirectories())->getUploadPath();
            $excludedDirectories[] = rtrim($wpUploadsFolder, '/\\');
        }

        // Excluded Directories
        if (isset($_POST["excludedDirectories"]) && is_array($_POST["excludedDirectories"])) {
            $this->options->excludedDirectories = wpstg_urldecode($_POST["excludedDirectories"]);
        }

        // Excluded Directories TOTAL
        // Do not copy these folders and plugins
        $excludedDirectories = [
            \WPStaging\Core\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'cache',
            \WPStaging\Core\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wps-hide-login',
            \WPStaging\Core\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'wp-super-cache',
            \WPStaging\Core\WPStaging::getWPpath() . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'peters-login-redirect',
        ];

        $this->options->excludedDirectories = array_merge($excludedDirectories, $this->options->excludedDirectories);

        // Included Directories
        if (isset($_POST["includedDirectories"]) && is_array($_POST["includedDirectories"])) {
            $this->options->includedDirectories = wpstg_urldecode($_POST["includedDirectories"]);
        }

        // Extra Directories
        if (isset($_POST["extraDirectories"]) && !empty($_POST["extraDirectories"])) {
            $this->options->extraDirectories = wpstg_urldecode($_POST["extraDirectories"]);
        }

        $this->options->cloneDir = '';
        if (isset($_POST["cloneDir"]) && !empty($_POST["cloneDir"])) {
            $this->options->cloneDir = wpstg_urldecode(trailingslashit($_POST["cloneDir"]));
        }

        $this->options->destinationDir = $this->getDestinationDir();

        $this->options->cloneHostname = $this->options->destinationHostname;

        $this->options->emailsDisabled = isset( $_POST['emailsDisabled'] ) && $_POST['emailsDisabled'] !== "false";

        // Directories to Copy
        $this->options->directoriesToCopy = array_merge(
            $this->options->includedDirectories, $this->options->extraDirectories
        );

        array_unshift($this->options->directoriesToCopy, ABSPATH);

        // Process lock state
        $this->options->isRunning = true;

        return $this->saveOptions();
    }

    /**
     * Get Destination Directory including staging subdirectory
     * @return string
     */
    private function getDestinationDir()
    {
        if (empty($this->options->cloneDir)) {
            return trailingslashit(\WPStaging\Core\WPStaging::getWPpath() . $this->options->cloneDirectoryName);
        }
        return trailingslashit($this->options->cloneDir);
    }

    /**
     * Check and return prefix of the staging site
     */
    public function getStagingPrefix()
    {
        // prefix not defined! Happens if staging site has ben generated with older version of wpstg
        // Try to get staging prefix from wp-config.php of staging site
        $this->options->prefix = $this->options->existingClones[$this->options->clone]['prefix'];
        if (empty($this->options->prefix)) {
            // Throw error if wp-config.php is not readable 
            $path = ABSPATH . $this->options->cloneDirectoryName . "/wp-config.php";
            if (($content = @file_get_contents($path)) === false) {
                $this->log("Can not open {$path}. Can't read contents", Logger::TYPE_ERROR);
                $this->returnException("Fatal Error: Can not read {$path} to get correct table prefix. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com");
                wp_die("Fatal Error: Can not read {$path} to get correct table prefix. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com");
            } else {
                // Get prefix from wp-config.php
                preg_match("/table_prefix\s*=\s*'(\w*)';/", $content, $matches);

                if (!empty($matches[1])) {
                    $this->options->prefix = $matches[1];
                } else {
                    $this->returnException("Fatal Error: Can not detect prefix from {$path}. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com");
                    wp_die("Fatal Error: Can not detect prefix from {$path}. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com");
                }
            }
        }

        // Die() if staging prefix is the same as the live prefix
        if ($this->isExternal === false && $this->db->prefix === $this->options->prefix) {
            $this->log("Fatal Error: Can not update staging site. Prefix. '{$this->options->prefix}' is used for the live site. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com");
            wp_die("Fatal Error: Can not update staging site. Prefix. '{$this->options->prefix}' is used for the live site. Stopping for security reasons. Deleting this staging site and creating a new one could fix this issue. Otherwise contact us support@wp-staging.com");
        }

        // Else
        return $this->options->prefix;
    }

    /**
     * Start the cloning job
     * not used but is abstract
     */
    public function start()
    {
    }

}
