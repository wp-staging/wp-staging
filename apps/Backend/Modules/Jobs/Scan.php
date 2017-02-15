<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
use WPStaging\WPStaging;

if (!defined("WPINC"))
{
    die;
}

/**
 * Class Scan
 * @package WPStaging\Backend\Modules\Jobs
 */
class Scan extends JobExec
{

    /**
     * @var object
     */
    private $directoryStructure;

    /**
     * @var array
     */
    private $bigFiles = array();

    private $maxFileSize;

    /**
     * Upon class initialization
     */
    protected function initialize()
    {
        $this->directoryStructure = new \stdClass();

        $settings = get_option("wpstg_settings", array());

        if (isset($settings["wpstg_batch_size"]) && (int) $settings["wpstg_batch_size"] > 0)
        {
            $this->maxFileSize = (int) $settings["wpstg_batch_size"] * 1000000;
        }

        // Scan database
        $this->database();

        // Scan directories
        $this->directories();
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
     * Get table names of the WP database
     */
    private function database()
    {
        $wpDB = WPStaging::getInstance()->get("wpdb");

        if (strlen($wpDB->prefix) > 0)
        {
            $sql = "SHOW TABLE STATUS LIKE '{$wpDB->prefix}%'";
        }
        else
        {
            $sql = "SHOW TABLE STATUS";
        }

        $this->options->tables = $wpDB->get_results($sql);
    }

    /**
     * Get list of main directory sizes
     * @param null|string $path
     */
    private function directories($path = null)
    {
        if (null === $path)
        {
            $path = ABSPATH;
        }

        $dirs = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(ABSPATH, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        $tmpDirs                                    = array();
        $this->directoryStructure->totalUsedSpace   = 0;
        foreach($dirs as $dir)
        {
            if (!$dir->isDir() || in_array($dir->getBasename(), array('.', "..")))
            {
                continue;
            }

            $size                                      = $this->getDirectorySize($dir->getRealPath());
            $this->directoryStructure->totalUsedSpace += $size;

            if ($this->maxFileSize < $size)
            {
                $this->bigFiles = array(
                    "name"      => $dir->getBasename(),
                    "dir"       => $dir->getPath(),
                    "humanSize" => $this->formatSize($size)
                );
            }

            $tmpDirs[] = array(
                "name"      => $dir->getBasename(),
                "dir"       => $dir->getRealPath(),
                "humanSize" => $this->formatSize($size)
            );
        }

        $this->directoryStructure->directories = $tmpDirs;
/*
        $dirs                                   = new \DirectoryIterator($path);
        $this->directoryStructure->directories  = array();

        if (!$dirs)
        {
            return;
        }

        $tmpDirs                                    = array();
        $this->directoryStructure->totalUsedSpace   = 0;

        foreach ($dirs as $dir)
        {
            if (!$dir->isDir() || in_array($dir->getBasename(), array('.', "..")))
            {
                continue;
            }

            $size                                      = $this->getDirectorySize($dir->getRealPath());
            $this->directoryStructure->totalUsedSpace += $size;

            if ($this->maxFileSize < $size)
            {
                $this->bigFiles = array(
                    "name"      => $dir->getBasename(),
                    "dir"       => $dir->getPath(),
                    "humanSize" => $this->formatSize($size)
                );
            }

            $tmpDirs[] = array(
                "name"      => $dir->getBasename(),
                "dir"       => $dir->getRealPath(),
                "humanSize" => $this->formatSize($size)
            );
        }

        $this->directoryStructure->directories = $tmpDirs;
        */

        $this->hasFreeDiskSpace();
    }

    /**
     * Gets size of given directory
     * @param string $path
     * @return int
     */
    private function getDirectorySize($path)
    {
        // Basics
        $totalBytes = 0;
        $path       = realpath($path);

        // Invalid path
        if (false === $path)
        {
            return $totalBytes;
        }

        // We can use exec(), you go dude!
        if ($this->canUseExec())
        {
            return $this->getDirectorySizeWithExec($path);
        }

        // Iterator
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        // Loop & add file size
        foreach ($iterator as $file)
        {
            try {
                $totalBytes += $file->getSize();
            }
            // Some invalid symbolik links can cause issues in *nix systems
            catch(\Exception $e) {
                // TODO log the issue???
            }
        }

        return $totalBytes;
    }

    /**
     * @param string $path
     * @return int
     */
    private function getDirectorySizeWithExec($path)
    {
        $os = $this->checkOS();

        // OS is not supported
        if (!in_array($os, array("WIN", "LIN"), true))
        {
            return 0;
        }


        // WIN OS
        if ("WIN" === $os)
        {
            return $this->getDirectorySizeForWin($path);
        }

        // *Nix OS
        return $this->getDirectorySizeForNix($path);
    }

    /**
     * @param string $path
     * @return int
     */
    private function getDirectorySizeForNix($path)
    {
        exec("du -s {$path}", $output, $return);

        $size = explode("\t", $output[0]);

        if (0 == $return && count($size) == 2)
        {
            return (int) $size[0];
        }

        return 0;
    }

    /**
     * @param string $path
     * @return int
     */
    private function getDirectorySizeForWin($path)
    {
        exec("diruse {$path}", $output, $return);

        $size = explode("\t", $output[0]);

        if (0 == $return && count($size) >= 4)
        {
            return (int) $size[0];
        }

        return 0;
    }

    /**
     * Format bytes into human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    public function formatSize($bytes, $precision = 2)
    {
        $units  = array('B', "KB", "MB", "GB", "TB");

        $base   = log($bytes) / log(1000); // 1024 would be for MiB KiB etc
        $pow    = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $precision) . ' ' . $units[(int) floor($base)];
    }

    /**
     * Checks if there is enough free disk space to create staging site
     */
    private function hasFreeDiskSpace()
    {
        if (!function_exists("disk_free_space"))
        {
            return;
        }

        $freeSpace = @disk_free_space(ABSPATH);

        if (false === $freeSpace)
        {
            return;
        }

        $this->options->hasEnoughDiskSpace = ($freeSpace >= $this->directoryStructure->totalUsedSpace);
    }

    /**
     * @param null|string $directories
     * @return string
     */
    public function directoryListing($directories = null)
    {
        $this->directories($directories);

        if (count($this->directoryStructure->directories) < 1)
        {
            return '';
        }

        $output = (null !== $directories) ? "<div class=\"wpstg-dir wpstg-subdir\">" : '';

        foreach ($this->directoryStructure->directories as $directory)
        {
            $isChecked = !in_array($directory["dir"], $this->options->excludedDirectories);

            $output .= "<div class='wpstg-dir'>";
            $output .= "<input type='checkbox' class='wpstg-check-dir'";
                if ($isChecked) $output .= " checked";
            $output .= " name='selectedDirectories[]' value='{$directory["dir"]}'>";

            $output .= "<a href='#' class='wpstg-expand-dirs";
                if ($isChecked) $output .= " disabled";
                $output .= "'>{$directory["name"]}";
            $output .= "</a>";

            $output .= "<span class='wpstg-size-info'>{$directory["humanSize"]}</span>";

            $output .= $this->directoryListing($directory["dir"]);

            $output .= "</div>";
        }

        if (null !== $directories)
        {
            $output .= "</div>";
        }

        return $output;
    }
}