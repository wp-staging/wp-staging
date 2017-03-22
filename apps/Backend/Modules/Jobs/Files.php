<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
use WPStaging\Utils\Logger;

if (!defined("WPINC"))
{
    die;
}

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Jobs
 */
class Files extends JobExecutableWithCommandLine
{

    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var int
     */
    private $maxFilesPerRun = 500;

    /**
     * @var string
     */
    private $destination;

    /**
     * Initialization
     */
    public function initialize()
    {
        $this->destination = ABSPATH . $this->options->cloneDirectoryName . DIRECTORY_SEPARATOR;

        $filePath = $this->cache->getCacheDir() . "files_to_copy." . $this->cache->getCacheExtension();

        if (is_file($filePath))
        {
            $this->file = new \SplFileObject($filePath, 'r');
        }

        // Informational logs
        if (0 == $this->options->currentStep)
        {
            $this->log("Copying files...");

            // We can use exec
            if (true === $this->canUseExec)
            {
                $this->log("Files will be copied using EXEC, platform : {$this->OS}");
            }
            // We'll use popen
            elseif (true === $this->canUsePopen)
            {
                $this->log("Files will be copied using POPEN, platform : {$this->OS}");
            }
            // PHP
            else
            {
                $this->log("Files will be copied using PHP, platform : {$this->OS}");
            }
        }
    }

    /**
     * Calculate Total Steps in This Job and Assign It to $this->options->totalSteps
     * @return void
     */
    protected function calculateTotalSteps()
    {
        $this->options->totalSteps = ceil($this->options->totalFiles / $this->maxFilesPerRun);
    }

    /**
     * Execute the Current Step
     * Returns false when over threshold limits are hit or when the job is done, true otherwise
     * @return bool
     */
    protected function execute()
    {
        // Finished
        if ($this->isFinished())
        {
            $this->log("Copying files finished");
            $this->prepareResponse(true, false);
            return false;
        }

        // Get files and copy'em
        if (!$this->getFilesAndCopy())
        {
            $this->prepareResponse(false, false);
            return false;
        }

        // Prepare response
        $this->prepareResponse();

        // Not finished
        return true;
    }

    /**
     * Get files and copy
     * @return bool
     */
    private function getFilesAndCopy()
    {
        // Over limits threshold
        if ($this->isOverThreshold())
        {
            // Prepare response and save current progress
            $this->prepareResponse(false, false);
            $this->saveOptions();
            return false;
        }

        // Skip processed ones
        if ($this->options->copiedFiles != 0)
        {
            $this->file->seek($this->options->copiedFiles);
        }

        $this->file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);

        // One thousand files at a time
        for ($i = 0; $i <= $this->maxFilesPerRun; $i++)
        {
            // End of file
            if ($this->file->eof())
            {
                break;
            }

            $this->copyFile($this->file->fgets());
        }

        $totalFiles = $this->maxFilesPerRun + $this->options->copiedFiles;
        $this->log("Total {$totalFiles} files processed");

        return true;
    }

    /**
     * Checks Whether There is Any Job to Execute or Not
     * @return bool
     */
    private function isFinished()
    {
        return (
            $this->options->currentStep > $this->options->totalSteps ||
            $this->options->copiedFiles >= $this->options->totalFiles
        );
    }

    /**
     * @param string $file
     * @return bool
     */
    private function copyFile($file)
    {
        $file = trim($file);

        // Increment copied files whatever the result is
        // This way we don't get stuck in the same step / files
        $this->options->copiedFiles++;

        // Invalid file, skipping it as if succeeded
        if (!is_file($file) || !is_readable($file))
        {
            return true;
        }

        // Failed to get destination
        if (false === ($destination = $this->getDestination($file)))
        {
            return false;
        }


        // We can use exec
        if (true === $this->canUseExec)
        {
            return $this->copyFileWithExec($file, $destination);
        }

        // We can use popen
        if (true === $this->canUsePopen)
        {
            return $this->copyFileWithPopen($file, $destination);
        }

        // Good old PHP
        return $this->copyFileWithPHP($file, $destination);
    }

    /**
     * Gets destination file and checks if the directory exists, if it does not attempts to create it.
     * If creating destination directory fails, it returns false, gives destination full path otherwise
     * @param string $file
     * @return bool|string
     */
    private function getDestination($file)
    {
        $relativePath           = str_replace(ABSPATH, null, $file);
        $destinationPath        = $this->destination . $relativePath;
        $destinationDirectory   = dirname($destinationPath);

        if (!is_dir($destinationDirectory) && !@mkdir($destinationDirectory, 0775, true))
        {
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
    private function copyFileWithPHP($file, $destination)
    {
        // Get file size
        $fileSize = filesize($file);

        // File is over batch size
        if ($fileSize >= $this->settings->batchSize)
        {
            return $this->copyBigFileWithPHP($file, $destination);
        }

        // Attempt to copy
        if (!@copy($file, $destination))
        {
            $this->log("Failed to copy file to destination; {$file} -> {$destination}", Logger::TYPE_ERROR);
            return false;
        }

        return true;
    }

    /**
     * Copy bigger files than $this->settings->batchSize
     * @param string $file
     * @param string $destination
     * @return bool
     */
    private function copyBigFileWithPHP($file, $destination)
    {
        $bytes      = 0;
        $fileInput  = new \SplFileObject($file, "rb");
        $fileOutput = new \SplFileObject($destination, 'w');

        $this->log("Copying big file; {$file} -> {$destination}");

        while (!$fileInput->eof())
        {
            $bytes += $fileOutput->fwrite($fileInput->fread($this->settings->batchSize));
        }

        $fileInput = null;
        $fileOutput= null;

        return ($bytes > 0);
    }

    /**
     * @param string $file
     * @param string $destination
     * @return bool
     */
    private function copyFileWithExec($file, $destination)
    {
        // OS is not supported
        if (!in_array($this->OS, array("WIN", "LIN"), true))
        {
            return false;
        }

        // WIN OS
        if ("WIN" === $this->OS)
        {
            return $this->copyFileWithExecForWin($file, $destination);
        }

        // *Nix OS
        return $this->copyFileWithExecForNix($file, $destination);
    }

    /**
     * @param string $file
     * @param string $destination
     * @return bool
     */
    private function copyFileWithExecForWin($file, $destination)
    {
        @exec("copy {$file} {$destination}");
        return true;
    }

    /**
     * @param string $file
     * @param string $destination
     * @return bool
     */
    private function copyFileWithExecForNix($file, $destination)
    {
        @exec("cp {$file} {$destination} > /dev/null &");
        return true;
    }

    /**
     * @param string $file
     * @param string $destination
     * @return int
     */
    private function copyFileWithPopen($file, $destination)
    {
        // OS is not supported
        if (!in_array($this->OS, array("WIN", "LIN"), true))
        {
            return 0;
        }

        // WIN OS
        if ("WIN" === $this->OS)
        {
            return $this->copyFileWithPopenForWin($file, $destination);
        }

        // *Nix OS
        return $this->copyFileWithPopenForNix($file, $destination);
    }

    /**
     * @param string $file
     * @param string $destination
     * @return bool
     */
    private function copyFileWithPopenForWin($file, $destination)
    {
        $handle = @popen("/usr/bin/copy {$file} {$destination}", "w+");
        pclose($handle);
        return true;
    }

    /**
     * @param string $file
     * @param string $destination
     * @return bool
     */
    private function copyFileWithPopenForNix($file, $destination)
    {
        $handle = @popen("/usr/bin/cp {$file} {$destination} > /dev/null &", "w+");
        pclose($handle);
        return true;
    }
}