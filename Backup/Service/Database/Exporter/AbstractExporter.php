<?php

namespace WPStaging\Backup\Service\Database\Exporter;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Backup\Exceptions\DiskNotWritableException;

abstract class AbstractExporter
{
    /** @var InterfaceDatabaseClient */
    protected $client;

    /** @var Database */
    protected $database;

    protected $sourceTablePrefix;

    protected $sourceTableBasePrefix;

    // We cache this value to calculate it only once, since this can run millions of times
    protected $sourceTablePrefixLength;

    /** @var FileObject */
    protected $file;

    protected $excludedTables = [];

    /** @array Multisite subsites  */
    protected $subsites = [];

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->client = $database->getClient();
        $this->sourceTablePrefix = $this->getWpDb()->prefix;
        $this->sourceTableBasePrefix = $this->database->getBasePrefix();
        $this->sourceTablePrefixLength = strlen($this->sourceTablePrefix);
    }

    /**
     * @param array $subsites
     */
    public function setSubsites($subsites)
    {
        $this->subsites = $subsites;
    }

    /**
     * @param array $tablesToExclude Table Names without prefix
     */
    public function setTablesToExclude($tablesToExclude)
    {
        foreach ($tablesToExclude as $tableWithoutPrefix) {
            $this->excludedTables[] = $this->sourceTableBasePrefix . $tableWithoutPrefix;
            $this->addExcludedTablesForSubsites($this->sourceTableBasePrefix, $tableWithoutPrefix);
        }
    }

    /**
     * @param string $tablePrefix
     * @param string $tableWithoutPrefix Table name without prefix
     */
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

    /**
     * @param string $filename
     * @throws DiskNotWritableException
     */
    public function setFileName($filename)
    {
        $this->file = new FileObject($filename, FileObject::MODE_APPEND);
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function getPrefixedTableName($tableName)
    {
        return $this->replacePrefix($tableName, '{WPSTG_TMP_PREFIX}');
    }

    protected function replacePrefix($prefixedString, $newPrefix)
    {
        return $newPrefix . substr($prefixedString, $this->sourceTablePrefixLength);
    }
}
