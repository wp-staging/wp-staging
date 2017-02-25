<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
use WPStaging\WPStaging;

if (!defined("WPINC"))
{
    die;
}

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Directories
 */
class Directories extends Job
{
    /**
     * @var array
     */
    private $files = array();

    /**
     * @var int
     */
    private $step = 0;

    /**
     * @var int
     */
    private $total = 0;

    /**
     * Initialize
     */
    public function initialize()
    {
        $this->total        = count($this->options->directoriesToCopy);
    }

    /**
     * @param int $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * @param $path
     * @return array|bool
     */
    public function getFilesFromSubDirectories($path)
    {
        if ($this->isOverThreshold())
        {
            $this->saveProgress();

            return false;
        }

        $directories = new \DirectoryIterator($path);

        foreach($directories as $directory)
        {
            // Not a valid directory
            if (false === ($path = $this->getPath($directory)))
            {
                continue;
            }

            // This directory is already scanned
            if (in_array($path, $this->options->scannedDirectories))
            {
                continue;
            }

            // Save all files
            $dir = ABSPATH . $path . DIRECTORY_SEPARATOR;
            $this->getFilesFromDirectory($dir);

            // Add scanned directory listing
            $this->options->scannedDirectories[] = $dir;
        }

        $this->saveOptions();

        return array(
            "status"        => false,
            "percentage"    => round(($this->step / $this->total) * 100),
            "total"         => $this->total,
            "step"          => $this->step
        );
    }

    /**
     * @param string $directory
     */
    public function getFilesFromDirectory($directory)
    {
        // Save all files
        $files = array_diff(scandir($directory), array('.', ".."));

        foreach ($files as $file)
        {
            $fullPath = $directory . $file;

            if (is_dir($fullPath) && !in_array($fullPath, $this->options->directoriesToCopy))
            {
                $this->options->directoriesToCopy[] = $fullPath;
                continue;
            }

            if (!is_file($fullPath) || in_array($fullPath, $this->files))
            {
                continue;
            }

            $this->files[] = $fullPath;
        }
    }

    /**
     * Get Path from $directory
     * @param \SplFileInfo $directory
     * @return string|false
     */
    private function getPath($directory)
    {
        $path = str_replace(ABSPATH, null, $directory->getRealPath());

        // Using strpos() for symbolic links as they could create nasty stuff in nix stuff for directory structures
        if (!$directory->isDir() || strlen($path) < 1 || strpos($directory->getRealPath(), ABSPATH) !== 0)
        {
            return false;
        }

        return $path;
    }

    /**
     * Save files
     * @return bool
     */
    protected function saveProgress()
    {
        $this->saveOptions();

        $ds     = DIRECTORY_SEPARATOR;

        $dir    = WP_PLUGIN_DIR . $ds . WPStaging::SLUG . $ds . "vars" . $ds . "cache" . $ds;

        $files  = implode(PHP_EOL, $this->files);

        if (strlen($files) > 0)
        {
            $files .= PHP_EOL;
        }

        return (false !== @file_put_contents($dir . "files_to_copy.cache", $files, FILE_APPEND));
    }

    /**
     * Start Module
     * @return array
     */
    public function start()
    {
        if (empty($this->options->directoriesToCopy) || !isset($this->options->directoriesToCopy[$this->step]))
        {
            return array(
                "status"        => true,
                "percentage"    => 100,
                "total"         => 0,
                "step"          => $this->step
            );
        }

        $result = array(
            "status"    => false,
            "percentage"=> 0,
            "total"     => 0,
            "step"      => 0
        );


        $total = $this->total - 1;
        for ($i = 0; $i <= $total; $i++)
        {
            $directory = $this->options->directoriesToCopy[$this->step];

            // Get files recursively
            if (false === ($result = $this->getFilesFromSubDirectories($directory)))
            {
                $result = array(
                    "status"        => false,
                    "percentage"    => round(($this->step / $total) * 100),
                    "total"         => $total,
                    "step"          => $this->step
                );

                break;
            }

            $this->options->scannedDirectories[] = $directory;

            $this->step = $i;
        }

        // Completed
        $result["status"]       = true;
        $result["percentage"]   = 100;

        $this->saveProgress();

        return $result;

        // Save last scanned directory
        //$this->options->lastScannedDirectory = array($this->options->directoriesToCopy[$this->step]);

        //        $directory = $this->options->directoriesToCopy[$this->step];
        //
        //        // Get files recursively
        //        $result = $this->getFilesFromSubDirectories($directory);
        //
        //        if (false === $result)
        //        {
        //            $result = array(
        //                "status"        => false,
        //                "percentage"    => round(($this->step / $this->total) * 100),
        //                "total"         => $this->total,
        //                "step"          => $this->step
        //            );
        //        }
        //
        //        $this->options->scannedDirectories[] = $directory;
        //
        //        $this->saveOptions();
        //
        //        return $result;
    }

    /**
     * Next Step of the Job
     * @return void
     */
    public function next()
    {
        // TODO: Implement next() method.
    }
}