<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\Utils\Directories;
use WPStaging\WPStaging;

/**
 * Class Scan
 * @package WPStaging\Backend\Modules\Jobs
 */
class Scan extends Job
{

    /**
     * @var array
     */
    private $directories = array();

    /**
     * @var Directories
     */
    private $objDirectories;
    

    /**
     * Upon class initialization
     */
    protected function initialize()
    {
        $this->objDirectories = new Directories();
                
        // Database Tables
        $this->getTables();

        // Get directories
        $this->directories();
        
        $this->db = WPStaging::getInstance()->get('wpdb');
        $this->prefix = $this->db->prefix;
        
                
    }

    /**
     * Start Module
     * @return $this
     */
    public function start()
    {
        // Basic Options
        $this->options->root                    = str_replace(array("\\", '/'), DIRECTORY_SEPARATOR, ABSPATH);
        $this->options->existingClones          = get_option("wpstg_existing_clones_beta", array());
        $this->options->current                 = null;

        if (isset($_POST["clone"]) && array_key_exists($_POST["clone"], $this->options->existingClones))
        {
            $this->options->current = $_POST["clone"];
        }

        // Tables
        //$this->options->excludedTables          = array();
        $this->options->clonedTables            = array();

        // Files
        $this->options->totalFiles              = 0;
        $this->options->totalFileSize           = 0;
        $this->options->copiedFiles             = 0;


        // Directories
        $this->options->includedDirectories     = array();
        $this->options->includedExtraDirectories= array();
        $this->options->excludedDirectories     = array();
        $this->options->extraDirectories        = array();
        $this->options->directoriesToCopy       = array();
        $this->options->scannedDirectories      = array();

        // Job
        $this->options->currentJob              = "database";
        $this->options->currentStep             = 0;
        $this->options->totalSteps              = 0;

        // Delete previous cached files
        $this->cache->delete("files_to_copy");
        $this->cache->delete("clone_options");
        //$this->cache->delete("files_to_verify");
        //$this->cache->delete("files_verified");

        // Save options
        $this->saveOptions();

        return $this;
    }

    /**
     * Format bytes into human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize($bytes, $precision = 2)
    {
        if ((double) $bytes < 1)
        {
            return '';
        }

        $units  = array('B', "KB", "MB", "GB", "TB");

        $bytes  = (double) $bytes;
        $base   = log($bytes) / log(1000); // 1024 would be for MiB KiB etc
        $pow    = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $precision) . ' ' . $units[(int) floor($base)];
    }

    /**
     * @param null|string $directories
     * @param bool $forceDisabled
     * @return string
     */
    public function directoryListing($directories = null, $forceDisabled = false)
    {
        if (null == $directories)
        {
            $directories = $this->directories;
        }

        // Sort results
        uksort($directories, 'strcasecmp');
        
        $output = '';
        foreach ($directories as $name => $directory)
        {
            // Not a directory, possibly a symlink, therefore we will skip it           
            if (!is_array($directory)) {
                continue;
            }
           
            // Need to preserve keys so no array_shift()
            $data = reset($directory);
            unset($directory[key($directory)]);

            $isChecked = (
                empty($this->options->includedDirectories) ||
                in_array($data["path"], $this->options->includedDirectories)
            );

            //$isDisabled = ($this->options->existingClones && isset($this->options->existingClones[$name]));

            // Include wp core folders and their sub dirs. 
            // Exclude all other folders (default setting)
            $isDisabled = ($name !== 'wp-admin' && 
                          $name !== 'wp-includes' && 
                          $name !== 'wp-content') &&
                          false === strpos( strrev($data["path"]), strrev("wp-admin") ) &&
                          false === strpos( strrev($data["path"]), strrev("wp-includes") ) &&
                          false === strpos( strrev($data["path"]), strrev("wp-content") )
                           ? true : false;
            
            // Extra class to differentiate between wp core and non core folders
            $class = !$isDisabled ? 'wpstg-root' : 'wpstg-extra';
            

            $output .= "<div class='wpstg-dir'>";
            $output .= "<input type='checkbox' class='wpstg-check-dir " . $class . "'";

            if ($isChecked && !$isDisabled && !$forceDisabled) $output .= " checked";
            //if ($forceDisabled || $isDisabled) $output .= " disabled";

            $output .= " name='selectedDirectories[]' value='{$data["path"]}'>";

            $output .= "<a href='#' class='wpstg-expand-dirs ";
            if (!$isChecked || $isDisabled) $output .= " disabled";
            $output .= "'>{$name}";
            $output .= "</a>";

            $output .= "<span class='wpstg-size-info'>{$this->formatSize($data["size"])}</span>";

            if (!empty($directory))
            {
                $output .= "<div class='wpstg-dir wpstg-subdir'>";
                $output .= $this->directoryListing($directory, $isDisabled);
                $output .= "</div>";
            }

            $output .= "</div>";
        }

        return $output;
    }

    /**
     * Checks if there is enough free disk space to create staging site
     * Returns null when can't run disk_free_space function one way or another
     * @return bool|null
     */
    public function hasFreeDiskSpace() {
      if( !function_exists( "disk_free_space" ) ) {
         return null;
      }

      $freeSpace = @disk_free_space( ABSPATH );

      if( false === $freeSpace ) {
         $data = array(
             'freespace' => false,
             'usedspace' => $this->formatSize($this->getDirectorySizeInclSubdirs(ABSPATH))
         );
         echo json_encode($data);
         die();
      }


      $data = array(
          'freespace' => $this->formatSize($freeSpace),
          'usedspace' => $this->formatSize($this->getDirectorySizeInclSubdirs(ABSPATH))
      );

      echo json_encode( $data );
      die();
   }

   /**
     * Get Database Tables
     */
    protected function getTables()
    {
        $wpDB = WPStaging::getInstance()->get("wpdb");

        if (strlen($wpDB->prefix) > 0)
        {
            $prefix = str_replace('_', '', $wpDB->prefix);
            $sql = "SHOW TABLE STATUS LIKE '{$wpDB->prefix}%'";
        }
        else
        {
            $sql = "SHOW TABLE STATUS";
        }
        
        $tables = $wpDB->get_results($sql);
        
        $currentTables = array();
          
        // Reset excluded Tables than loop through all tables
        $this->options->excludedTables = array();
        foreach ($tables as $table)
        {
            
            // Exclude WP Staging Tables
//            if (0 === strpos($table->Name, "wpstg"))
//            {
//                continue;
//            }
            // Create array of unchecked tables
            if (!empty($wpDB->prefix) && 0 !== strpos($table->Name, $wpDB->prefix))
            {
                $this->options->excludedTables[] = $table->Name;
            }
            

            $currentTables[] = array(
                "name"  => $table->Name,
                "size"  => ($table->Data_length + $table->Index_length)
            );
        }

        $this->options->tables = json_decode(json_encode($currentTables));
    }

    /**
     * Get directories and main meta data about'em recursively
     */
    protected function directories()
    {
        $directories = new \DirectoryIterator(ABSPATH);

        foreach($directories as $directory)
        {
            // Not a valid directory
            if (false === ($path = $this->getPath($directory)))
            {
                continue;
            }

            $this->handleDirectory($path);

            // Get Sub-directories
            $this->getSubDirectories($directory->getRealPath());
        }

        // Gather Plugins
        $this->getSubDirectories(WP_PLUGIN_DIR);

        // Gather Themes
        $this->getSubDirectories(WP_CONTENT_DIR  . DIRECTORY_SEPARATOR . "themes");

        // Gather Uploads
        $this->getSubDirectories(WP_CONTENT_DIR  . DIRECTORY_SEPARATOR . "uploads");
    }

    /**
     * @param string $path
     */
    protected function getSubDirectories($path)
    {
        $directories = new \DirectoryIterator($path);

        foreach($directories as $directory)
        {
            // Not a valid directory
            if (false === ($path = $this->getPath($directory)))
            {
                continue;
            }

            $this->handleDirectory($path);
        }
    }

    /**
     * Get Path from $directory
     * @param \SplFileInfo $directory
     * @return string|false
     */
    protected function getPath($directory)
    {
       
      /* 
       * Do not follow root path like src/web/..
       * This must be done before \SplFileInfo->isDir() is used!
       * Prevents open base dir restriction fatal errors
       */
      if (strpos( $directory->getRealPath(), ABSPATH ) !== 0 ) {
         return false;
      }
        $path = str_replace(ABSPATH, null, $directory->getRealPath());

        // Using strpos() for symbolic links as they could create nasty stuff in nix stuff for directory structures
        if (!$directory->isDir() || strlen($path) < 1)
        {
            return false;
        }

        return $path;
    }

    /**
     * Organizes $this->directories
     * @param string $path
     */
    protected function handleDirectory($path)
    {
        $directoryArray = explode(DIRECTORY_SEPARATOR, $path);
        $total          = is_array($directoryArray) || $directoryArray instanceof Countable ? count($directoryArray) : 0;


        if ($total < 1)
        {
            return;
        }

        $total          = $total - 1;
        $currentArray   = &$this->directories;

        for ($i = 0; $i <= $total; $i++)
        {
            if (!isset($currentArray[$directoryArray[$i]]))
            {
                $currentArray[$directoryArray[$i]] = array();
            }

            $currentArray = &$currentArray[$directoryArray[$i]];

            // Attach meta data to the end
            if ($i < $total)
            {
                continue;
            }

            $fullPath   = ABSPATH . $path;
            $size       = $this->getDirectorySize($fullPath);

            $currentArray["metaData"] = array(
                "size"      => $size,
                "path"      => ABSPATH . $path,
            );
        }
    }

    /**
     * Gets size of given directory
     * @param string $path
     * @return int|null
     */
    protected function getDirectorySize($path)
    {
        if (!isset($this->settings->checkDirectorySize) || '1' !== $this->settings->checkDirectorySize)
        {
            return null;
        }

        return $this->objDirectories->size($path);
    }
    
    /**
     * Get total size of a directory including all its subdirectories
     * @param string $dir
     * @return int
     */
    function getDirectorySizeInclSubdirs( $dir ) {
      $size = 0;
      foreach ( glob( rtrim( $dir, '/' ) . '/*', GLOB_NOSORT ) as $each ) {
         $size += is_file( $each ) ? filesize( $each ) : $this->getDirectorySizeInclSubdirs( $each );
      }
      return $size;
   }

}