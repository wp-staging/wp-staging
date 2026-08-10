<?php

namespace WPStaging\Framework\Database\Exporter;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Filesystem\FileObject;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;

abstract class AbstractExporter
{
    /** @var int Returned by getWrittenBytes() when the size on disk could not be measured. */
    const BYTES_UNKNOWN = -1;

    /** @var int truncateTo(): bytes past the checkpoint were removed. */
    const TRUNCATE_DONE = 1;

    /** @var int truncateTo(): the file already ended at or before the checkpoint. */
    const TRUNCATE_NOT_NEEDED = 0;

    /** @var int truncateTo(): the file could not be measured or shortened. */
    const TRUNCATE_FAILED = -1;

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
        $this->setDatabase($database);
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

    /**
     * @return int Bytes written to the sql file so far.
     */
    /**
     * @return int Bytes on disk, or self::BYTES_UNKNOWN when the size could not be measured.
     *             A caller must never treat that as a byte count: a checkpoint short of the
     *             real size truncates rows the offset already accounts for.
     */
    public function getWrittenBytes(): int
    {
        if (!$this->file instanceof FileObject) {
            return self::BYTES_UNKNOWN;
        }

        // filesize() misses the stream buffer, and a short checkpoint discards accounted rows.
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

    /**
     * Discard everything written past $bytes.
     *
     * @param int $bytes
     * @return bool True if the file was shortened.
     */
    /**
     * A single false would conflate a file that is already short enough with a truncate that
     * failed. The first is routine, the second leaves unaccounted rows in the export and
     * recreates the duplicate-key failure this checkpoint exists to prevent, so the caller
     * has to be able to tell them apart.
     *
     * @return int One of self::TRUNCATE_DONE, self::TRUNCATE_NOT_NEEDED, self::TRUNCATE_FAILED.
     */
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

        // Fewer bytes than the checkpoint means this is not the file the checkpoint was taken
        // from — deleted, replaced, or reopened empty by the append mode. Resuming would carry
        // on from a row offset that accounts for rows the export no longer holds.
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

    protected function setDatabase(Database $database)
    {
        $this->database = $database;
        $this->client = $database->getClient();
        $this->sourceTablePrefix = $this->getWpDb()->prefix;
        $this->sourceTableBasePrefix = $this->database->getBasePrefix();
        $this->sourceTablePrefixLength = strlen($this->sourceTablePrefix);
    }
}
