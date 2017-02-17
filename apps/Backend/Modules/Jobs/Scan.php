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
        $uncheckedTables                        = array();
        $excludedDirectories                    = array();


        $this->options->disableInputStageName   = false;
        $this->options->isInProgress            = false;
        $this->options->root                    = str_replace(array("\\", '/'), DIRECTORY_SEPARATOR, ABSPATH);
        $this->options->existingClones          = get_option("wpstg_existing_clones", array());

        // Clone posted
        if (isset($_POST["clone"]))
        {
            $this->options->current                 = $_POST["clone"];
            $this->options->disableInputStageName   = true;
        }

        // TODO; finish it up
        $this->options->uncheckedTables         = $uncheckedTables;
        $this->options->clonedTables            = array();
        $this->options->excludedDirectories     = $excludedDirectories;

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

            $isChecked = !in_array($data["path"], $this->options->excludedDirectories);

            $output .= "<div class='wpstg-dir'>";
            $output .= "<input type='checkbox' class='wpstg-check-dir'";
                if ($isChecked) $output .= " checked";
            $output .= " name='selectedDirectories[]' value='{$data["path"]}'>";

            $output .= "<a href='#' class='wpstg-expand-dirs";
                if ($isChecked) $output .= " disabled";
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