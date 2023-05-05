<?php

namespace WPStaging\Backend\Modules\Jobs;

use Exception;
use FilesystemIterator;
use mysqli;
use stdClass;
use wpdb;
use WPStaging\Backend\Modules\Jobs\Exceptions\CloneNotFoundException;
use WPStaging\Core\Utils\Logger;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Filesystem\FilesystemExceptions;
use WPStaging\Framework\Staging\Sites;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\Strings;

/**
 * Class Delete
 * @package WPStaging\Backend\Modules\Jobs
 */
class Delete extends Job
{

    /**
     * @var stdClass
     */
    private $clone = false;

    /**
     * The path to delete
     * @var string
     */
    private $deleteDir;

    /**
     * @var null|object
     */
    private $tables = null;

    /**
     * @var object|null
     */
    private $job = null;

    /**
     * @var bool
     */
    //private $forceDeleteDirectories = false;

    /**
     *
     * @var wpdb
     */
    public $wpdb;

    /**
     *
     * @var bool
     */
    private $isExternalDb;

    /** @var Strings  */
    private $strings;

    /** @var Sanitize */
    private $sanitize;

    public function __construct($isExternal = false)
    {
        parent::__construct();

        /** @var Sanitize */
        $this->sanitize     = WPStaging::make(Sanitize::class);
        $this->isExternalDb = $isExternal;
        $this->deleteDir    = !empty($_POST['deleteDir']) ? $this->sanitize->sanitizePath($_POST['deleteDir']) : '';
        $this->strings      = new Strings();
    }

    /**
     * Sets Clone and Table Records
     * @param null|array $clone
     * @return bool
     */
    public function setData($clone = null)
    {
        if (!is_array($clone)) {
            $this->getCloneRecords();
        } else {
            $this->clone = (object)$clone;
            //$this->forceDeleteDirectories = true;
        }

        if (!$this->isExternalDatabase()) {
            $this->wpdb = WPStaging::getInstance()->get("wpdb");
            $this->getTableRecords();
            return true;
        }

        if ($this->isExternalDatabaseError()) {
            return false;
        }

        $this->wpdb = $this->getStagingDb();
        $this->getTableRecords();
        return true;
    }

    /**
     * Get database object to interact with
     */
    private function getStagingDb()
    {
        if ($this->clone->databaseSsl && !defined('MYSQL_CLIENT_FLAGS')) {
            // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
            define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
        }

        return new wpdb($this->clone->databaseUser, $this->clone->databasePassword, $this->clone->databaseDatabase, $this->clone->databaseServer);
    }

    /**
     * Date database name
     * @return string
     */
    public function getDbName()
    {
        return $this->wpdb->dbname;
    }

    /**
     * Check if external database is used
     * @return boolean
     */
    protected function isExternalDatabase()
    {
        if ($this->isExternalDb) {
            return true;
        }

        if (!empty($this->clone->databaseUser)) {
            return true;
        }

        return false;
    }

    /**
     * Get clone
     * @param null|string $name
     */
    private function getCloneRecords($name = null)
    {
        if ($name === null && !isset($_POST["clone"])) {
            $this->log("Clone name is not set", Logger::TYPE_FATAL);
            $this->returnException("Clone name is not set");
        }

        if ($name === null) {
            $name = $this->sanitize->sanitizeString($_POST["clone"]);
        }

        $clones = get_option(Sites::STAGING_SITES_OPTION, []);

        if (empty($clones) || !isset($clones[$name])) {
            $this->log("Couldn't find clone name $name or no existing clone", Logger::TYPE_FATAL);
            $this->returnException("Couldn't find clone name $name or no existing clone");
        }

        $this->clone         = $clones[$name];
        $this->clone["name"] = $name;

        $this->clone = (object)$this->clone;

        unset($clones);
    }

    /**
     * Get Tables
     */
    private function getTableRecords()
    {

        $stagingPrefix = $this->getStagingPrefix();

        // Escape "_" to allow searching for that character
        $prefix = $this->strings->replaceLastMatch('_', '\_', $stagingPrefix);

        if ($this->isExternalDatabase()) { // Show all tables if its an external database
            $tables = $this->wpdb->get_results("SHOW TABLE STATUS");
        } else {
            $tables = $this->wpdb->get_results("SHOW TABLE STATUS LIKE '$prefix%'");
        }


        $this->tables = [];

        // no results
        if ($tables !== null) {
            foreach ($tables as $table) {
                $this->tables[] = [
                    "name" => $table->Name,
                    "size" => $this->utilsMath->formatSize($table->Data_length + $table->Index_length)
                ];
            }
        }

        $this->tables = json_decode(json_encode($this->tables));
    }

    /**
     * Check and return prefix of the staging site
     */
    private function getStagingPrefix()
    {
        if ($this->isExternalDatabase() && !empty($this->clone->prefix)) {
            return $this->clone->prefix;
        }

        // Prefix not defined! Happens if staging site has been generated with older version of wpstg
        // Try to get staging prefix from wp-config.php of staging site
        if (empty($this->clone->prefix)) {
            $path = ABSPATH . $this->clone->directoryName . "/wp-config.php";
            if (($content = @file_get_contents($path)) === false) {
                $this->log("Can not open $path. Can't read contents", Logger::TYPE_ERROR);
            }

            preg_match("/table_prefix\s*=\s*'(\w*)';/", $content, $matches);

            if (!empty($matches[1])) {
                $this->clone->prefix = $matches[1];
            } else {
                $this->returnException("Fatal Error: Can not delete staging site. Can not find Prefix. '$matches[1]'. Stopping for security reasons. Creating a new staging site will likely resolve this the next time. Contact support@wp-staging.com");
            }
        }

        if (empty($this->clone->prefix)) {
            $this->returnException("Fatal Error: Can not delete staging site. Can not find table prefix. Contact support@wp-staging.com");
        }

        // Check if staging prefix is the same as the live prefix
        if (empty($this->options->databaseUser) && $this->wpdb->prefix === $this->clone->prefix) {
            $this->log("Fatal Error: Can not delete staging site. Prefix. '{$this->clone->prefix}' is used for the production site. Stopping for security reasons. Go to Sites > Actions > Edit Data and correct the table prefix or contact us.");
            $this->returnException("Fatal Error: Can not delete staging site. Prefix. '{$this->clone->prefix}' is used for the production site. Stopping for security reasons. Go to Sites > Actions > Edit Data and correct the table prefix or contact us");
        }

        return $this->clone->prefix;
    }

    /**
     * @return false
     */
    public function getClone()
    {
        return $this->clone;
    }

    /**
     * @return null|object
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * Start Module
     * @param null|array $clone
     * @return boolean
     * @throws CloneNotFoundException
     * @throws Exception
     */
    public function start($clone = null)
    {
        // Set data
        $this->setData($clone);

        // Get the job first
        $this->getJob();

        $method = "delete" . ucwords($this->job->current);

        if (method_exists($this, $method)) {
            return $this->{$method}();
        }

        // If method doesn't exist probably the cache file was corrupted
        // Just delete that corrupted cache file and restart itself.
        $this->cache->delete($this->getJobCacheFileName());
        return $this->start($clone);
    }

    /**
     * Get job data
     * @throws Exception
     */
    private function getJob()
    {
        $this->job = $this->cache->get($this->getJobCacheFileName());


        if ($this->job !== null && isset($this->job->current)) {
            return;
        }

        // Generate JOB
        $this->job = (object)[
            "current"               => "tables",
            "nextDirectoryToDelete" => $this->clone->path,
            "name"                  => $this->clone->name
        ];

        $this->cache->save($this->getJobCacheFileName(), $this->job);
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function updateJob()
    {
        $this->job->nextDirectoryToDelete = trim($this->job->nextDirectoryToDelete);
        return $this->cache->save($this->getJobCacheFileName(), $this->job);
    }

    /**
     * @return array
     */
    private function getTablesToRemove()
    {
        $tables = $this->getTableNames();

        if (!isset($_POST["excludedTables"]) || !is_array($_POST["excludedTables"]) || empty($_POST["excludedTables"])) {
            return $tables;
        }

        return array_diff($tables, $this->sanitize->sanitizeString($_POST["excludedTables"]));
    }

    /**
     * @return array
     */
    private function getTableNames()
    {
        return (!is_array($this->tables)) ? [] : array_map(function ($value) {
            return ($value->name);
        }, $this->tables);
    }

    /**
     * Delete Tables
     *
     * @throws Exception
     * @todo DRY the code by implementing through WPStaging\Framework\Database\TableService::deleteTablesStartWith
     */
    public function deleteTables()
    {

        if ($this->isOverThreshold()) {
            $this->log("Deleting: Is over threshold");
            return;
        }

        $tables = $this->getTablesToRemove();

        foreach ($tables as $table) {
            // PROTECTION: Never delete any table that beginns with wp prefix of live site
            if (!$this->isExternalDatabase() && $this->strings->startsWith($table, $this->wpdb->prefix)) {
                $this->log("Fatal Error: Trying to delete table $table of main WP installation!", Logger::TYPE_CRITICAL);
            }

            $this->wpdb->query("DROP TABLE $table");
        }

        // Move on to the next
        $this->job->current = "directory";
        $this->updateJob();
    }

    /**
     * Delete complete directory including all files and sub folders
     * @throws Exception
     */
    public function deleteDirectory()
    {
        if ($this->isFatalError()) {
            $this->returnException('Can not delete directory: ' . $this->deleteDir . '. This seems to be the root directory. Exclude this directory from deleting and try again.');
            throw new Exception('Can not delete directory: ' . $this->deleteDir . ' This seems to be the root directory. Exclude this directory from deleting and try again.');
        }

        // Finished or path does not exist
        if (
            empty($this->deleteDir) ||
            $this->deleteDir === get_home_path() ||
            !is_dir($this->deleteDir)
        ) {
            $this->job->current = "finish";
            $this->updateJob();
            $this->deleteFinish();
            return;
        }

        $this->log("Delete staging site: " . $this->clone->path);

        // Make sure the root dir is never deleted!
        if ($this->deleteDir === get_home_path()) {
            $this->log("Fatal Error 8: Trying to delete root of WP installation!", Logger::TYPE_CRITICAL);
            $this->returnException('Fatal Error 8: Trying to delete root of WP installation!');
        }

        // Check if threshold is reached
        if ($this->isOverThreshold()) {
            return;
        }

        $clone        = (string)$this->clone->path;
        $errorMessage = __(sprintf("We could not delete the staging site completely. There are still files in the folder %s that could not be deleted. This could be a write permission issue. Try to delete the folder manually by using FTP or a file manager plugin.<br/> If this happens again please contact us at support@wp-staging.com", $clone), "wp-staging");
        $deleteStatus = "finished";
        if ($this->isNotEmpty($this->deleteDir)) {
            $fs = (new Filesystem())
                ->setShouldStop([$this, 'isOverThreshold'])
                ->shouldPermissionExceptionsBypass(true)
                ->setRecursive();
            try {
                if (!$fs->delete($this->deleteDir)) {
                    return;
                }
            } catch (FilesystemExceptions $ex) {
                $errorMessage = $ex->getMessage();
                $deleteStatus = "unfinished";
            }
        }

        // Throw fatal error if the folder has still not been deleted and there are files in it
        if ($this->isNotEmpty($this->deleteDir)) {
            $response = [
                'job'     => 'delete',
                'status'  => true,
                'delete'  => $deleteStatus,
                'message' => $errorMessage,
                'error'   => true,
            ];
            wp_die(json_encode($response));
        }

        // Successful finish deleting job
        $this->deleteFinish();
    }

    /**
     * Check if directory exists and is not empty
     * @param string $dir
     * @return bool
     */
    private function isNotEmpty($dir)
    {
        // Throw fatal error if the folder has still not been deleted and there are files in it
        $isDirNotEmpty = false;
        if (is_dir($dir)) {
            $iterator      = new FilesystemIterator($dir);
            $isDirNotEmpty = $iterator->valid();
        }

        return $isDirNotEmpty;
    }

    /**
     *
     * @return boolean
     */
    public function isFatalError()
    {
        $homePath = rtrim(get_home_path(), "/");
        return $homePath === rtrim($this->deleteDir, "/");
    }

    /**
     * Finish / Update Existing Clones
     * @throws Exception
     */
    public function deleteFinish()
    {

        $response = [
            'delete' => 'finished',
        ];

        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);

        // Check if clone exist and then remove it from options
        $this->log("Verifying existing clones...");
        foreach ($existingClones as $name => $clone) {
            if ($clone["path"] === $this->clone->path) {
                unset($existingClones[$name]);
            }
        }

        if (update_option(Sites::STAGING_SITES_OPTION, $existingClones) === false) {
            $this->log("Delete: Nothing to save.'");
        }

        // Delete cached file
        $this->cache->delete($this->getJobCacheFileName());
        $this->cache->delete("delete_directories_{$this->clone->name}");
        $this->cache->delete("clone_options");

        wp_die(json_encode($response));
    }

    /**
     * Check if there is error in external database connection
     * can happen if the external database does not exist or stored credentials are wrong
     * @return bool
     *
     * @todo replace it logic with DbInfo once collation check PR is merged.
     */
    private function isExternalDatabaseError()
    {
        if ($this->clone->databaseSsl) {
            // wpdb requires this constant for SSL use
            if (!defined('MYSQL_CLIENT_FLAGS')) {
                // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
                define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
            }

            $db = mysqli_init();
            $db->real_connect($this->clone->databaseServer, $this->clone->databaseUser, $this->clone->databasePassword, $this->clone->databaseDatabase, null, null, MYSQL_CLIENT_FLAGS);
        } else {
            $db = new mysqli($this->clone->databaseServer, $this->clone->databaseUser, $this->clone->databasePassword, $this->clone->databaseDatabase);
        }
        if ($db->connect_error) {
            return true;
        }

        return false;
    }

    /**
     * Return the cache file which contains the info about current job
     *
     * @return string
     */
    private function getJobCacheFileName()
    {
        return "delete_job_{$this->clone->name}";
    }
}
