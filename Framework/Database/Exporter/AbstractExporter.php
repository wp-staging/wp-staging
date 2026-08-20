<?php

namespace WPStaging\Framework\Database\Exporter;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;

abstract class AbstractExporter
{
 
    const BYTES_UNKNOWN = -1;

 
    const TRUNCATE_DONE = 1;

 
    const TRUNCATE_NOT_NEEDED = 0;

 
    const TRUNCATE_FAILED = -1;




    const TMP_PREFIX_PLACEHOLDER = '{WPSTG_TMP_PREFIX}';

 
    protected $client;

 
    protected $database;

    protected $sourceTablePrefix;

    protected $sourceTableBasePrefix;

 
    protected $sourceTablePrefixLength;

 
    protected $file;

 
    protected $excludedTables = [];

 
    protected $subsites = [];

 
    protected $isNetworkSiteBackup = false;

 
    protected $subsiteBlogId = 0;

    public function __construct(Database $database)
    {
        $this->setDatabase($database);
    }





    public function setIsNetworkSiteBackup(bool $isNetworkSiteBackup)
    {
        $this->isNetworkSiteBackup = $isNetworkSiteBackup;
    }




    public function setSubsites($subsites)
    {
        $this->subsites = $subsites;
    }




    public function setSubsiteBlogId(int $subsiteBlogId)
    {
        $this->subsiteBlogId = $subsiteBlogId;
    }




    public function setTablesToExclude($tablesToExclude)
    {
        foreach ($tablesToExclude as $tableWithoutPrefix) {
            $this->excludedTables[] = $this->sourceTableBasePrefix . $tableWithoutPrefix;
            $this->addExcludedTablesForSubsites($this->sourceTableBasePrefix, $tableWithoutPrefix);
        }
    }




    public function setFileName($filename)
    {
        $this->file = new FileObject($filename, FileObject::MODE_APPEND);
    }









    public function getWrittenBytes(): int
    {
        if (!$this->file instanceof FileObject) {
            return self::BYTES_UNKNOWN;
        }

 
        if ($this->file->fflush() === false) {
            return self::BYTES_UNKNOWN;
        }

        clearstatcache(true, $this->file->getPathname());
        $bytes = filesize($this->file->getPathname());

        if ($bytes === false) {
            return self::BYTES_UNKNOWN;
        }

        return (int)$bytes;
    }















    public function truncateTo(int $bytes): int
    {
        if (!$this->file instanceof FileObject || $bytes < 0) {
            return self::TRUNCATE_FAILED;
        }

        $writtenBytes = $this->getWrittenBytes();

        if ($writtenBytes === self::BYTES_UNKNOWN) {
            return self::TRUNCATE_FAILED;
        }

        if ($writtenBytes === $bytes) {
            return self::TRUNCATE_NOT_NEEDED;
        }

 
 
 
        if ($writtenBytes < $bytes) {
            return self::TRUNCATE_FAILED;
        }

        if (!$this->file->ftruncate($bytes)) {
            return self::TRUNCATE_FAILED;
        }

        clearstatcache(true, $this->file->getPathname());

        return self::TRUNCATE_DONE;
    }

    protected function getFinalPrefix()
    {
        return self::TMP_PREFIX_PLACEHOLDER;
    }





    protected function addExcludedTablesForSubsites($tablePrefix, $tableWithoutPrefix)
    {
        if (!is_multisite()) {
            return;
        }

        foreach ($this->subsites as $subsite) {
            $siteId = $subsite['blog_id'];
            if (empty($siteId) || $siteId === 1) {
                continue;
            }

            $tableName = $tablePrefix . $siteId . '_' . $tableWithoutPrefix;
            if (!in_array($tableName, $this->excludedTables)) {
                $this->excludedTables[] = $tableName;
            }
        }
    }

    protected function getWpDb()
    {
        return $this->database->getWpdba()->getClient();
    }





    protected function getPrefixedTableName(string $tableName): string
    {
        return $this->replacePrefix($tableName, $this->getFinalPrefix());
    }





    protected function getPrefixedBaseTableName(string $tableName): string
    {
        return $this->replaceBasePrefix($tableName, $this->getFinalPrefix());
    }






    protected function replacePrefix(string $prefixedString, string $newPrefix): string
    {
        return $newPrefix . substr($prefixedString, $this->sourceTablePrefixLength);
    }






    protected function replaceBasePrefix(string $prefixedString, string $newPrefix): string
    {
        if (strpos($prefixedString, $this->sourceTableBasePrefix) !== 0) {
            return $prefixedString;
        }

        return $newPrefix . substr($prefixedString, strlen($this->sourceTableBasePrefix));
    }

    protected function setDatabase(Database $database)
    {
        $this->database = $database;
        $this->client = $database->getClient();
        $this->sourceTablePrefix = $this->getWpDb()->prefix;
        $this->sourceTableBasePrefix = $this->database->getBasePrefix();
        $this->sourceTablePrefixLength = strlen($this->sourceTablePrefix);
    }
}
