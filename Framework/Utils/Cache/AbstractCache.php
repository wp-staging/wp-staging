<?php

 
 
 

namespace WPStaging\Framework\Utils\Cache;

use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Framework\Exceptions\IOException;
use WPStaging\Framework\Adapter\Directory;

use function WPStaging\functions\debug_log;

abstract class AbstractCache
{
 
    const DEFAULT_LIFETIME = 2592000; 

 
    const EXTENSION        = 'cache';

 
    protected $lifetime;

 
    protected $path;

 
    protected $filename;

 
    protected $filePath;

    public function __construct(Directory $directory)
    {
        $this->setPath($directory->getCacheDirectory());
        $this->setLifetime(self::DEFAULT_LIFETIME);
    }






    abstract public function get($default = null);






    abstract public function save($value);





    public function delete()
    {
        if (!is_file($this->filePath)) {
            return;
        }

        if (unlink($this->filePath)) {
            return;
        }

        debug_log(sprintf('Attempting to delete invalid cache file (%s) failed', $this->filePath));
        throw new IOException(sprintf('Attempting to delete invalid cache file (%s) failed', $this->filePath));
    }







    public function rename($newName)
    {
        $newFilePath = $this->path . $newName . '.' . self::EXTENSION;

        if (!rename($this->filePath, $newFilePath)) {
            debug_log(sprintf('Renaming cache file (%s) to (%s) failed', $this->filePath, $newFilePath));
            throw new IOException(sprintf('Renaming cache file (%s) to (%s) failed', $this->filePath, $newFilePath));
        }

 
        $this->filename = $newName;
        $this->filePath = $newFilePath;
    }




    public function setLifetime($lifetime)
    {
        $this->lifetime = (int)$lifetime;
    }




    public function getPath()
    {
        return $this->path;
    }




    public function setPath($path)
    {
        $this->path = $path;

        (new Filesystem())->mkdir($path, true);

        $this->initializeFilePath();
    }




    public function getFilename()
    {
        return $this->filename;
    }




    public function setFilename($filename)
    {
        $this->filename = $filename;
        $this->initializeFilePath();
    }




    public function getFilePath()
    {
        return $this->filePath;
    }







    public function isValid($delete = true)
    {
        if (!$this->filePath || !is_file($this->filePath)) {
            return false;
        }

        if (!$this->isExpired()) {
            return true;
        }

        if ($delete) {
            $this->delete();
        }

        return false;
    }




    protected function isExpired()
    {
        if ($this->lifetime === -1) {
            return false;
        }

        return $this->lifetime <= time() - filemtime($this->filePath);
    }




    protected function getFileExtension(): string
    {
        return self::EXTENSION;
    }




    private function initializeFilePath()
    {
        $this->filePath = $this->path;
        if ($this->filename) {
            $this->filePath .= $this->filename . '.' . $this->getFileExtension();
        }
    }
}
