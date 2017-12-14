<?php

namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Exceptions\CloneNotFoundException;
use WPStaging\Utils\Directories;
use WPStaging\Utils\Logger;
use WPStaging\WPStaging;

/**
 * Class Delete
 * @package WPStaging\Backend\Modules\Jobs
 */
class Delete extends Job {

    /**
     * @var false
     */
    private $clone = false;

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
    private $forceDeleteDirectories = false;

    /**
     *
     * @var object 
     */
    public $wpdb;

    public function __construct() {
        parent::__construct();
        $this->wpdb = WPStaging::getInstance()->get("wpdb");
    }

    /**
     * Sets Clone and Table Records
     * @param null|array $clone
     */
    public function setData($clone = null) {
        if (!is_array($clone)) {
            $this->getCloneRecords();
        } else {
            $this->clone = (object) $clone;
            $this->forceDeleteDirectories = true;
        }

        $this->getTableRecords();
    }

    /**
     * Get clone
     * @param null|string $name
     * @throws CloneNotFoundException
     */
    private function getCloneRecords($name = null) {
        if (null === $name && !isset($_POST["clone"])) {
            $this->log("Clone name is not set", Logger::TYPE_FATAL);
            throw new CloneNotFoundException();
        }

        if (null === $name) {
            $name = $_POST["clone"];
        }

        $clones = get_option("wpstg_existing_clones_beta", array());

        if (empty($clones) || !isset($clones[$name])) {
            $this->log("Couldn't find clone name {$name} or no existing clone", Logger::TYPE_FATAL);
            throw new CloneNotFoundException();
        }

        $this->clone = $clones[$name];
        $this->clone["name"] = $name;

        $this->clone = (object) $this->clone;

        unset($clones);
    }

    /**
     * Get Tables
     */
    private function getTableRecords() {
        //$wpdb   = WPStaging::getInstance()->get("wpdb");
        $this->wpdb = WPStaging::getInstance()->get("wpdb");

        $stagingPrefix = $this->getStagingPrefix();

        $tables = $this->wpdb->get_results("SHOW TABLE STATUS LIKE '{$stagingPrefix}%'");

        $this->tables = array();

        foreach ($tables as $table) {
            $this->tables[] = array(
                "name" => $table->Name,
                "size" => $this->formatSize(($table->Data_length + $table->Index_length))
            );
        }

        $this->tables = json_decode(json_encode($this->tables));
    }

    /**
     * Check and return prefix of the staging site
     */
    public function getStagingPrefix() {
        // Prefix not defined! Happens if staging site has ben generated with older version of wpstg
        // Try to get staging prefix from wp-config.php of staging site
        if (empty($this->clone->prefix)) {
            // Throw error
            $path = ABSPATH . $this->clone->directoryName . "/wp-config.php";
            if (false === ($content = @file_get_contents($path))) {
                $this->log("Can not open {$path}. Can't read contents", Logger::TYPE_ERROR);
                // Create a random prefix which hopefully never exists.
                $this->clone->prefix = rand(7, 15) . '_';
            } else {

                // Get prefix from wp-config.php
                //preg_match_all("/table_prefix\s*=\s*'(\w*)';/", $content, $matches);
                preg_match("/table_prefix\s*=\s*'(\w*)';/", $content, $matches);
                //wp_die(var_dump($matches));

                if (!empty($matches[1])) {
                    $this->clone->prefix = $matches[1];
                } else {
                    $this->log("Fatal Error: Can not delete staging site. Can not find Prefix. '{$matches[1]}'. Stopping for security reasons. Creating a new staging site will likely resolve this the next time. Contact support@wp-staging.com");
                    // Create a random prefix which hopefully never exists.
                    return $this->clone->prefix = rand(7, 15) . '_';
                }
            }
        }

        // Check if staging prefix is the same as the live prefix
        if ($this->wpdb->prefix == $this->clone->prefix) {
            $this->log("Fatal Error: Can not delete staging site. Prefix. '{$this->clone->prefix}' is used for the live site. Creating a new staging site will likely resolve this the next time. Stopping for security reasons. Contact support@wp-staging.com");
            wp_die("Fatal Error: Can not delete staging site. Prefix. '{$this->clone->prefix}' is used for the live site. Creating a new staging site will likely resolve this the next time. Stopping for security reasons. Contact support@wp-staging.com");
        }

        // Else
        return $this->clone->prefix;
    }

    /**
     * Format bytes into human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize($bytes, $precision = 2) {
        if ((int) $bytes < 1) {
            return '';
        }

        $units = array('B', "KB", "MB", "GB", "TB");

        $bytes = (int) $bytes;
        $base = log($bytes) / log(1000); // 1024 would be for MiB KiB etc
        $pow = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $precision) . ' ' . $units[(int) floor($base)];
    }

    /**
     * @return false
     */
    public function getClone() {
        return $this->clone;
    }

    /**
     * @return null|object
     */
    public function getTables() {
        return $this->tables;
    }

    /**
     * Start Module
     * @param null|array $clone
     * @return bool
     */
    public function start($clone = null) {
        // Set data
        $this->setData($clone);

        // Get the job first
        $this->getJob();

        $method = "delete" . ucwords($this->job->current);
        return $this->{$method}();
    }

    /**
     * Get job data
     */
    private function getJob() {
        $this->job = $this->cache->get("delete_job_{$this->clone->name}");


        if (null !== $this->job) {
            return;
        }

        // Generate JOB
        $this->job = (object) array(
                    "current" => "tables",
                    "nextDirectoryToDelete" => $this->clone->path,
                    "name" => $this->clone->name
        );

        $this->cache->save("delete_job_{$this->clone->name}", $this->job);
    }

    /**
     * @return bool
     */
    private function updateJob() {
        $this->job->nextDirectoryToDelete = trim($this->job->nextDirectoryToDelete);
        return $this->cache->save("delete_job_{$this->clone->name}", $this->job);
    }

    /**
     * @return array
     */
    private function getTablesToRemove() {
        $tables = $this->getTableNames();

        if (!isset($_POST["excludedTables"]) || !is_array($_POST["excludedTables"]) || empty($_POST["excludedTables"])) {
            return $tables;
        }

        return array_diff($tables, $_POST["excludedTables"]);
    }

    /**
     * @return array
     */
    private function getTableNames() {
        return (!is_array($this->tables)) ? array() : array_map(function($value) {
                    return ($value->name);
                }, $this->tables);
    }

    /**
     * Delete Tables
     */
    public function deleteTables() {
        if ($this->isOverThreshold()) {
            return;
        }

        //$wpdb = WPStaging::getInstance()->get("wpdb");

        foreach ($this->getTablesToRemove() as $table) {
            // PROTECTION: Never delete any table that beginns with wp prefix of live site
            if ($this->startsWith($table, $this->wpdb->prefix)) {
                $this->log("Fatal Error: Trying to delete table {$table} of main WP installation!", Logger::TYPE_CRITICAL);
                return false;
            } else {
                $this->wpdb->query("DROP TABLE {$table}");
            }
        }

        // Move on to the next
        $this->job->current = "directory";
        $this->updateJob();
    }

    /**
     * Check if a strings start with a specific string
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    protected function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Delete complete directory including all files and subfolders
     * 
     * @throws InvalidArgumentException
     */
    public function deleteDirectory() {
        if ($this->isFatalError()) {
            $this->returnException('Can not delete directory: ' . $this->clone->path . '. This seems to be the root directory. Please contact support@wp-staging.com');
            throw new \Exception('Can not delete directory: ' . $this->clone->path . ' This seems to be the root directory. Please contact support@wp-staging.com');
        }
        // Finished or path does not exist
        if (
                empty($this->clone->path) ||
                $this->clone->path == get_home_path() ||
                !is_dir($this->clone->path)) {

            $this->job->current = "finish";
            $this->updateJob();
            return $this->returnFinish();
        }

        $this->log("Delete staging site: " . $this->clone->path, Logger::TYPE_INFO);

        // Just to make sure the root dir is never deleted!
        if ($this->clone->path == get_home_path()) {
            $this->log("Fatal Error 8: Trying to delete root of WP installation!", Logger::TYPE_CRITICAL);
            $this->returnException('Fatal Error 8: Trying to delete root of WP installation!');
        }

        // Check if threshold is reached
        if ($this->isOverThreshold()) {
            //$this->returnException('Maximum PHP execution time exceeded. Run again and repeat the deletion process until it is sucessfully finished.');
            return;
        }

        $di = new \RecursiveDirectoryIterator($this->clone->path, \FilesystemIterator::SKIP_DOTS);
        $ri = new \RecursiveIteratorIterator($di, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($ri as $file) {
            //$file->isDir() ? @rmdir($file) : unlink($file);
            $this->deleteFile($file);
            if ($this->isOverThreshold()) {
                //$this->returnException('Maximum PHP execution time exceeded. Run again and repeat the deletion process until it is sucessfully finished.');
                return;
            }
        }

        if (@rmdir($this->clone->path)) {
            return $this->returnFinish();
        }
        return;
    }

    /**
     * Delete file
     * @param object iterator $file
     */
    private function deleteFile($file) {
        if ($file->isDir()) {
            if (!@rmdir($file)) {
                $this->returnException('Permission Error: Can not delete folder ' . $file);
            }
        } else {
            if (!unlink($file)) {
                $this->returnException('Permission Error: Can not delete file ' . $file);
            }
        }
    }

    /**
     * @return bool
     */
    public function isDirectoryDeletingFinished() {
        return (
                (false === $this->forceDeleteDirectories && (!isset($_POST["deleteDir"]) || '1' !== $_POST["deleteDir"])) ||
                !is_dir($this->clone->path) || ABSPATH === $this->job->nextDirectoryToDelete
                );
    }

    /**
     * Delete contents of the directory if there are no directories in it and then delete itself
     * @param string $path
     * @return mixed
     */
//    private function processDirectory($path) {
//        // We hit the limit, stop
//        if ($this->shouldStop($path)) {
//            $this->updateJob();
//            return false;
//        }
//
//        $this->totalRecursion++;
//
//        $contents = new \DirectoryIterator($path);
//
//        foreach ($contents as $content => $value) {
//
//            // Skip dots
//            if ($content->isDot())
//
//
//            // Get into the directory
//                if (!$content->isLink() && $content->isDir()) {
//                    return $this->processDirectory($content->getRealPath());
//                }
//
//            // Delete file
//            if ($content->isFile()) {
//                @unlink($content->getRealPath());
//            }
//        }
//
//        // Delete directory
//        $this->job->lastDeletedDirectory = realpath($path . "/..");
//        @rmdir($path);
//        $this->updateJob();
//        $this->processDirectory($this->job->nextDirectoryToDelete);
//    }

    /**
     * @param string $path
     * @return bool
     */
//    private function shouldStop($path) {
//        // Just to make sure the root dir is never deleted!
//        if ($path === get_home_path()) {
//            $this->log("Fatal Error: Trying to delete root of WP installation!", Logger::TYPE_CRITICAL);
//            return true;
//        }
//
//        // Check if threshold is reached and is valid dir
//        return (
//                $this->isOverThreshold() ||
//                !is_dir($path) ||
//                $this->isDirectoryDeletingFinished()
//                );
//    }

    /**
     * 
     * @return boolean
     */
    public function isFatalError() {
        if (rtrim($this->clone->path, "/") == rtrim(get_home_path(), "/")) {
            return true;
        }
        return false;
    }

    /**
     * Finish / Update Existing Clones
     */
    public function deleteFinish() {
        $existingClones = get_option("wpstg_existing_clones_beta", array());

        // Check if clones still exist
        $this->log("Verifying existing clones...");
        foreach ($existingClones as $name => $clone) {
            if (!is_dir($clone["path"])) {
                unset($existingClones[$name]);
            }
        }
        $this->log("Existing clones verified!");

        if (false === update_option("wpstg_existing_clones_beta", $existingClones)) {
            $this->log("Failed to save {$this->options->clone}'s clone job data to database'");
        }

        // Delete cached file
        $this->cache->delete("delete_job_{$this->clone->name}");
        $this->cache->delete("delete_directories_{$this->clone->name}");

        //return true;
        $response = array('delete' => 'finished');
        wp_die(json_encode($response));
    }

    /**
     * Get json response
     * return json
     */
//    private function returnException($message = ''){
//        wp_die( json_encode(array(
//                  'job'     => 'delete',
//                  'status'  => false,
//                  'message' => $message,
//                  'error' => true
//            )));
//    }
    /**
     * Get json response
     * return json
     */
    private function returnFinish($message = '') {

        $this->deleteFinish();

        wp_die(json_encode(array(
            'job' => 'delete',
            'status' => true,
            'message' => $message,
            'error' => false,
            'delete' => 'finished'
        )));
    }

}
