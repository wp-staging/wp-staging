<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
use WPStaging\Utils\Logger;
use splitbrain\PHPArchive\Tar;
use splitbrain\PHPArchive\Archive;

if( !defined( "WPINC" ) ) {
   die;
}

/**
 * Class FileExport
 * @package WPStaging\Backend\Modules\Jobs
 */
class FileArchive extends JobExecutable {

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
    * The archive
    * @var object
    */
   private $archive;
   public $archiveFilesize = 0;

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
         $this->log( "Archiving files..." );
      }

      $this->settings->batchSize = $this->settings->batchSize * 1000000;
      //$this->maxFilesPerRun = $this->settings->fileLimit;
      $this->maxFilesPerRun = 1;

      if( $this->options->currentStep === 0 ) {
         $this->createArchive();
      }
   }

   /**
    * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
    * @return void
    */
   protected function calculateTotalSteps() {
      $this->options->totalSteps = ceil( $this->options->totalFiles / $this->maxFilesPerRun );
   }

   /**
    * Execute the Current Job
    * Returns false when over threshold limits are hit or when the job is done, true otherwise
    * @return bool
    */
   protected function execute() {

      // Finished
      if( $this->isFinished() ) {
         $this->archive->close();
         $this->log( "Archiving files finished" );
         $this->prepareResponse( true, false );
         return false;
      }

      // Get files and archive'em
      if( !$this->getFilesAndArchive() ) {
         $this->prepareResponse( false, false );
         return false;
      }

      // Prepare and return response
      $this->prepareResponse();

      // Not finished
      return true;
   }

   private function createArchive() {
      $path = \WPStaging\WPStaging::getContentDir() . 'backups' . DIRECTORY_SEPARATOR;
      $this->archive = new Tar();
      $this->archive->create( $path . 'wpstaging' . $this->options->currentStep . '.tar' );
      $this->archive->setCompression( 9, Archive::COMPRESS_GZIP );
   }

   /**
    * Get files and archive
    * @return bool
    */
   private function getFilesAndArchive() {
      // Over limits threshold
      if( $this->isOverThreshold() ) {
         // Prepare response and save current progress
         $this->prepareResponse( false, false );
         $this->saveOptions();
         return false;
      }

      // Go to last copied line and than to next one
      if( isset( $this->options->copiedFiles ) && $this->options->copiedFiles != 0 ) {
         $this->file->seek( $this->options->copiedFiles - 1 );
      }

      $this->file->setFlags( \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD );


      // Loop x files at a time
      for ( $i = 0; $i < $this->maxFilesPerRun; $i++ ) {

         // Increment files
         // Do this anytime to make sure to not stuck in the same step / files
         $this->options->copiedFiles++;

         // End of file
         if( $this->file->eof() ) {
            break;
         }


         $file = $this->file->fgets();
         $this->processFile( $file );

         // Over limits threshold
//         if( $this->isOverThreshold() ) {
//            $this->log( "Over threshold. Trying to restart.... Step: " . $i );
//            // Prepare response and save current progress
//            $this->prepareResponse( false, false );
//            $this->saveOptions();
//            break;
//            return false;
//         }
      }

      $totalFiles = $this->options->copiedFiles;
      // Log this only every 50 entries to keep the log small and to not block the rendering browser
      if( $this->options->copiedFiles % 50 == 0 ) {
         $this->log( "Total {$totalFiles} files processed" );
      }


      return true;
   }

//   protected function isOverThreshold() {
//      parent::isOverThreshold();
//      
//      if ($this->archiveFilesize > 10000000){
//         return true;
//      }
//      
//      return false;
//   }

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
   private function processFile( $file ) {
      $file = trim( ABSPATH . $file );

      $directory = dirname( $file );

      // Get file size
      $fileSize = filesize( $file );
      $this->archiveFilesize = $this->archiveFilesize + $filesize;

      // Directory is excluded
      if( $this->isDirectoryExcluded( $directory ) ) {
         $this->debugLog( "Skipping directory by rule: {$file}", Logger::TYPE_INFO );
         return false;
      }

//      // File is excluded
      if( $this->isFileExcluded( $file ) ) {
         $this->debugLog( "Skipping file by rule: {$file}", Logger::TYPE_INFO );
         return false;
      }

      // File has more than certain size (8MB)
      if( $fileSize >= $this->settings->maxFileSize * 1000000 ) {
         $this->log( "Skipping large file: {$file}", Logger::TYPE_INFO );
         return false;
      }

      // Invalid file, skipping it as if succeeded
//      if( !is_file( $file ) ) {
//         $this->debugLog( "Not a file {$file}" );
//         return true;
//      }
//      // Invalid file, skipping it as if succeeded
//      if( !is_readable( $file ) ) {
//         $this->log( "Can't read file {$file}" );
//         return true;
//      }
      // Attempt to archive
      try {
         $this->archive->addFile( $file );
      } catch ( exception $e ) {
         $this->log( "Can't add file {$file} to archive. Error {$e} ", Logger::TYPE_ERROR );
      }

      return true;
   }

   /**
    * Check if file is excluded from copying process
    * 
    * @param string $file filename including ending
    * @return boolean
    */
   private function isFileExcluded( $file ) {

      // If file name exists
      if( in_array( basename( $file ), $this->options->excludedFiles ) ) {
         return true;
      }
      // If path exists
      foreach ( $this->options->excludedFiles as $excludedFile ) {
         if( false !== strpos( $file, $excludedFile ) ) {
            return true;
         }
      }

      // Do not copy if file is located in wp-staging/backups folder 
      if( strpos( $file, 'wp-staging' . DIRECTORY_SEPARATOR . 'backups' ) !== false ) {
         return true;
      }


      return false;
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
   private function isExtraDirectory( $directory ) {
      foreach ( $this->options->extraDirectories as $extraDirectory ) {
         if( strpos( $directory, $extraDirectory ) === 0 ) {
            return true;
         }
      }

      return false;
   }

   private function test2() {
      //\clearstatcache();
      $cache = new \WPStaging\Utils\Cache();
      $filePath = ABSPATH . "files_to_copy." . $cache->getCacheExtension();
      $files = new \SplFileObject( $filePath, 'r' );
      $files->setFlags( \SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD );

      $path = ABSPATH;

      $zip = new \ZipArchive();
      $zip->open( ABSPATH . 'wp-staging.zip', \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
      while ( !$files->eof() ) {
         $file = trim( ABSPATH . $files->fgets() );

         if( is_file( $file ) ) {
            $zip->addFile( $file );
         } elseif( is_dir( $file ) ) {
            $zip->addEmptyDir( $file );
         }
         echo $file . '<br/>';
      }
      $zip->close();
   }

}
