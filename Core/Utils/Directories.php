<?php

namespace WPStaging\Core\Utils;

use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WPStaging\Core\WPStaging;





class Directories
{




    private $log;




    public function __construct()
    {
        $this->log = WPStaging::getInstance()->get("logger");
    }







    public function size($path)
    {
 
        $path       = realpath($path);

 
        if ($path === false) {
            return null;
        }

 
        return $this->sizeWithPHP($path);
    }







    private function sizeWithPHP($path)
    {
        $totalBytes = 0;

        try {
 
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

 
            foreach ($iterator as $file) {
                try {
                    $totalBytes += $file->getSize();
                }
 
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
