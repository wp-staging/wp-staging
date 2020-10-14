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
class Verify extends JobExecutable {

    /**
     * @var \SplFileObject
     */
    private $files = array();

    /**
     * @var \SplFileObject
     */
    private $verifyFiles = array();

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

        $this->getCopyFiles();
        $this->getVerifyFiles();

        // Informational logs
        if (0 == $this->options->currentStep) {
            $this->log("Verifying files...");
        }

        $this->settings->batchSize = $this->settings->batchSize * 1000000;
        $this->maxFilesPerRun = 1;
        $this->options->verifiedFiles = 0;
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps() {
        $this->options->totalSteps = ceil($this->options->totalFiles / $this->maxFilesPerRun);
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute() {
        // Finished
        if ($this->isFinished()) {
            $this->log("Verifying files finished");
            $this->prepareResponse(true, false);
            return false;
        }

        // Get files and copy'em
        if (!$this->getFilesAndVerify()) {
            $this->prepareResponse(false, false);
            $this->saveOptions();
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
    private function getFilesAndVerify() {
        // Over limits threshold
        if ($this->isOverThreshold()) {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }
        $this->saveVerifyFiles();
        $this->saveOptions();
        //$this->log(json_encode(array_diff($this->files, $this->verifyFiles)));
        //wp_die(print_r(array_diff($this->files, $this->verifyFiles)));
        return true;
    }

    /**
     * Get files
     * @return void
     */
    protected function getVerifyFiles() {
        $file = $this->cache->getCacheDir() . "files_to_verify." . $this->cache->getCacheExtension();

        if (false === ($this->verifyFiles = @file_get_contents($file))) {
            $this->verifyFiles = array();
            return;
        }

        $this->verifyFiles = explode(PHP_EOL, $this->verifyFiles);
    }

    /**
     * Get files
     * @return void
     */
    protected function getCopyFiles() {
        $file = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();

        if (false === ($this->verifyFiles = @file_get_contents($file))) {
            $this->verifyFiles = array();
            return;
        }

        $this->verifyFiles = explode(PHP_EOL, $this->verifyFiles);
    }

    /**
     * Save Result of File Verification
     * @return bool
     */
    protected function saveVerifyFiles() {

        // Get file copy differences
        $filesVerified = array_diff($this->files, $this->verifyFiles);
        
        $fileName = $this->cache->getCacheDir() . "files_verified" . $this->cache->getCacheExtension();
        $files = implode(PHP_EOL, $filesVerified);

        return (false !== @wpstg_put_contents($fileName, $files));
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    private function isFinished() {
        return (
                $this->options->currentStep > $this->options->totalSteps ||
                $this->options->verifiedFiles >= $this->options->totalFiles
                );
    }

}
