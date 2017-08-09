<?php

namespace WPStaging\Backend\Modules\Jobs;

//ini_set('display_startup_errors', 1);
//ini_set('display_errors', 1);
//error_reporting(-1);

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

use WPStaging\WPStaging;

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Directories
 */
class Directories extends JobExecutable {

    /**
     * @var array
     */
    private $files = array();

    /**
     * @var int
     */
    private $total = 0;

    /**
     * Initialize
     */
    public function initialize() {
        $this->total = count( $this->options->directoriesToCopy );
        $this->getFiles();
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps() {
        $this->options->totalSteps = $this->total;
    }

    /**
     * Get Root Files
     */
    protected function getRootFiles() {
        if( 1 < $this->options->totalFiles ) {
            return;
        }

        $this->getFilesFromDirectory( ABSPATH );
    }

    /**
     * Start Module
     * @return object
     */
    public function start() {
        // Root files
        $this->getRootFiles();

        // Execute steps
        $this->run();

        // Save option, progress
        $this->saveProgress();

        return ( object ) $this->response;
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute() {
        // No job left to execute
        if( $this->isFinished() ) {
            $this->prepareResponse( true, false );
            return false;
        }

        // Get current directory
        $directory = $this->options->directoriesToCopy[$this->options->currentStep];

        // Get files recursively
        if( !$this->getFilesFromSubDirectories( $directory ) ) {
            $this->prepareResponse( false, false );
            return false;
        }

        // Add directory to scanned directories listing
        $this->options->scannedDirectories[] = $directory;

        // Prepare response
        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    private function isFinished() {
        return (
                $this->options->currentStep > $this->total ||
                empty( $this->options->directoriesToCopy ) ||
                !isset( $this->options->directoriesToCopy[$this->options->currentStep] )
                );
    }

    /**
     * @param $path
     * @return bool
     */
    protected function getFilesFromSubDirectories( $path ) {
        $this->totalRecursion++;

        if( $this->isOverThreshold() ) {
            //$this->saveProgress();
            return false;
        }

        $this->log( "Scanning {$path} for its sub-directories and files" );

        $directories = new \DirectoryIterator( $path );

        foreach ( $directories as $directory ) {

            
            // Not a valid directory
            if( false === ($path = $this->getPath( $directory )) ) {
                continue;
            }

            // Excluded directory
            if( $this->isDirectoryExcluded( $directory->getRealPath() ) ) {
                continue;
            }

            // This directory is already scanned
            if( in_array( $path, $this->options->scannedDirectories ) ) {
                continue;
            }

            // Save all files
            $dir = ABSPATH . $path . DIRECTORY_SEPARATOR;
            $this->getFilesFromDirectory( $dir );

            // Add scanned directory listing
            $this->options->scannedDirectories[] = $dir;
        }

        $this->saveOptions();

        // Not finished
        return true;
    }

    /**
     * @param $directory
     * @return bool
     */
    protected function getFilesFromDirectory( $directory ) {
        $this->totalRecursion++;

        // Get only files
        $files = array_diff( scandir( $directory ), array('.', "..") );

        foreach ( $files as $file ) {
            $fullPath = $directory . $file;
            
            // It's a readable valid file and not excluded for copying
            if( is_file( $fullPath ) && !$this->isExcluded($file) ) {
               $this->options->totalFiles++;
               $this->files[] = $fullPath;
               continue;
            }
            // It's a valid file but not readable
            if( is_file( $fullPath ) && !is_readable( $fullPath ) ) {
                    $this->debugLog('File {$fullPath} is not readable', Logger::TYPE_DEBUG );
               continue;
            }

            // Iterate and loop through if it's a directory and if it's not excluded
            if( is_dir( $fullPath ) && !in_array( $fullPath, $this->options->directoriesToCopy ) && !$this->isDirectoryExcluded( $fullPath ) ) {
                $this->options->directoriesToCopy[] = $fullPath;

                //return $this->getFilesFromSubDirectories( $fullPath );
                //continue;
                $this->getFilesFromSubDirectories( $fullPath );
                continue;
            }

//            if( !is_file( $fullPath ) || in_array( $fullPath, $this->files ) ) {
//                continue;
//            }

//            $this->options->totalFiles++;
//
//            $this->files[] = $fullPath;

            /**
             * Test and measure if its faster to copy at the same time while the array with folders is  generated
             */
            //$this->copy($fullPath);

        }
    }

    /**
     * Get Path from $directory
     * @param \SplFileInfo $directory
     * @return string|false
     */
    protected function getPath( $directory ) {
       
      /* 
       * Do not follow root path like src/web/..
       * This must be done before \SplFileInfo->isDir() is used!
       * Prevents open base dir restriction fatal errors
       */
      if (strpos( $directory->getRealPath(), ABSPATH ) !== 0 ) {
         return false;
      }
      
        $path = str_replace( ABSPATH, null, $directory->getRealPath() );

        // Using strpos() for symbolic links as they could create nasty stuff in nix stuff for directory structures
        if( !$directory->isDir() || strlen( $path ) < 1 ) {
            return false;
        }

        return $path;
    }

    /**
     * Check if directory is excluded from copying
     * @param string $directory
     * @return bool
     */
    protected function isDirectoryExcluded( $directory ) {
        foreach ( $this->options->excludedDirectories as $excludedDirectory ) {
            if( strpos( $directory, $excludedDirectory ) === 0 && !$this->isExtraDirectory( $directory ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if directory is an extra directory and should be copied
     * @param string $directory
     * @return boolean
     */
    protected function isExtraDirectory( $directory ) {
        foreach ( $this->options->extraDirectories as $extraDirectory ) {
            if( strpos( $directory, $extraDirectory ) === 0 ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Save files
     * @return bool
     */
    protected function saveProgress() {
        $this->saveOptions();

        $fileName = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();
        $files = implode( PHP_EOL, $this->files );

        if( strlen( $files ) > 0 ) {
            //$files .= PHP_EOL;
        }

        return (false !== @file_put_contents( $fileName, $files ));
    }

    /**
     * Get files
     * @return void
     */
    protected function getFiles() {
        $fileName = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();

        if( false === ($this->files = @file_get_contents( $fileName )) ) {
            $this->files = array();
            return;
        }

        $this->files = explode( PHP_EOL, $this->files );
    }
    
    /**
     * Copy File using PHP (Only for testing)
     * @param string $file
     * @param string $destination
     * @return bool
     * 
     * @deprecated since version 2.0.2
     */
    protected function copy($file)
    {
        
        if( $this->isOverThreshold() ) {
            return false;
        }
        
        // Failed to get destination
        if (false === ($destination = $this->getDestination($file)))
        {
            //$this->log("Can't get the destination of {$file}");
            //return false;
        }

        // Attempt to copy
        if (!@copy($file, $destination))
        {
            //$this->log("Failed to copy file to destination: {$file} -> {$destination}", Logger::TYPE_ERROR);
            //return false;
        }
        
        //$this->log("Copy {$file} -> {$destination}", Logger::TYPE_INFO);

        // Not finished
        return true;
    }

    
    /**
     * (only for testing)
     * Gets destination file and checks if the directory exists, if it does not attempts to create it.
     * If creating destination directory fails, it returns false, gives destination full path otherwise
     * @param string $file
     * @return bool|string
     * 
     * @deprecated
     */
    private function getDestination($file)
    {
        $destination = ABSPATH . $this->options->cloneDirectoryName . DIRECTORY_SEPARATOR;
        $relativePath           = str_replace(ABSPATH, null, $file);
        $destinationPath        = $destination . $relativePath;
        $destinationDirectory   = dirname($destinationPath);

        if (!is_dir($destinationDirectory) && !@mkdir($destinationDirectory, 0775, true))
        {
            $this->log("Destination directory doesn't exist; {$destinationDirectory}", Logger::TYPE_ERROR);
            //return false;
        }

        return $destinationPath;
    }
    
    /**
    * Check if filename is excluded for cloning process
     * 
    * @param string $file filename including ending
    * @return boolean
    */
   private function isExcluded( $file ) {
      $excluded = false;
      foreach ( $this->options->excludedFiles as $excludedFile ) {
         if (stripos(strrev($file), strrev($excludedFile)) === 0) {
           $excluded = true;
           break;
         }
      }
      return $excluded;
   }

}
