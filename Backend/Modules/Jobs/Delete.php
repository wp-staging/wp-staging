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
use WPStaging\Staging\Sites;
use WPStaging\Framework\Utils\Sanitize;
use WPStaging\Framework\Utils\Strings;






class Delete extends Job
{



    const DELETE_STATUS_FINISHED = 'finished';




    const DELETE_STATUS_UNFINISHED = 'unfinished';




    private $clone = false;





    private $deleteDir;




    private $tables = null;




    private $job = null;




    public $wpdb;




    private $isExternalDb;

 
    private $strings;

 
    private $sanitize;

    public function __construct()
    {
        parent::__construct();

 
        $this->sanitize  = WPStaging::make(Sanitize::class);
        $this->deleteDir = !empty($_POST['deleteDir']) ? $this->sanitize->sanitizePath($_POST['deleteDir']) : '';
        $this->strings   = new Strings();
    }





    public function setIsExternalDb(bool $isExternal = false)
    {
        $this->isExternalDb = $isExternal;
    }






    public function setData($clone = null): bool
    {
        if (!is_array($clone)) {
            $this->getCloneRecords();
        } else {
            $this->clone = (object)$clone;
        }

 
        $this->cache->setFilename($this->getJobCacheFileName());

        if (!$this->isExternalDatabase()) {
            $this->wpdb = WPStaging::getInstance()->get("wpdb");
            $this->getTableRecords();
            return true;
        }

        if ($this->isExternalDatabaseError()) {
            return false;
        }

        $this->wpdb = $this->getExternalStagingDb();
        $this->getTableRecords();
        return true;
    }





    private function getExternalStagingDb(): wpdb
    {
        if (!empty($this->clone->databaseSsl) && !defined('MYSQL_CLIENT_FLAGS')) {
            // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
            define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
        }

        return new wpdb($this->clone->databaseUser, $this->clone->databasePassword, $this->clone->databaseDatabase, $this->clone->databaseServer);
    }





    public function getDbName(): string
    {
        return (string)$this->wpdb->dbname;
    }





    protected function isExternalDatabase(): bool
    {
        if (isset($this->isExternalDb)) {
            return $this->isExternalDb;
        }

        return $this->externalDatabaseConfiguration->isEnabled($this->clone);
    }






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





    private function getTableRecords()
    {
        $stagingPrefix = $this->getStagingPrefix();

 
        $prefix = $this->strings->replaceLastMatch('_', '\_', $stagingPrefix);

        if ($this->isExternalDatabase()) { 
            $tables = $this->wpdb->get_results("SHOW TABLE STATUS");
        } else {
            $tables = $this->wpdb->get_results("SHOW TABLE STATUS LIKE '$prefix%'");
        }

        $this->tables = [];

 
        if ($tables !== null) {
            foreach ($tables as $table) {
                $this->tables[] = [
                    "name" => $table->Name,
                    "size" => $this->utilsMath->formatSize($table->Data_length + $table->Index_length),
                ];
            }
        }

        $this->tables = json_decode(json_encode($this->tables));
    }





    private function getStagingPrefix(): string
    {
        if ($this->isExternalDatabase() && !empty($this->clone->databasePrefix)) {
            $this->clone->prefix = $this->clone->databasePrefix;
            return $this->clone->databasePrefix;
        }

 
 
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

 
        if (empty($this->options->databaseUser) && $this->wpdb->prefix === $this->clone->prefix) {
            $this->log("Fatal Error: Can not delete staging site. Prefix. '{$this->clone->prefix}' is used for the production site. Stopping for security reasons. Go to Sites > Actions > Edit Data and correct the table prefix or contact us.");
            $this->returnException("Fatal Error: Can not delete staging site. Prefix. '{$this->clone->prefix}' is used for the production site. Stopping for security reasons. Go to Sites > Actions > Edit Data and correct the table prefix or contact us");
        }

        return $this->clone->prefix;
    }




    public function getClone()
    {
        return $this->clone;
    }




    public function getTables()
    {
        return $this->tables;
    }








    public function start($clone = null)
    {
 
        $this->setData($clone);

 
        $this->getJob();

        $method = "delete" . ucwords($this->job->current);

        if (method_exists($this, $method)) {
            $this->{$method}();
            return;
        }

 
 
        $this->cache->delete();
        $this->start($clone);
    }






    public function getJob()
    {
        $this->job = $this->cache->get();
        $this->job = json_decode(json_encode($this->job)); 

        if ($this->job !== null && isset($this->job->current)) {
            return;
        }

 
        $this->job = (object)[
            "current"               => "tables",
            "nextDirectoryToDelete" => $this->clone->path,
            "name"                  => $this->clone->name,
        ];

        $this->cache->save($this->job);
    }





    private function updateJob(): bool
    {
        $this->job->nextDirectoryToDelete = trim($this->job->nextDirectoryToDelete);
        $result = $this->cache->save($this->job);

        return $result !== false;
    }




    private function getTablesToRemove(): array
    {
        $tables = $this->getTableNames();

        if (!isset($_POST["excludedTables"]) || !is_array($_POST["excludedTables"]) || empty($_POST["excludedTables"])) {
            return $tables;
        }

 
        $sanitizedExcludedTables = $this->sanitize->sanitizeArrayString($_POST["excludedTables"]);

        return array_diff($tables, $sanitizedExcludedTables);
    }




    private function getTableNames(): array
    {
        return (!is_array($this->tables)) ? [] : array_map(function ($value) {
            return ($value->name);
        }, $this->tables);
    }







    public function deleteTables()
    {

        if ($this->isOverThreshold()) {
            $this->log("Deleting: Is over threshold");
            return;
        }

        $tables = $this->getTablesToRemove();

        foreach ($tables as $table) {
 
            if (!$this->isExternalDatabase() && $this->strings->startsWith($table, $this->wpdb->prefix)) {
                $this->log("Fatal Error: Trying to delete table $table of main WP installation!", Logger::TYPE_CRITICAL);
            }

            $this->wpdb->query("DROP TABLE $table");
        }

 
        $this->job->current = "directory";
        $this->updateJob();
    }






    public function deleteDirectory()
    {
        if ($this->isFatalError()) {
            $this->returnException('Can not delete directory: ' . $this->deleteDir . '. This seems to be the root directory. Exclude this directory from deleting and try again.');
            throw new Exception('Can not delete directory: ' . $this->deleteDir . ' This seems to be the root directory. Exclude this directory from deleting and try again.');
        }

 
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

 
        if ($this->deleteDir === get_home_path()) {
            $this->log("Fatal Error 8: Trying to delete root of WP installation!", Logger::TYPE_CRITICAL);
            $this->returnException('Fatal Error 8: Trying to delete root of WP installation!');
        }

 
        if ($this->isOverThreshold()) {
            return;
        }

        $clone        = (string)$this->clone->path;
        $errorMessage = sprintf(__('We could not delete the staging site completely. There are still files in the folder %s that could not be deleted. This could be a write permission issue. Try to delete the folder manually by using FTP or a file manager plugin.<br/> If this happens again please contact us at support@wp-staging.com', 'wp-staging'), $clone);
        $deleteStatus = self::DELETE_STATUS_FINISHED;
        $isDeleted    = false;

        try {
            $isDeleted = $this->cleanStagingDirectory($this->deleteDir);
        } catch (FilesystemExceptions $ex) {
            $errorMessage = $ex->getMessage();
            $deleteStatus = self::DELETE_STATUS_UNFINISHED;
        }

 
        if (!$isDeleted && $deleteStatus !== self::DELETE_STATUS_UNFINISHED) {
            return;
        }

 
        if (!$this->isEmptyDir($this->deleteDir)) {
            $response = [
                'job'     => 'delete',
                'status'  => true,
                'delete'  => $deleteStatus,
                'message' => $errorMessage,
                'error'   => true,
            ];
            wp_die(json_encode($response));
        }

 
        $this->deleteFinish();
    }






    protected function cleanStagingDirectory(string $deleteDir): bool
    {
        if (!is_dir($deleteDir)) {
            return true;
        }

 
        $fs = (new Filesystem())
            ->setShouldStop([$this, 'isOverThreshold'])
            ->shouldPermissionExceptionsBypass(true)
            ->setRecursive();

        try {
            if (!$fs->delete($this->deleteDir)) {
                return false;
            }
        } catch (FilesystemExceptions $ex) {
            throw $ex;
        }

        return true;
    }






    private function isEmptyDir($dir): bool
    {
        if (!is_dir($dir)) {
            return true;
        }

        $iterator = new FilesystemIterator($dir);

        return !$iterator->valid();
    }




    public function isFatalError(): bool
    {
        $homePath = rtrim(get_home_path(), "/");
        return $homePath === rtrim($this->deleteDir, "/");
    }






    public function deleteFinish()
    {
        $response = [
            'delete' => self::DELETE_STATUS_FINISHED,
        ];

        $existingClones = get_option(Sites::STAGING_SITES_OPTION, []);

 
        $this->log("Verifying existing clones...");
        foreach ($existingClones as $name => $clone) {
            if ($clone["path"] === $this->clone->path) {
                unset($existingClones[$name]);
            }
        }

        if (update_option(Sites::STAGING_SITES_OPTION, $existingClones, false) === false) {
            $this->log("Delete: Nothing to save.'");
        }

 
        $this->cache->delete();
        $this->cloneOptionCache->delete();

        wp_die(json_encode($response));
    }








    private function isExternalDatabaseError(): bool
    {
        if ($this->clone->databaseSsl) {
 
            if (!defined('MYSQL_CLIENT_FLAGS')) {
                // phpcs:disable PHPCompatibility.Constants.NewConstants.mysqli_client_ssl_dont_verify_server_certFound
                define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
            }

            $db = mysqli_init();
            // @phpstan-ignore-next-line - null is valid for port and socket parameters
            $db->real_connect($this->clone->databaseServer, $this->clone->databaseUser, $this->clone->databasePassword, $this->clone->databaseDatabase, null, null, MYSQL_CLIENT_FLAGS);
        } else {
            $db = new mysqli($this->clone->databaseServer, $this->clone->databaseUser, $this->clone->databasePassword, $this->clone->databaseDatabase);
        }

        if ($db->connect_error) {
            return true;
        }

        return false;
    }






    private function getJobCacheFileName(): string
    {
        return "delete_job_{$this->clone->name}";
    }
}
