<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Filesystem\PartIdentifier;

trait WithBackupIdentifier
{




    protected $listedMultipartBackups = [];






    public function checkPartByIdentifier(string $identifier, string $input)
    {
        return preg_match("#{$identifier}(.[0-9]+)?.wpstg$#", $input);
    }





    public function isBackupPart(string $name)
    {
        if (preg_match($this->getDatabasePartSuffixPattern(), $name)) {
            return true;
        }

        $dbIdentifier          = PartIdentifier::DATABASE_PART_IDENTIFIER;
        $pluginIdentifier      = PartIdentifier::PLUGIN_PART_IDENTIFIER;
        $mupluginIdentifier    = PartIdentifier::MU_PLUGIN_PART_IDENTIFIER;
        $themeIdentifier       = PartIdentifier::THEME_PART_IDENTIFIER;
        $uploadIdentifier      = PartIdentifier::UPLOAD_PART_IDENTIFIER;
        $otherIdentifier       = PartIdentifier::OTHER_WP_CONTENT_PART_IDENTIFIER;
        $otherWpRootIdentifier = PartIdentifier::OTHER_WP_ROOT_PART_IDENTIFIER;

        $identifiers = "({$dbIdentifier}|{$pluginIdentifier}|{$mupluginIdentifier}|{$themeIdentifier}|{$uploadIdentifier}|{$otherIdentifier}|{$otherWpRootIdentifier})";

        if ($this->checkPartByIdentifier($identifiers, $name)) {
            return true;
        }

        return false;
    }




    public function clearListedMultipartBackups()
    {
        $this->listedMultipartBackups = [];
    }

    public function isListedMultipartBackup(string $filename, bool $shouldAddBackup = true)
    {
        $id = $this->extractBackupIdFromFilename($filename);
        if (in_array($id, $this->listedMultipartBackups)) {
            return true;
        }

        if ($shouldAddBackup) {
            $this->listedMultipartBackups[] = $id;
        }

        return false;
    }





    public function extractBackupIdFromFilename(string $filename)
    {
        if (preg_match($this->getDatabasePartSuffixPattern(), $filename)) {
            return $this->extractBackupIdFromDatabaseBackupFilename($filename);
        }

        $fileInfos = explode('_', $filename);
        $fileInfos = $fileInfos[count($fileInfos) - 1];
        return explode('.', $fileInfos)[0];
    }





    protected function extractBackupIdFromDatabaseBackupFilename(string $filename)
    {
        $filename = preg_replace($this->getDatabasePartSuffixPattern(), '', $filename);

        $lastDotPosition = strrpos($filename, '.');
        if ($lastDotPosition !== false) {
            $filename = substr($filename, 0, $lastDotPosition);
        }

        $fileInfos = explode('_', $filename);
        return $fileInfos[count($fileInfos) - 1];
    }




    protected function getDatabasePartSuffixPattern(): string
    {
        return '#\.' . PartIdentifier::DATABASE_PART_IDENTIFIER . '(\.\d+)?\.' . DatabaseImporter::FILE_FORMAT . '$#';
    }
}
