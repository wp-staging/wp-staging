<?php
namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Exceptions\CloneNotFoundException;
//use WPStaging\Utils\Directories;
use WPStaging\Utils\Logger;
use WPStaging\WPStaging;

/**
 * Class Delete
 * @package WPStaging\Backend\Modules\Jobs
 */
class Delete extends Job
{

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
     * Sets Clone and Table Records
     * @param null|array $clone
     */
    public function setData($clone = null)
    {
        if (!is_array($clone))
        {
            $this->getCloneRecords();
        }
        else
        {
            $this->clone                    = (object) $clone;
            $this->forceDeleteDirectories   = true;
        }

        $this->getTableRecords();
    }

    /**
     * Get clone
     * @param null|string $name
     * @throws CloneNotFoundException
     */
    private function getCloneRecords($name = null)
    {
        if (null === $name && !isset($_POST["clone"]))
        {
            $this->log("Clone name is not set", Logger::TYPE_FATAL);
            throw new CloneNotFoundException();
        }

        if (null === $name)
        {
            $name = $_POST["clone"];
        }

        $clones = get_option("wpstg_existing_clones_beta", array());

        if (empty($clones) || !isset($clones[$name]))
        {
            $this->log("Couldn't find clone name {$name} or no existing clone", Logger::TYPE_FATAL);
            //throw new CloneNotFoundException();
        }

        $this->clone            = $clones[$name];
        $this->clone["name"]    = $name;

        $this->clone = (object) $this->clone;

        unset($clones);
    }

    /**
     * Get Tables
     */
    private function getTableRecords()
    {
        $wpdb   = WPStaging::getInstance()->get("wpdb");
        
        $tables = $wpdb->get_results("SHOW TABLE STATUS LIKE 'wpstg{$this->clone->number}_%'");

        $this->tables = array();

        foreach ($tables as $table)
        {
            $this->tables[] = array(
                "name"  => $table->Name,
                "size"  => $this->formatSize(($table->Data_length + $table->Index_length))
            );
        }

        $this->tables = json_decode(json_encode($this->tables));
    }
    
        /**
     * Format bytes into human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize($bytes, $precision = 2)
    {
        if ((int) $bytes < 1)
        {
            return '';
        }

        $units  = array('B', "KB", "MB", "GB", "TB");

        $bytes  = (int) $bytes;
        $base   = log($bytes) / log(1000); // 1024 would be for MiB KiB etc
        $pow    = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $precision) . ' ' . $units[(int) floor($base)];
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
     * @return bool
     */
    public function start($clone = null)
    {
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
      $this->job = $this->cache->get( "delete_job_{$this->clone->name}" );


      if( null !== $this->job ) {
         return;
      }

      // Generate JOB
      $this->job = ( object ) array(
                  "current" => "tables",
                  "nextDirectoryToDelete" => $this->clone->path,
                  "name" => $this->clone->name
      );

      $this->cache->save( "delete_job_{$this->clone->name}", $this->job );
   }
   


   /**
     * @return bool
     */
    private function updateJob()
    {
        $this->job->nextDirectoryToDelete = trim($this->job->nextDirectoryToDelete);
        return $this->cache->save("delete_job_{$this->clone->name}", $this->job);
    }

    /**
     * @return array
     */
    private function getTablesToRemove()
    {
        $tables = $this->getTableNames();

        if (!isset($_POST["excludedTables"]) || !is_array($_POST["excludedTables"]) || empty($_POST["excludedTables"]))
        {
            return $tables;
        }

        return array_diff($tables, $_POST["excludedTables"]);
    }

    /**
     * @return array
     */
    private function getTableNames()
    {
        return (!is_array($this->tables)) ? array() : array_map(function($value) {
            return ($value->name);
        }, $this->tables);
    }

    /**
     * Delete Tables
     */
    public function deleteTables()
    {
        if ($this->isOverThreshold())
        {
            return;
        }

        $wpdb = WPStaging::getInstance()->get("wpdb");

        foreach ($this->getTablesToRemove() as $table)
        {
            // PROTECTION: Never delete any table that beginns with wp prefix of live site
            if($this->startsWith($table, $wpdb->prefix)){
                $this->log("Fatal Error: Trying to delete table {$table} of main WP installation!", Logger::TYPE_CRITICAL);
                return false;
            } else{
                $wpdb->query("DROP TABLE {$table}");
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
    protected function startsWith($haystack, $needle)
    {
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
    }
    
   /**
    * Delete a specific directory and all of its subfolders in a native way without using any external caching data
    * 
    * @param array $dir
    * @param array $excluded_dirs
    * @return boolean false when its finished
    */
   function deleteDirectoryNative( $dir = '' ) {

      if( !file_exists( $dir ) ) {
         return $this->isFinished();
      }

      if( !is_dir( $dir ) || is_link( $dir ) ) {
         unlink( $dir );
         return $this->isFinished();
      }
      foreach ( scandir( $dir ) as $item ) {
         if( $item == '.' || $item == '..' ) {
            continue;
         }
         if( !$this->deleteDirectoryNative( $dir . "/" . $item, false ) ) {
            //chmod( $dir . "/" . $item, 0777 );
            //if( !$this->deleteDirectoryNative( $dir . "/" . $item, false ) ){
               //return false;
            //}
         }
      };

      rmdir( $dir );
      return $this->isFinished();
   }
   


   /**
     * Delete Directories
     */
    public function deleteDirectory()
    {
        // No deleting directories or root of this clone is deleted
        if ($this->isDirectoryDeletingFinished())
        {
            $this->job->current = "finish";
            $this->updateJob();
            return;
        }

        $this->processDirectory($this->job->nextDirectoryToDelete);

        return;
    }

    /**
     * @return bool
     */
    public function isDirectoryDeletingFinished()
    {
        return (
            !is_dir($this->clone->path) ||
            (false === $this->forceDeleteDirectories && (!isset($_POST["deleteDir"]) || '1' !== $_POST["deleteDir"])) ||
             ABSPATH === $this->job->nextDirectoryToDelete
        );
    }

    /**
     * Delete contents of the directory if there are no directories in it and then delete itself
     * @param string $path
     * @return mixed
     */
    private function processDirectory($path)
    {
        // We hit the limit, stop
        if ($this->shouldStop($path))
        {
            $this->updateJob();
            return false;
        }

        $this->totalRecursion++;

        $contents = new \DirectoryIterator($path);

        foreach ($contents as $content)
        {
            // Skip dots
            if ($content->isDot())
            {
                continue;
            }

            // Get into the directory
            if (!$content->isLink() && $content->isDir())
            {
                return $this->processDirectory($content->getRealPath());
            }

            // Delete file
            if ($content->isFile())
            {
                @unlink($content->getRealPath());
            }
        }

        // Delete directory
        $this->job->lastDeletedDirectory = realpath($path . "/..");
        @rmdir($path);
        $this->updateJob();
        $this->processDirectory($this->job->nextDirectoryToDelete);
    }

    /**
     * @param string $path
     * @return bool
     */
    private function shouldStop($path)
    {
        // Just to make sure the root dir is never deleted!
        if ($path === get_home_path()){
            $this->log("Fatal Error: Trying to delete root of WP installation!", Logger::TYPE_CRITICAL);
            return true;
        }
        
        // Check if threshold is reached and is valid dir
        return (
            $this->isOverThreshold() ||
            !is_dir($path) || 
            $this->isDirectoryDeletingFinished()
        );
    }

    /**
     * Finish / Update Existing Clones
     */
    public function deleteFinish()
    {
        $existingClones = get_option("wpstg_existing_clones_beta", array());

        // Check if clones still exist
        $this->log("Verifying existing clones...");
        foreach ($existingClones as $name => $clone)
        {
            if (!is_dir($clone["path"]))
            {
                unset($existingClones[$name]);
            }
        }
        $this->log("Existing clones verified!");

        if (false === update_option("wpstg_existing_clones_beta", $existingClones))
        {
            $this->log("Failed to save {$this->options->clone}'s clone job data to database'");
        }

        // Delete cached file
        $this->cache->delete("delete_job_{$this->clone->name}");
        $this->cache->delete("delete_directories_{$this->clone->name}");

        //return true;
        $response = array('delete' => 'finished');
        wp_die(json_encode($response));
    }
}