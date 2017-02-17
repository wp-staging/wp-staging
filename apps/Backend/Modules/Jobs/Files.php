<?php
namespace WPStaging\Backend\Modules\Jobs;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

/**
 * Class Files
 * @package WPStaging\Backend\Modules\Jobs
 */
class Files extends Job
{

    /**
     * Directories
     * @var array
     */
    private $directories = array();

    /**
     * @var array
     */
    private $bigFiles = array();

    /**
     * @var bool
     */
    private $canUseExec;

    /**
     * @var bool
     */
    private $canUsePopen;

    /**
     * @var bool
     */
    private $isEnoughDiskSpace = true;

    /**
     * Operating System
     * @var string
     */
    private $OS;

    /**
     * Initialization
     */
    public function initialize()
    {
        $this->directories();
        $this->hasFreeDiskSpace();
        $this->OS           = $this->getOS();
        $this->canUseExec   = $this->canUse("exec");
        $this->canUsePopen  = $this->canUsePopen();
    }

    /**
     * Start Module
     * @return mixed
     */
    public function start()
    {
        // TODO: Implement start() method.

        // TODO: check if we can use EXEC or not
        // TODO: if we can use exec; WIN: exec("copy {$sourceFile} {$targetFile}"), LIN: exec("cp {$sourceFile} {$targetFile}")
    }

    /**
     * Get OS
     * @return string
     */
    protected function getOS()
    {
        return strtoupper(substr(PHP_OS, 0, 3)); // WIN, LIN..
    }

    /**
     * Checks whether we can use given function or not
     * @param string $functionName
     * @return bool
     */
    protected function canUse($functionName)
    {
        // Exec doesn't exist
        if (!function_exists($functionName))
        {
            return false;
        }

        // Check if it is disabled from INI
        $disabledFunctions = explode(',', ini_get("disable_functions"));

        return (!in_array($functionName, $disabledFunctions));
    }

    /**
     * Checks whether we can use popen() / \COM class (for WIN) or not
     * @return bool
     */
    protected function canUsePopen()
    {
        // Windows
        if ("WIN" === $this->OS)
        {
            return class_exists("\\COM");
        }

        // This should cover rest OS for servers
        return $this->canUse("popen");
    }

    /**
     * Get directories and main meta data about'em recursively
     */
    public function directories()
    {
        $directories = new \DirectoryIterator(ABSPATH);

        foreach($directories as $directory)
        {
            // Not a valid directory
            if (false === ($path = $this->getPath($directory)))
            {
                continue;
            }

            $this->handleDirectory($path);

            // Get Sub-directories
            $this->getSubDirectories($directory->getRealPath());
        }

        // Gather Plugins
        $this->getSubDirectories(WP_PLUGIN_DIR);

        // Gather Themes
        $this->getSubDirectories(WP_CONTENT_DIR  . DIRECTORY_SEPARATOR . "themes");
    }

    /**
     * @param string $path
     */
    public function getSubDirectories($path)
    {
        $directories = new \DirectoryIterator($path);

        foreach($directories as $directory)
        {
            // Not a valid directory
            if (false === ($path = $this->getPath($directory)))
            {
                continue;
            }

            $this->handleDirectory($path);
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
     * Organizes $this->directories
     * @param string $path
     */
    private function handleDirectory($path)
    {
        $directoryArray = explode(DIRECTORY_SEPARATOR, $path);
        $total          = count($directoryArray);

        if (count($total) < 1)
        {
            return;
        }

        $total          = $total - 1;
        $currentArray   = &$this->directories;

        for ($i = 0; $i <= $total; $i++)
        {
            if (!isset($currentArray[$directoryArray[$i]]))
            {
                $currentArray[$directoryArray[$i]] = array();
            }

            $currentArray = &$currentArray[$directoryArray[$i]];

            // Attach meta data to the end
            if ($i < $total)
            {
                continue;
            }

            $fullPath   = ABSPATH . $path;
            $size       = $this->getDirectorySize($fullPath);

            $currentArray["metaData"] = array(
                "size"      => $size,
                "path"      => ABSPATH . $path,
            );
        }
    }

    /**
     * Checks and organizes big files
     * @param \SplFileInfo $file
     */
    private function handleFile($file)
    {
        if (
            !isset($this->options->maxFileBatch) ||
            (int) $this->options->maxFileBatch < 1 ||
            $this->options->maxFileBatch > $file->getSize()
        )
        {
            return;
        }

        $this->bigFiles[] = array(
            "name"  => $file->getBasename(),
            "size"  => $file->getSize(),
            "path"  => $file->getRealPath()
        );
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

        $this->isEnoughDiskSpace = ($freeSpace >= $this->getDirectorySize(ABSPATH));
    }

    /**
     * Gets size of given directory
     * @param string $path
     * @return int|null
     */
    private function getDirectorySize($path)
    {
        // Basics
        $path       = realpath($path);

        // Invalid path
        if (false === $path)
        {
            return 0;
        }

        // We can use exec(), you go dude!
        if (true === $this->canUseExec)
        {
            return $this->getDirectorySizeWithExec($path);
        }

        // Well, exec failed try popen()
        if (true === $this->canUsePopen)
        {
            return $this->getDirectorySizeWithPopen($path);
        }

        // Good, old PHP... slow but will get the job done
        //return $this->getDirectorySizeWithPHP($path);
        return null;
    }

    private function getDirectorySizeWithPHP($path)
    {
        // Iterator
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        $totalBytes = 0;

        // Loop & add file size
        foreach ($iterator as $file)
        {
            try {
                $totalBytes += $file->getSize();
            } // Some invalid symbolik links can cause issues in *nix systems
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
        // OS is not supported
        if (!in_array($this->OS, array("WIN", "LIN"), true))
        {
            return 0;
        }

        // WIN OS
        if ("WIN" === $this->OS)
        {
            return $this->getDirectorySizeForWinWithExec($path);
        }

        // *Nix OS
        return $this->getDirectorySizeForNixWithExec($path);
    }

    /**
     * @param string $path
     * @return int
     */
    private function getDirectorySizeForNixWithExec($path)
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
    private function getDirectorySizeForWinWithExec($path)
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
     * @param string $path
     * @return int
     */
    private function getDirectorySizeWithPopen($path)
    {
        // OS is not supported
        if (!in_array($this->OS, array("WIN", "LIN"), true))
        {
            return 0;
        }

        // WIN OS
        if ("WIN" === $this->OS)
        {
            return $this->getDirectorySizeForWinWithCOM($path);
        }

        // *Nix OS
        return $this->getDirectorySizeForNixWithPopen($path);
    }

    /**
     * @param string $path
     * @return int
     */
    private function getDirectorySizeForNixWithPopen($path)
    {
        $filePointer= popen("/usr/bin/du -sk {$path}", 'r');

        $size       = fgets($filePointer, 4096);
        $size       = (int) substr($size, 0, strpos($size, "\t"));

        pclose($filePointer);

        return $size;
    }

    /**
     * @param string $path
     * @return int
     */
    private function getDirectorySizeForWinWithCOM($path)
    {
        $com = new \COM ("scripting.filesystemobject");

        if (is_object($com))
        {
            $directory = $com->getfolder($path);

            return (int) $directory->size;
        }

        return 0;
    }

    /**
     * @return array
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * @return array
     */
    public function getBigFiles()
    {
        return $this->bigFiles;
    }

    /**
     * @return bool
     */
    public function isEnoughDiskSpace()
    {
        return $this->isEnoughDiskSpace;
    }
}