<?php
namespace WPStaging\Backend\Modules\Jobs;

use WPStaging\Backend\Modules\Jobs\Exceptions\JobNotFoundException;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cloning extends Job
{

    /**
     * Save Chosen Cloning Settings
     * @return bool
     */
    public function save()
    {
        if (!isset($_POST) || !isset($_POST["cloneID"]))
        {
            return false;
        }

        // Generate Options

        // Clone
        $this->options->clone               = $_POST["cloneID"];
        $this->options->cloneDirectoryName  = preg_replace("#\W+#", '-', strtolower($this->options->clone));
        $this->options->cloneNumber         = 1;

        if (isset($this->options->existingClones[$this->options->clone]))
        {
            $this->options->cloneNumber = $this->options->existingClones[$this->options->clone]->number;
        }

        // Excluded Tables
        if (isset($_POST["excludedTables"]) && is_array($_POST["excludedTables"]))
        {
            $this->options->excludedTables = $_POST["excludedTables"];
        }

        // Included Directories
        if (isset($_POST["includedDirectories"]) && is_array($_POST["includedDirectories"]))
        {
            $this->options->includedDirectories = $_POST["includedDirectories"];
        }

        // Extra Directories
        if (isset($_POST["extraDirectories"]) && strlen($_POST["extraDirectories"]) > 0)
        {
            $this->options->extraDirectories = $_POST["extraDirectories"];
        }

        // Directories to Copy
        $this->options->directoriesToCopy = array_merge(
            $this->options->includedDirectories,
            $this->options->extraDirectories
        );

        array_unshift($this->options->directoriesToCopy, ABSPATH);

        // Delete files to copy listing
        $this->cache->delete("files_to_copy");

        return $this->saveOptions();
    }

    /**
     * Start the cloning job
     */
    public function start()
    {
        if (null === $this->options->currentJob)
        {
            $this->log("Cloning job for {$this->options->clone} finished");
            return true;
        }

        $methodName = "job" . ucwords($this->options->currentJob);

        if (!method_exists($this, $methodName))
        {
            $this->log("Can't execute job; Job's method {$methodName} is not found");
            throw new JobNotFoundException($methodName);
        }

        // Call the job
        return $this->{$methodName}();
    }

    /**
     * @param object $response
     * @param string $nextJob
     * @return object
     */
    private function handleJobResponse($response, $nextJob)
    {
        // Job is not done
        if (true !== $response->status)
        {
            return $response;
        }

        $this->options->currentJob              = $nextJob;
        $this->options->currentStep             = 0;
        $this->options->totalSteps              = 0;

        // Save options
        $this->saveOptions();

        return $response;
    }

    /**
     * Clone Database
     * @return object
     */
    public function jobDatabase()
    {
        $database = new Database();
        return $this->handleJobResponse($database->start(), "directories");
    }

    /**
     * Get All Files From Selected Directories Recursively Into a File
     * @return object
     */
    public function jobDirectories()
    {
        $directories = new Directories();
        return $this->handleJobResponse($directories->start(), "files");
    }

    /**
     * Copy Files
     * @return object
     */
    public function jobFiles()
    {
        $files = new Files();
        return $this->handleJobResponse($files->start(), "data");
    }

    /**
     * Replace Data
     * @return object
     */
    public function jobData()
    {
        $data = new Data();
        return $this->handleJobResponse($data->start(), "finish");
    }

    /**
     * Save Clone Data
     * @return bool
     */
    public function jobFinish()
    {
        $this->log("Deleting clone job's cache files...");

        // Clean cache files
        $this->cache->delete("clone_options");
        $this->cache->delete("files_to_copy");

        $this->log("Clone job's cache files have been deleted!");

        // Check if clones still exist
        $this->log("Verifying existing clones...");
        foreach ($this->options->existingClones as $name => $clone)
        {
            if (!is_dir($clone["path"]))
            {
                unset($this->options->existingClones[$name]);
            }
        }
        $this->log("Existing clones verified!");

        // Clone data already exists
        if (isset($this->options->existingClones[$this->options->clone]))
        {
            $this->log("Clone data already exists, no need to update, the job finished");
            return true;
        }

        // Save new clone data
        $this->log("{$this->options->clone}'s clone job's data is not in database, generating data");
        $this->options->existingClones[$this->options->clone] = array(
            "directoryName"     => $this->options->cloneDirectoryName,
            "path"              => ABSPATH . $this->options->cloneDirectoryName,
            "url"               => get_site_url() . '/' . $this->options->cloneDirectoryName,
            "number"            => $this->options->cloneNumber
        );

        if (false === ($response = update_option("wpstg_existing_clones", $this->options->existingClones)))
        {
            $this->log("Failed to save {$this->options->clone}'s clone job data to database'");
        }

        // Save scanned directories for delete job
        $this->cache->save("delete_directories_" . $this->options->clone, $this->options->scannedDirectories);

        $this->log("Successfully saved {$this->options->clone}'s clone job data to database'");
        $this->log("Cloning job has finished!");

        return $response;
    }
}