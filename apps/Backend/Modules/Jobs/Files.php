<?php

namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
use WPStaging\Utils\Logger;

if (!defined("WPINC")) {
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
    //private $maxFilesPerRun = 500;
    private $maxFilesPerRun;
    
    /**
     * File Object
     */
    //private $verifyFiles = array();

    /**
     * @var string
     */
    private $destination;

    /**
     * Initialization
     */
    public function initialize() {
        //$this->getVerifyFiles();
        
        $this->destination = ABSPATH . $this->options->cloneDirectoryName . DIRECTORY_SEPARATOR;

        $filePath = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();

        if (is_file($filePath)) {
            $this->file = new \SplFileObject($filePath, 'r');
        }

        // Informational logs
        if (0 == $this->options->currentStep) {
            $this->log("Copying files...");
        }

        $this->settings->batchSize = $this->settings->batchSize * 1000000;
        $this->maxFilesPerRun = $this->settings->fileLimit;
        //$this->maxFilesPerRun = 1;
        //$this->options->copiedFiles = 0;
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps() {
        $this->options->totalSteps = ceil($this->options->totalFiles / $this->maxFilesPerRun);
        //$this->options->totalSteps = $this->options->totalFiles;
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute() {
        // Finished
        if ($this->isFinished()) {
            $this->log("Copying files finished");
            $this->prepareResponse(true, false);
            return false;
        }

        // Get files and copy'em
        if (!$this->getFilesAndCopy()) {
            $this->prepareResponse(false, false);
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
        if ($this->isOverThreshold()) {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // Go to last copied line and than to next one
        if ($this->options->copiedFiles != 0) {
            $this->file->seek($this->options->copiedFiles);
        }

        $this->file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);

        // End of file
//        if ($this->file->eof())
//        {
//            // Increment copied files when end of file is reached
//            $this->options->copiedFiles++;
//            return;
//        }
        // Loop x files at a time
        for ($i = 0; $i < $this->maxFilesPerRun; $i++) {
            
            
            // Increment copied files
            // Do this anytime to make sure to not stuck in the same step / files
            $this->options->copiedFiles++;

            // End of file
            if ($this->file->eof()) {
                break;
            }
            $file = $this->file->fgets();
            $this->debugLog('copy file ' . $file, Logger::TYPE_DEBUG);
            $this->copyFile($file);
            //$this->verifyFiles[] = $file;

        }

        $totalFiles = $this->maxFilesPerRun + $this->options->copiedFiles;
        //$totalFiles = $this->options->copiedFiles;
        $this->log("Total {$totalFiles} files processed");
        //$this->saveCopiedFiles();

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
    private function copyFile($file) {
        $file = trim($file);

        // Invalid file, skipping it as if succeeded
        if (!is_file($file) || !is_readable($file)) {
            $this->log("Can't read file or file doesn't exist {$file}");
            return true;
        }

        // Failed to get destination
        if (false === ($destination = $this->getDestination($file))) {
            $this->log("Can't get the destination of {$file}");
            return false;
        }

        // Good old PHP
        return $this->copy($file, $destination);
    }

    /**
     * Gets destination file and checks if the directory exists, if it does not attempts to create it.
     * If creating destination directory fails, it returns false, gives destination full path otherwise
     * @param string $file
     * @return bool|string
     */
    private function getDestination($file) {
        $relativePath = str_replace(ABSPATH, null, $file);
        $destinationPath = $this->destination . $relativePath;
        $destinationDirectory = dirname($destinationPath);

        if (!is_dir($destinationDirectory) && !@mkdir($destinationDirectory, 0775, true)) {
            $this->log("Destination directory doesn't exist; {$destinationDirectory}", Logger::TYPE_ERROR);
            return false;
        }

        return $destinationPath;
    }

    /**
     * Copy File using PHP
     * @param string $file
     * @param string $destination
     * @return bool
     */
    private function copy($file, $destination) {
        // Get file size
        $fileSize = filesize($file);

        // File is over batch size
        if ($fileSize >= $this->settings->batchSize) {
            $this->log("Trying to copy big file {$file} -> {$destination}", Logger::TYPE_INFO);
            return $this->copyBig($file, $destination, $this->settings->batchSize);
        }

        // Attempt to copy
        if (!@copy($file, $destination)) {
            $this->log("Failed to copy file to destination: {$file} -> {$destination}", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Copy bigger files than $this->settings->batchSize
     * @param string $file
     * @param string $destination
     * @return bool
     * 
     * @deprecated since version 2.0.0 (Supported only in php 5.5.11 and later)
     */
//    private function copyBig($file, $destination)
//    {
//        $bytes      = 0;
//        $fileInput  = new \SplFileObject($file, "rb");
//        $fileOutput = new \SplFileObject($destination, 'w');
//
//        $this->log("Copying big file; {$file} -> {$destination}");
//
//        while (!$fileInput->eof())
//        {
//            $bytes += $fileOutput->fwrite($fileInput->fread($this->settings->batchSize));
//        }
//
//        $fileInput = null;
//        $fileOutput= null;
//
//        return ($bytes > 0);
//    }

    /**
     * Copy bigger files than $this->settings->batchSize
     * @param string $src
     * @param string $dst
     * @param int $buffersize
     * @return boolean
     */
    private function copyBig($src, $dst, $buffersize) {
        $src = fopen($src, 'r');
        $dest = fopen($dst, 'w');

        // Try first method:
        while (!feof($src)) {
            if (false === fwrite($dest, fread($src, $buffersize))) {
                $error = true;
            }
        }
        // Try second method if first one failed
        if (isset($error) && ($error === true)) {
            while (!feof($src)) {
                if (false === stream_copy_to_stream($src, $dest, 1024)) {
                    $this->log("Can not copy big file; {$src} -> {$dest}");
                    fclose($src);
                    fclose($dest);
                    return false;
                }
            }
        }
        // Close any open handler
        fclose($src);
        fclose($dest);
        return true;
    }
    
    /**
     * Save verified Files
     * @return bool
     */
//    private function saveCopiedFiles(){
//        $fileName = $this->cache->getCacheDir() . "files_to_verify." . $this->cache->getCacheExtension();
//        $files = implode( PHP_EOL, $this->verifyFiles );
//
//        return (false !== @file_put_contents( $fileName, $files ));
//    }
    
    /**
     * Get files
     * @return void
     */
//    protected function getVerifyFiles() {
//        $fileName = $this->cache->getCacheDir() . "files_to_verify." . $this->cache->getCacheExtension();
//
//        if( false === ($this->verifyFiles = @file_get_contents( $fileName )) ) {
//            $this->verifyFiles = array();
//            return;
//        }
//
//        $this->verifyFiles = explode( PHP_EOL, $this->verifyFiles );
//    }

}
