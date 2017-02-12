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
class Scan extends Job
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
        $excludedFolders    = array();

        // Clone posted
        if (isset($_POST["clone"]))
        {
            $this->options->current = $_POST["clone"];
        }

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

    private function directories()
    {
        $dirs = new \DirectoryIterator(ABSPATH);

        if (!$dirs)
        {
            return;
        }

        $tmpDirs = array();
        foreach ($dirs as $dir)
        {
            if ($dir->isDir() && !in_array($dir->getBasename(), array('.', "..")))
            {
                $tmpDirs[] = array(
                    "name"  => $dir->getBasename(),
                    "size"  => $this->getDirectorySize($dir->getRealPath()),
                    "huma"  => $this->formatSize($this->getDirectorySize($dir->getRealPath()))
                );
            }
        }

        $this->options->directories = $tmpDirs;
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
     * Format bytes into human readable form
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatSize($bytes, $precision = 2)
    {
        $units  = array('B', "KB", "MB", "GB", "TB");

        $base   = log($bytes) / log(1000); // 1024 would be for MiB KiB etc
        $pow    = pow(1000, $base - floor($base)); // Same rule for 1000

        return round($pow, $precision) . ' ' . $units[(int) floor($base)];
    }
}