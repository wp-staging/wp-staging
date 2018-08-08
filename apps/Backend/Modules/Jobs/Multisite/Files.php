<?php

namespace WPStaging\Backend\Modules\Jobs\Multisite;

use WPStaging\Backend\Modules\Jobs\JobExecutable;
// No Direct Access
use WPStaging\Utils\Logger;

if( !defined( "WPINC" ) ) {
   die;
}

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Jobs
 */
class Files extends JobExecutable {

   /**
    * @var \SplFileObject
    */
   private $file;

   /**
    * @var int
    */
   private $maxFilesPerRun;

   /**
    * @var string
    */
   private $destination;

   /**
    * Initialization
    */
   public function initialize() {

      $this->destination = ABSPATH . $this->options->cloneDirectoryName . DIRECTORY_SEPARATOR;

      $filePath = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();

      if( is_file( $filePath ) ) {
         $this->file = new \SplFileObject( $filePath, 'r' );
      }

      // Informational logs
      if( 0 == $this->options->currentStep ) {
         $this->log( "Copying files..." );
      }

      $this->settings->batchSize = $this->settings->batchSize * 1000000;
      $this->maxFilesPerRun = $this->settings->fileLimit;
   }

   /**
    * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
    * @return void
    */
   protected function calculateTotalSteps() {
      $this->options->totalSteps = ceil( $this->options->totalFiles / $this->maxFilesPerRun );
   }

   /**
    * Execute the Current Step
    * Returns false when over threshold limits are hit or when the job is done, true otherwise
    * @return bool
    */
   protected function execute() {
      // Finished
      if( $this->isFinished() ) {
         $this->log( "Copying files finished" );
         $this->prepareResponse( true, false );
         return false;
      }

      // Get files and copy'em
      if( !$this->getFilesAndCopy() ) {
         $this->prepareResponse( false, false );
         return false;
      }

      // Prepare and return response
      $this->prepareResponse();

      // Not finished
      return true;
   }

   /**
    * Get files and copy
    * @return bool
    */
   private function getFilesAndCopy() {
      // Over limits threshold
      if( $this->isOverThreshold() ) {
         // Prepare response and save current progress
         $this->prepareResponse( false, false );
         $this->saveOptions();
         return false;
      }

      // Go to last copied line and than to next one
      //if ($this->options->copiedFiles != 0) {
      if( isset( $this->options->copiedFiles ) && $this->options->copiedFiles != 0 ) {
         $this->file->seek( $this->options->copiedFiles - 1 );
      }

      $this->file->setFlags( \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD );


      // Loop x files at a time
      for ( $i = 0; $i < $this->maxFilesPerRun; $i++ ) {

         // Increment copied files
         // Do this anytime to make sure to not stuck in the same step / files
         $this->options->copiedFiles++;

         // End of file
         if( $this->file->eof() ) {
            break;
         }


         $file = $this->file->fgets();

         $this->copyFile( $file );
      }

      $totalFiles = $this->options->copiedFiles;
      // Log this only every 50 entries to keep the log small and to not block the rendering browser
      if( $this->options->copiedFiles % 50 == 0 ) {
         $this->log( "Total {$totalFiles} files processed" );
      }

      return true;
   }

   /**
    * Checks Whether There is Any Job to Execute or Not
    * @return bool
    */
   private function isFinished() {
      return (
              $this->options->currentStep > $this->options->totalSteps ||
              $this->options->copiedFiles >= $this->options->totalFiles
              );
   }

   /**
    * @param string $file
    * @return bool
    */
   private function copyFile( $file ) {
      $file = trim( ABSPATH . $file );

      $directory = dirname( $file );

      // Get file size
      $fileSize = filesize( $file );

      // Directory is excluded
      if( $this->isDirectoryExcluded( $directory ) ) {
         $this->debugLog( "Skipping directory by rule: {$file}", Logger::TYPE_INFO );
         return false;
      }

      // File is excluded
      if( $this->isFileExcluded( $file ) ) {
         $this->log( "Skipping file by rule: {$file}", Logger::TYPE_INFO );
         return false;
      }

      // File is over maximum allowed file size (8MB)
      if( $fileSize >= $this->settings->maxFileSize * 1000000 ) {
         $this->log( "Skipping big file: {$file}", Logger::TYPE_INFO );
         return false;
      }

      // Invalid file, skipping it as if succeeded
      if( !is_file( $file ) || !is_readable( $file ) ) {
         $this->log( "Can't read file or file doesn't exist {$file}" );
         return true;
      }

      // Failed to get destination
      if( false === ($destination = $this->getDestination( $file )) ) {
         $this->log( "Can't get the destination of {$file}" );
         return false;
      }

      // File is over batch size
      if( $fileSize >= $this->settings->batchSize ) {
         $this->log( "Trying to copy big file: {$file} -> {$destination}", Logger::TYPE_INFO );
         return $this->copyBig( $file, $destination, $this->settings->batchSize );
      }

      // Attempt to copy
      if( !@copy( $file, $destination ) ) {
         $errors = error_get_last();
         $this->log( "Files: Failed to copy file to destination. Error: {$errors['message']} {$file} -> {$destination}", Logger::TYPE_ERROR );
         return false;
      }

      return true;

      // Good old PHP
      //return $this->copy($file, $destination);
   }

   /**
    * Gets destination file and checks if the directory exists, if it does not attempts to create it.
    * If creating destination directory fails, it returns false, gives destination full path otherwise
    * @param string $file
    * @return bool|string
    */
   private function getDestination( $file ) {
      $file = $this->replaceMultisiteUploadFolder( $file );
      $relativePath = str_replace( ABSPATH, null, $file );
      $destinationPath = $this->destination . $relativePath;
      $destinationDirectory = dirname( $destinationPath );

      if( !is_dir( $destinationDirectory ) && !@mkdir( $destinationDirectory, 0775, true ) ) {
         $this->log( "Files: Can not create directory {$destinationDirectory}", Logger::TYPE_ERROR );
         return false;
      }

      return $destinationPath;
   }

   /**
    * Copy bigger files than $this->settings->batchSize
    * @param string $src
    * @param string $dst
    * @param int $buffersize
    * @return boolean
    */
   private function copyBig( $src, $dst, $buffersize ) {
      $src = fopen( $src, 'r' );
      $dest = fopen( $dst, 'w' );

      // Try first method:
      while ( !feof( $src ) ) {
         if( false === fwrite( $dest, fread( $src, $buffersize ) ) ) {
            $error = true;
         }
      }
      // Try second method if first one failed
      if( isset( $error ) && ($error === true) ) {
         while ( !feof( $src ) ) {
            if( false === stream_copy_to_stream( $src, $dest, 1024 ) ) {
               $this->log( "Can not copy big file; {$src} -> {$dest}" );
               fclose( $src );
               fclose( $dest );
               return false;
            }
         }
      }
      // Close any open handler
      fclose( $src );
      fclose( $dest );
      return true;
   }

   /**
    * Check if file is excluded from copying process
    * 
    * @param string $file filename including ending
    * @return boolean
    */
   private function isFileExcluded( $file ) {
      $excluded = false;
      foreach ( $this->options->excludedFiles as $excludedFile ) {
         if( stripos( strrev( $file ), strrev( $excludedFile ) ) === 0 ) {
            $excluded = true;
            break;
         }
      }

      // Do not copy wp-config.php if the clone gets updated. This is for security purposes, 
      // because if the updating process fails, the staging site is not accessable any longer
      if( isset( $this->options->mainJob ) && $this->options->mainJob == "updating" && stripos( strrev( $file ), strrev( "wp-config.php" ) ) === 0 ) {
         $excluded = true;
      }


      return $excluded;
   }

   /**
    * Replace forward slash with current directory separator
    * Windows Compatibility Fix
    * @param string $path Path
    *
    * @return string
    */
   private function sanitizeDirectorySeparator( $path ) {
      $string = str_replace( "/", "\\", $path );
      return str_replace( '\\\\', '\\', $string );
   }

   /**
    * Check if directory is excluded from copying
    * @param string $directory
    * @return bool
    */
   private function isDirectoryExcluded( $directory ) {
      // Make sure that wp-staging-pro directory / plugin is never excluded
      if( false !== strpos( $directory, 'wp-staging' ) || false !== strpos( $directory, 'wp-staging-pro' ) ) {
         return false;
      }

      $directory = $this->sanitizeDirectorySeparator( $directory );

      foreach ( $this->options->excludedDirectories as $excludedDirectory ) {
         $excludedDirectory = $this->sanitizeDirectorySeparator( $excludedDirectory );
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
   private function isExtraDirectory( $directory ) {
      foreach ( $this->options->extraDirectories as $extraDirectory ) {
         if( strpos( $directory, $extraDirectory ) === 0 ) {
            return true;
         }
      }

      return false;
   }

   /**
    * Replace relative path of file if its located in multisite upload folder wp-content/uploads/sites/x/
    * @return boolean
    */
   private function replaceMultisiteUploadFolder( $file ) {
      $search = 'wp-content' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sites' . DIRECTORY_SEPARATOR . get_current_blog_id();
      $replace = 'wp-content' . DIRECTORY_SEPARATOR . 'uploads';

      return str_replace( $search, $replace, $file );
   }

}
