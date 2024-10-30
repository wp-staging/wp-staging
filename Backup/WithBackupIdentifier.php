<?php

namespace WPStaging\Backup;

use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Filesystem\PartIdentifier;

trait WithBackupIdentifier
{
    /**
     * List of ids of multipart backups
     * @var string[]
     */
    protected $listedMultipartBackups = [];

    /**
     * @param string $identifier
     * @param string $input
     * @return bool
     */
    public function checkPartByIdentifier(string $identifier, string $input)
    {
        return preg_match("#{$identifier}(.[0-9]+)?.wpstg$#", $input);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isBackupPart(string $name)
    {
        $dbExtension  = DatabaseImporter::FILE_FORMAT;
        $dbIdentifier = PartIdentifier::DATABASE_PART_IDENTIFIER;
        if (preg_match("#{$dbIdentifier}(.[0-9]+)?.{$dbExtension}$#", $name)) {
            return true;
        }

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

    /**
     * @return void
     */
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

    /**
     * @param string $filename
     * @return string
     */
    public function extractBackupIdFromFilename(string $filename)
    {
        if (strpos($filename, '.' . PartIdentifier::DATABASE_PART_IDENTIFIER . '.' . DatabaseImporter::FILE_FORMAT) !== false) {
            return $this->extractBackupIdFromDatabaseBackupFilename($filename);
        }

        $fileInfos = explode('_', $filename);
        $fileInfos = $fileInfos[count($fileInfos) - 1];
        return explode('.', $fileInfos)[0];
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function extractBackupIdFromDatabaseBackupFilename(string $filename)
    {
        // This is required if the table prefix contains underscore like wp_some
        $filename = str_replace('.' . PartIdentifier::DATABASE_PART_IDENTIFIER . '.' . DatabaseImporter::FILE_FORMAT, '', $filename);
        // Get position of last dot . in filename
        $lastDotPosition = strrpos($filename, '.');
        // Get filename until last dot to remove the table prefix
        $filename = substr($filename, 0, $lastDotPosition);

        $fileInfos = explode('_', $filename);
        return $fileInfos[count($fileInfos) - 1];
    }
}
