<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class Scan
 * @package WPStaging\Backend\Modules\Jobs
 */
class Scan extends Job
{

    /**
     * @var Files
     */
    private $files;

    /**
     * Upon class initialization
     */
    protected function initialize()
    {
        // Database
        $database               = new Database();
        $database->getStatus();
        $this->options->tables  = $database->getTables();

        // Files
        $this->files            = new Files();
    }

    /**
     * Start Module
     * @return $this
     */
    public function start()
    {
        // Basic Options
        $this->options->root                    = str_replace(array("\\", '/'), DIRECTORY_SEPARATOR, ABSPATH);
        $this->options->existingClones          = get_option("wpstg_existing_clones", array());

        $this->options->excludedTables          = array();
        $this->options->clonedTables            = array();

        $this->options->includedDirectories     = array();
        $this->options->extraDirectories        = array();
        $this->options->directoriesToCopy       = array();
        $this->options->scannedDirectories      = array();
        $this->options->lastScannedDirectory    = array();

        $this->options->currentJob              = "database";
        $this->options->currentStep             = 0;
        $this->options->totalSteps              = 0;

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
     * @param null|string $directories
     * @return string
     */
    public function directoryListing($directories = null)
    {
        if (null == $directories)
        {
            $directories = $this->files->getDirectories();
        }

        $output = '';
        foreach ($directories as $name => $directory)
        {
            // Need to preserve keys so no array_shift()
            $data = reset($directory);
            unset($directory[key($directory)]);

            $isChecked = (
                empty($this->options->includedDirectories) ||
                in_array($data["path"], $this->options->includedDirectories)
            );

            $output .= "<div class='wpstg-dir'>";
            $output .= "<input type='checkbox' class='wpstg-check-dir'";
                if ($isChecked) $output .= " checked";
            $output .= " name='selectedDirectories[]' value='{$data["path"]}'>";

            $output .= "<a href='#' class='wpstg-expand-dirs";
                if (false === $isChecked) $output .= " disabled";
                $output .= "'>{$name}";
            $output .= "</a>";

            $output .= "<span class='wpstg-size-info'>{$this->formatSize($data["size"])}</span>";

            if (!empty($directory))
            {
                $output .= "<div class='wpstg-dir wpstg-subdir'>";
                    $output .= $this->directoryListing($directory);
                $output .= "</div>";
            }

            $output .= "</div>";
        }

        return $output;
    }
}