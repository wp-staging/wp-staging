<?php
namespace WPStaging\Utils;

// No Direct Access
if (!defined("WPINC"))
{
    die;
}

use WPStaging\WPStaging;

/**
 * Class Directories
 * @package WPStaging\Utils
 */
class Directories
{

    /**
     * @var string
     */
    private $OS;

    /**
     * @var bool
     */
    //private $canUseExec = false;

    /**
     * @var bool
     */
    //private $canUsePopen = false;

    /**
     * @var Logger
     */
    private $log;

    /**
     * Directories constructor.
     */
    public function __construct()
    {
        $info = new Info();

        $this->log          = WPStaging::getInstance()->get("logger");
        $this->OS           = $info->getOS();
        //$this->canUseExec   = $info->canUse("exec");
        //$this->canUsePopen  = $info->canUse("popen");

        // Windows Fix for Popen
//        if ("WIN" === $this->OS && true === $this->canUsePopen)
//        {
//            $this->canUsePopen = class_exists("\\COM");
//        }
    }

    /**
     * @param string $fileName
     */
//    public function setLoggerFileName($fileName)
//    {
//        $this->log->setFileName($fileName);
//    }

    /**
     * Gets size of given directory
     * @param string $path
     * @return int|null
     */
    public function size($path)
    {
        // Basics
        $path       = realpath($path);

        // Invalid path
        if (false === $path)
        {
            return null;
        }
        // Maybe we use this later. For now disabled for performance reasons
//        // We can use exec(), you go dude!
//        if (true === $this->canUseExec)
//        {
//            return $this->sizeWithExec($path);
//        }
//
//        // Well, exec failed try popen()
//        if (true === $this->canUsePopen)
//        {
//            return $this->sizeWithPopen($path);
//        }

        // Good, old PHP... slow but will get the job done
        return $this->sizeWithPHP($path);
    }

    /**
     * Get given directory size using PHP
     * WARNING! This function might cause memory / timeout issues
     * @param string $path
     * @return int
     */
    private function sizeWithPHP($path)
    {
        // Iterator
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
        );

        $totalBytes = 0;

        // Loop & add file size
        foreach ($iterator as $file)
        {
            try
            {
                $totalBytes += $file->getSize();
            }
            // Some invalid symbolik links can cause issues in *nix systems
            catch(\Exception $e)
            {
                $this->log->add("{$file} is a symbolic link or for some reason its size is invalid");
            }
        }

        return $totalBytes;
    }

    /**
     * @param string $path
     * @return int
     * @deprecated since version 2.0.0
     * 
     */
//    private function sizeWithExec($path)
//    {
//        // OS is not supported
//        if (!in_array($this->OS, array("WIN", "LIN"), true))
//        {
//            return 0;
//        }
//
//        // WIN OS
//        if ("WIN" === $this->OS)
//        {
//            return $this->sizeForWinWithExec($path);
//        }
//
//        // *Nix OS
//        return $this->sizeForNixWithExec($path);
//    }

    /**
     * @param string $path
     * @return int
     * @deprecated since version 2.0.0
     */
//    private function sizeForNixWithExec($path)
//    {
//        exec("du -s {$path}", $output, $return);
//
//        $size = explode("\t", $output[0]);
//
//        if (0 == $return && count($size) == 2)
//        {
//            return (int) $size[0];
//        }
//
//        return 0;
//    }

    /**
     * @param string $path
     * @return int
     * @deprecated since version 2.0.0
     */
//    private function sizeForWinWithExec($path)
//    {
//        exec("diruse {$path}", $output, $return);
//
//        $size = explode("\t", $output[0]);
//
//        if (0 == $return && count($size) >= 4)
//        {
//            return (int) $size[0];
//        }
//
//        return 0;
//    }

    /**
     * @param string $path
     * @return int
     * @deprecated since version 2.0.0
     */
//    private function sizeWithPopen($path)
//    {
//        // OS is not supported
//        if (!in_array($this->OS, array("WIN", "LIN"), true))
//        {
//            return 0;
//        }
//
//        // WIN OS
//        if ("WIN" === $this->OS)
//        {
//            return $this->sizeForWinWithCOM($path);
//        }
//
//        // *Nix OS
//        return $this->sizeForNixWithPopen($path);
//    }

    /**
     * @param string $path
     * @return int
     * @deprecated since version 2.0.0
     */
//    private function sizeForNixWithPopen($path)
//    {
//        $filePointer= popen("/usr/bin/du -sk {$path}", 'r');
//
//        $size       = fgets($filePointer, 4096);
//        $size       = (int) substr($size, 0, strpos($size, "\t"));
//
//        pclose($filePointer);
//
//        return $size;
//    }

    /**
     * @param string $path
     * @return int
     * @deprecated since version 2.0.0
     **/
//    private function sizeForWinWithCOM($path)
//    {
//        if (!class_exists("\\COM"))
//        {
//            return 0;
//        }
//
//        $com = new \COM("scripting.filesystemobject");
//
//        $directory = $com->getfolder($path);
//
//        return (int) $directory->size;
//    }
}