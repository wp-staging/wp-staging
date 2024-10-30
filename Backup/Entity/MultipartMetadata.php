<?php

namespace WPStaging\Backup\Entity;

use WPStaging\Framework\Traits\HydrateTrait;

/**
 * Class MultipartMetadata
 *
 * This is a OOP representation of the multipart metadata that is
 * stored in a within backup metadata, it contains information
 * related to backup multiparts like backup parts, total files in this part etc
 *
 * @package WPStaging\Backup\Entity
 */
class MultipartMetadata implements \JsonSerializable
{
    use HydrateTrait {
        hydrate as traitHydrate;
    }

    /** @var int total files in this part */
    private $totalFiles;

    /** @var int|string The backup file size in bytes. Default is empty string. */
    private $partSize = '';

    /** @var array List of plugins backup parts with their info */
    private $pluginsParts = [];

    /** @var array List of muplugins backup parts with their info */
    private $mupluginsParts = [];

    /** @var array List of themes backup parts with their info */
    private $themesParts = [];

    /** @var array List of uploads backup parts with their info */
    private $uploadsParts = [];

    /** @var array List of other backup parts with their info */
    private $othersParts = [];

    /** @var array List of others files in wp root backup parts with their info */
    private $otherWpRootParts = [];

    /** @var array List of database backup parts with their info */
    private $databaseParts = [];

    /** @var array List of database files to their extracted path */
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

    /**
     * @return int
     */
    public function getTotalFiles()
    {
        return $this->totalFiles;
    }

    /**
     * @param int $totalFiles
     */
    public function setTotalFiles($totalFiles)
    {
        $this->totalFiles = $totalFiles;
    }

    /**
     * @return int
     */
    public function getPartSize()
    {
        return (int)$this->partSize;
    }

    /**
     * @param int $partSize
     */
    public function setPartSize($partSize)
    {
        $this->partSize = (int)$partSize;
    }

    /**
     * @return array
     */
    public function getPluginsParts()
    {
        return $this->pluginsParts;
    }

    /**
     * @param array $parts
     */
    public function setPluginsParts($parts)
    {
        $this->pluginsParts = $parts;
    }

    /**
     * @return array
     */
    public function getMuPluginsParts()
    {
        return $this->mupluginsParts;
    }

    /**
     * @param array $parts
     */
    public function setMuPluginsParts($parts)
    {
        $this->mupluginsParts = $parts;
    }

    /**
     * @return array
     */
    public function getThemesParts()
    {
        return $this->themesParts;
    }

    /**
     * @param array $parts
     */
    public function setThemesParts($parts)
    {
        $this->themesParts = $parts;
    }

    /**
     * @return array
     */
    public function getUploadsParts()
    {
        return $this->uploadsParts;
    }

    /**
     * @param array $parts
     */
    public function setUploadsParts($parts)
    {
        $this->uploadsParts = $parts;
    }

    /**
     * @return array
     */
    public function getOthersParts()
    {
        return $this->othersParts;
    }

    /**
     * @param array $parts
     */
    public function setOthersParts($parts)
    {
        $this->othersParts = $parts;
    }

    /**
     * @return array
     */
    public function getOtherWpRootParts(): array
    {
        return $this->otherWpRootParts;
    }

    /**
     * @param array $parts
     *
     * @return void
     */
    public function setOtherWpRootParts(array $parts)
    {
        $this->otherWpRootParts = $parts;
    }

    /**
     * @return array
     */
    public function getDatabaseParts()
    {
        return $this->databaseParts;
    }

    /**
     * @param array $parts
     */
    public function setDatabaseParts($parts)
    {
        $this->databaseParts = $parts;
    }

    /**
     * @return array
     */
    public function getDatabaseFiles()
    {
        return $this->databaseFiles;
    }

    /**
     * @param array $files
     */
    public function setDatabaseFiles($files)
    {
        $this->databaseFiles = $files;
    }

    /**
     * @param string $part
     * @param array  $fileInfo
     */
    public function pushBackupPart($part, $fileInfo)
    {
        $partName            = $part . 'Parts';
        $this->{$partName}[] = $fileInfo;
    }

    /**
     * @param string $databaseFile
     */
    public function addDatabaseFile($databaseFile)
    {
        $this->databaseFiles[] = $databaseFile;
    }

    /**
     * @return array
     */
    public function getBackupParts()
    {
        return array_merge($this->databaseParts, $this->othersParts, $this->themesParts, $this->uploadsParts, $this->pluginsParts, $this->mupluginsParts, $this->otherWpRootParts);
    }

    /**
     * @return array
     */
    public function getFileParts()
    {
        return array_merge($this->othersParts, $this->themesParts, $this->pluginsParts, $this->mupluginsParts, $this->uploadsParts, $this->otherWpRootParts);
    }
}
