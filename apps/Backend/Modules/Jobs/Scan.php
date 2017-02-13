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
     * Upon class initialization
     */
    protected function initialize()
    {
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
        $uncheckedTables    = array();
        $excludedDirectories= array();

        // Clone posted
        if (isset($_POST["clone"]))
        {
            $this->options->current = $_POST["clone"];
        }

        // TODO; finish it up
        $this->options->uncheckedTables     = $uncheckedTables;
        $this->options->clonedTables        = array();
        $this->options->excludedDirectories = $excludedDirectories;

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
            $sql = "SHOW TABLES LIKE '{$wpDB->prefix}%'";
        }
        else
        {
            $sql = "SHOW TABLES";
        }

        $this->options->tables = $wpDB->get_col($sql);
    }

    /**
     * Get list of main directory sizes
     */
    private function directories()
    {
        $dirs = new \DirectoryIterator(ABSPATH);

        if (!$dirs)
        {
            return;
        }

        $tmpDirs                        = array();
        $this->options->totalUsedSpace  = 0;

        foreach ($dirs as $dir)
        {
            if (!$dir->isDir() || in_array($dir->getBasename(), array('.', "..")))
            {
                continue;
            }

            $size                           = $this->getDirectorySize($dir->getRealPath());
            $this->options->totalUsedSpace += $size;

            $tmpDirs[] = array(
                "name"      => $dir->getBasename(),
                "size"      => $size,
                "humanSize" => $this->formatSize($size)
            );
        }

        $this->options->directories = $tmpDirs;

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

        $size = explode("\t", $output);

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

        $size = explode("\t", $output);

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

        $this->options->hasEnoughDiskSpace = ($this->options->totalUsedSpace > $freeSpace);
    }
}