<?php

namespace WPStaging\Backup\Dto\Service;

use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Database\DatabaseImporter;

class DatabaseImporterDto
{
 
    private $currentIndex = 0;

 
    private $fileOffset = 0;

 
    private $totalLines = 0;

 
    private $tableToRestore = '';

 
    private $tmpPrefix = '';

 
    private $shortTablesToRestore = [];

 
    private $shortTablesToDrop = [];

 
    private $backupType = BackupMetadata::BACKUP_TYPE_SINGLE;

 
    private $subsiteId = null;

    public function getFileOffset(): int
    {
        return $this->fileOffset;
    }





    public function setFileOffset(int $fileOffset)
    {
        $this->fileOffset = $fileOffset;
    }

    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }





    public function setCurrentIndex(int $currentIndex)
    {
        $this->currentIndex = $currentIndex;
    }

    public function getTotalLines(): int
    {
        return $this->totalLines;
    }





    public function setTotalLines(int $totalLines)
    {
        $this->totalLines = $totalLines;
    }




    public function finish()
    {
        $this->currentIndex = $this->totalLines;
    }

    public function getTableToRestore(): string
    {
        return $this->tableToRestore;
    }





    public function setTableToRestore(string $tableToRestore)
    {
        $this->tableToRestore = $tableToRestore;
    }

    public function getTmpPrefix(): string
    {
        return $this->tmpPrefix;
    }





    public function setTmpPrefix(string $tmpPrefix)
    {
        $this->tmpPrefix = $tmpPrefix;
    }

    public function addShortNameTable(string $table, string $prefix): string
    {
        $shortName = uniqid($prefix) . str_pad((string)rand(0, 999999), 6, '0', STR_PAD_LEFT);
        if ($prefix === $this->tmpPrefix) {
            $this->shortTablesToRestore[$shortName] = $table;

            return $shortName;
        }

        if ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) {
            $this->shortTablesToDrop[$shortName] = $table;

            return $shortName;
        }

        throw new \RuntimeException(sprintf('Cannot shorten table %s: the prefix %s belongs to neither the restore nor the drop set.', $table, $prefix), DatabaseImporter::SHORT_NAME_MISSING_EXCEPTION_CODE);
    }

    public function getShortNameTable(string $table, string $prefix): string
    {
        $shortTables = [];
        if ($prefix === $this->tmpPrefix) {
            $shortTables = $this->shortTablesToRestore;
        } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) {
            $shortTables = $this->shortTablesToDrop;
        }

        $shortName = array_search($table, $shortTables);
        if ($shortName === false) {
            throw new \RuntimeException(sprintf('No shortened name was stored for table %s under prefix %s.', $table, $prefix), DatabaseImporter::SHORT_NAME_MISSING_EXCEPTION_CODE);
        }

        return (string)$shortName;
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






    public function getShortTables(string $prefix): array
    {
        if ($prefix === $this->tmpPrefix) {
            return $this->shortTablesToRestore;
        } elseif ($prefix === DatabaseImporter::TMP_DATABASE_PREFIX_TO_DROP) {
            return $this->shortTablesToDrop;
        }

        return [];
    }







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





    public function setBackupType(string $backupType)
    {
        $this->backupType = $backupType;
    }




    public function getSubsiteId()
    {
        return $this->subsiteId;
    }





    public function setSubsiteId($subsiteId)
    {
        $this->subsiteId = $subsiteId;
    }
}
