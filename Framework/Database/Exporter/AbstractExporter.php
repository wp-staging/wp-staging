<?php

namespace WPStaging\Framework\Database\Exporter;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;

abstract class AbstractExporter
{
    /**
     * @var string
     */
    const TMP_PREFIX_PLACEHOLDER = '{WPSTG_TMP_PREFIX}';

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

    /** @var array */
    protected $excludedTables = [];

    /** @var array Multisite subsites  */
    protected $subsites = [];

    /** @var bool */
    protected $isNetworkSiteBackup = false;

    /** @var int */
    protected $subsiteBlogId = 0;

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->client = $database->getClient();
        $this->sourceTablePrefix = $this->getWpDb()->prefix;
        $this->sourceTableBasePrefix = $this->database->getBasePrefix();
        $this->sourceTablePrefixLength = strlen($this->sourceTablePrefix);
    }

    /**
     * @param bool $isNetworkSiteBackup
     * @return void
     */
    public function setIsNetworkSiteBackup(bool $isNetworkSiteBackup)
    {
        $this->isNetworkSiteBackup = $isNetworkSiteBackup;
    }

    /**
     * @param array $subsites
     */
    public function setSubsites($subsites)
    {
        $this->subsites = $subsites;
    }

    /**
     * @param int $subsiteBlogId
     */
    public function setSubsiteBlogId(int $subsiteBlogId)
    {
        $this->subsiteBlogId = $subsiteBlogId;
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
     * @param string $filename
     */
    public function setFileName($filename)
    {
        $this->file = new FileObject($filename, FileObject::MODE_APPEND);
    }

    protected function getFinalPrefix()
    {
        return self::TMP_PREFIX_PLACEHOLDER;
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
     * @param string $tableName
     * @return string
     */
    protected function getPrefixedTableName(string $tableName): string
    {
        return $this->replacePrefix($tableName, $this->getFinalPrefix());
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function getPrefixedBaseTableName(string $tableName): string
    {
        return $this->replaceBasePrefix($tableName, $this->getFinalPrefix());
    }

    /**
     * @param string $prefixedString
     * @param string $newPrefix
     * @return string
     */
    protected function replacePrefix(string $prefixedString, string $newPrefix): string
    {
        return $newPrefix . substr($prefixedString, $this->sourceTablePrefixLength);
    }

    /**
     * @param string $prefixedString
     * @param string $newPrefix
     * @return string
     */
    protected function replaceBasePrefix(string $prefixedString, string $newPrefix): string
    {
        if (strpos($prefixedString, $this->sourceTableBasePrefix) !== 0) {
            return $prefixedString;
        }

        return $newPrefix . substr($prefixedString, strlen($this->sourceTableBasePrefix));
    }
}
