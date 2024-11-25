<?php

namespace WPStaging\Backup\Dto\Service;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Database\DatabaseImporter;

class DatabaseImporterDto
{
    /** @var int */
    private $currentIndex = 0;

    /** @var int */
    private $totalLines = 0;

    /** @var string */
    private $tableToRestore = '';

    /** @var string */
    private $tmpPrefix = '';

    /** @var array<string, string> */
    private $shortTablesToRestore = [];

    /** @var array<string, string> */
    private $shortTablesToDrop = [];

    /** @var string */
    private $backupType = BackupMetadata::BACKUP_TYPE_SINGLE;

    /** @var int|null */
    private $subsiteId = null;

    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }

    /**
     * @param int $currentIndex
     * @return void
     */
    public function setCurrentIndex(int $currentIndex)
    {
        $this->currentIndex = $currentIndex;
    }

    public function getTotalLines(): int
    {
        return $this->totalLines;
    }

    /**
     * @param int $totalLines
     * @return void
     */
    public function setTotalLines(int $totalLines)
    {
        $this->totalLines = $totalLines;
    }

    /**
     * @return void
     */
    public function finish()
    {
        $this->currentIndex = $this->totalLines;
    }

    public function getTableToRestore(): string
    {
        return $this->tableToRestore;
    }

    /**
     * @param string $tableToRestore
     * @return void
     */
    public function setTableToRestore(string $tableToRestore)
    {
        $this->tableToRestore = $tableToRestore;
    }

    public function getTmpPrefix(): string
    {
        return $this->tmpPrefix;
    }

    /**
     * @param string $tmpPrefix
     * @return void
     */
    public function setTmpPrefix(string $tmpPrefix)
    {
        $this->tmpPrefix = $tmpPrefix;
    }

    public function addShortNameTable(string $table, string $prefix): string
    {
        $shortName = uniqid($prefix) . str_pad(rand(0, 999999), 6, '0');
        if ($prefix === $this->tmpPrefix) {
            $this->shortTablesToRestore[$shortName] = $table;
        } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) {
            $this->shortTablesToDrop[$shortName] = $table;
        }

        return $shortName;
    }

    public function getShortNameTable(string $table, string $prefix): string
    {
        $shortTables = [];
        if ($prefix === $this->tmpPrefix) {
            $shortTables = $this->shortTablesToRestore;
        } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) {
            $shortTables = $this->shortTablesToDrop;
        }

        return (string)array_search($table, $shortTables);
    }

    public function getFullNameTableFromShortName(string $table, string $prefix): string
    {
        $shortTables = [];
        if ($prefix === $this->tmpPrefix) {
            $shortTables = $this->shortTablesToRestore;
        } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) {
            $shortTables = $this->shortTablesToDrop;
        }

        if (!array_key_exists($table, $shortTables)) {
            return $table;
        }

        return $shortTables[$table];
    }

    /**
     * @param string $prefix
     *
     * @return array<string, string>
     */
    public function getShortTables(string $prefix): array
    {
        if ($prefix === $this->tmpPrefix) {
            return $this->shortTablesToRestore;
        } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) {
            return $this->shortTablesToDrop;
        }

        return [];
    }

    /**
     * @param array $tables
     * @param string $prefix
     *
     * @return void
     */
    public function setShortTables(array $tables, string $prefix)
    {
        if ($prefix === $this->tmpPrefix) {
            $this->shortTablesToRestore = $tables;
        } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) {
            $this->shortTablesToDrop = $tables;
        }
    }

    public function getBackupType(): string
    {
        return $this->backupType;
    }

    /**
     * @param string $backupType
     * @return void
     */
    public function setBackupType(string $backupType)
    {
        $this->backupType = $backupType;
    }

    /**
     * @return int|null
     */
    public function getSubsiteId()
    {
        return $this->subsiteId;
    }

    /**
     * @param int|null $subsiteId
     * @return void
     */
    public function setSubsiteId($subsiteId)
    {
        $this->subsiteId = $subsiteId;
    }
}
