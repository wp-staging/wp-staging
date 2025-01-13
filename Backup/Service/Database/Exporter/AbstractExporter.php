<?php
namespace WPStaging\Backup\Service\Database\Exporter;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
abstract class AbstractExporter
{
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
        $this->database = $database;
        $this->client = $database->getClient();
        $this->sourceTablePrefix = $this->getWpDb()->prefix;
        $this->sourceTableBasePrefix = $this->database->getBasePrefix();
        $this->sourceTablePrefixLength = strlen($this->sourceTablePrefix);
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

    public function setFileName($filename)
    {
        $this->file = new FileObject($filename, FileObject::MODE_APPEND);
    }

    protected function getPrefixedTableName(string $tableName): string
    {
        return $this->replacePrefix($tableName, '{WPSTG_TMP_PREFIX}');
    }

    protected function getPrefixedBaseTableName(string $tableName): string
    {
        return $this->replaceBasePrefix($tableName, '{WPSTG_TMP_PREFIX}');
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
}
