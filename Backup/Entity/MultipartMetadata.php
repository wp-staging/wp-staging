<?php

namespace WPStaging\Backup\Entity;

use WPStaging\Framework\Traits\HydrateTrait;










class MultipartMetadata implements \JsonSerializable
{
    use HydrateTrait {
        hydrate as traitHydrate;
    }

 
    private $totalFiles;

 
    private $partSize = '';

 
    private $pluginsParts = [];

 
    private $mupluginsParts = [];

 
    private $themesParts = [];

 
    private $uploadsParts = [];

 
    private $othersParts = [];

 
    private $otherWpRootParts = [];

 
    private $databaseParts = [];

 
    private $databaseFiles = [];

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        $array = get_object_vars($this);

        return $array;
    }

    public function hydrate(array $data = [])
    {
        $this->traitHydrate($data);

        return $this;
    }




    public function getTotalFiles()
    {
        return $this->totalFiles;
    }




    public function setTotalFiles($totalFiles)
    {
        $this->totalFiles = $totalFiles;
    }




    public function getPartSize()
    {
        return (int)$this->partSize;
    }




    public function setPartSize($partSize)
    {
        $this->partSize = (int)$partSize;
    }




    public function getPluginsParts()
    {
        return $this->pluginsParts;
    }




    public function setPluginsParts($parts)
    {
        $this->pluginsParts = $parts;
    }




    public function getMuPluginsParts()
    {
        return $this->mupluginsParts;
    }




    public function setMuPluginsParts($parts)
    {
        $this->mupluginsParts = $parts;
    }




    public function getThemesParts()
    {
        return $this->themesParts;
    }




    public function setThemesParts($parts)
    {
        $this->themesParts = $parts;
    }




    public function getUploadsParts()
    {
        return $this->uploadsParts;
    }




    public function setUploadsParts($parts)
    {
        $this->uploadsParts = $parts;
    }




    public function getOthersParts()
    {
        return $this->othersParts;
    }




    public function setOthersParts($parts)
    {
        $this->othersParts = $parts;
    }




    public function getOtherWpRootParts(): array
    {
        return $this->otherWpRootParts;
    }






    public function setOtherWpRootParts(array $parts)
    {
        $this->otherWpRootParts = $parts;
    }




    public function getDatabaseParts()
    {
        return $this->databaseParts;
    }




    public function setDatabaseParts($parts)
    {
        $this->databaseParts = $parts;
    }




    public function getDatabaseFiles()
    {
        return $this->databaseFiles;
    }




    public function setDatabaseFiles($files)
    {
        $this->databaseFiles = $files;
    }





    public function pushBackupPart($part, $fileInfo)
    {
        $partName            = $part . 'Parts';
        $this->{$partName}[] = $fileInfo;
    }




    public function addDatabaseFile($databaseFile)
    {
        $this->databaseFiles[] = $databaseFile;
    }




    public function getBackupParts()
    {
        return array_merge($this->databaseParts, $this->othersParts, $this->themesParts, $this->uploadsParts, $this->pluginsParts, $this->mupluginsParts, $this->otherWpRootParts);
    }




    public function getFileParts()
    {
        return array_merge($this->othersParts, $this->themesParts, $this->pluginsParts, $this->mupluginsParts, $this->uploadsParts, $this->otherWpRootParts);
    }
}
