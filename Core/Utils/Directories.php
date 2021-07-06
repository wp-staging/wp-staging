<?php

namespace WPStaging\Core\Utils;

use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WPStaging\Core\WPStaging;

/**
 * Class Directories
 * @package WPStaging\Core\Utils
 */
class Directories
{

    /**
     * @var string
     */
    private $OS;

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
    }


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
        if ($path === false) {
            return null;
        }

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
        $totalBytes = 0;

        try {
            // Iterator
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            // Loop & add file size
            foreach ($iterator as $file) {
                try {
                    $totalBytes += $file->getSize();
                }
                // Some invalid symbolik links can cause issues in *nix systems
                catch (Exception $e) {
                    $this->log->add("{$file} is a symbolic link or for some reason its size is invalid");
                }
            }
        } catch (Exception $e) {
            $this->log->add("System Error: " . $e->getMessage());
        }

        return $totalBytes;
    }
}
