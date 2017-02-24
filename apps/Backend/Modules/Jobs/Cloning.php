<?php
namespace WPStaging\Backend\Modules\Jobs;

/**
 * Class Cloning
 * @package WPStaging\Backend\Modules\Jobs
 */
class Cloning extends Job
{

    /**
     * @return bool
     */
    public function save()
    {
        if (!isset($_POST) || !isset($_POST["cloneID"]))
        {
            return false;
        }

        // Generate Options
        $this->options->clone = $_POST["cloneID"];

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

        return $this->saveOptions();
    }

    /**
     * Start the cloning job
     */
    public function start()
    {
        if (!$this->options->currentJob)
        {
            throw new \Exception("Job is not set");
        }

        $methodName = "job" . ucwords($this->options->currentJob);

        if (!method_exists($this, $methodName))
        {
            throw new \Exception("Job method doesn't exist : " . $this->options->currentJob);
        }

        // Call the job
        return $this->{$methodName}();
    }

    /**
     * @return array
     */
    public function jobDatabase()
    {
        $this->options->currentJob              = "directories";
        $this->options->currentStep             = 0;
        $this->options->totalSteps              = 0;

        $this->saveOptions();

        return array(
            "status"        => true,
            "percentage"    => 100,
            "total"         => 10,
            "step"          => 10
        );

        $database = new Database(count($this->options->existingClones));

        $database->setTables($this->options->tables);
        $database->setStep($this->options->currentStep);

        $response = $database->start();

        // Job is done
        if (true === $response["status"])
        {
            $this->options->currentJob              = "directories";
            $this->options->currentStep             = 0;
            $this->options->totalSteps              = 0;
        }
        // Job is not done
        else
        {
            if (isset($this->options->tables[$this->options->currentStep]))
            {
                $this->options->clonedTables[] = $this->options->tables[$this->options->currentStep];
            }

            $this->options->currentStep             = $response["step"];
            $this->options->totalSteps              = $response["total"];
        }

        // Save options
        $this->saveOptions();

        return $response;
    }

    /**
     * @return array
     */
    public function jobDirectories()
    {
        $directories = new Directories();

        $directories->setStep($this->options->currentStep);

        $response = $directories->start();

        // Refresh options
        $this->options  = $directories->getOptions();

        // Job is done
        if (true === $response["status"])
        {
            $this->options->currentJob              = "files";
            $this->options->currentStep             = 0;
            $this->options->totalSteps              = 0;
        }
        // Job is not done
        else
        {
            $this->options->currentStep             = $response["step"];
            $this->options->totalSteps              = $response["total"];
        }

        // Save options
        $this->saveOptions();

        return $response;
    }
}