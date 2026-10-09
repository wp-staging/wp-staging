<?php

namespace WPStaging\Backup\Transfer;

use WPStaging\Backup\Service\Archiver;




class TransferArtifactNaming
{
 
    const BASE_NAME = 'backup';

 
    const NAME_HASH_LENGTH = 12;

 
    const FINAL_BACKUP_EXTENSIONS = [Archiver::BACKUP_EXTENSION, 'sql'];

    public function isFinalizedBackupFile(string $sourcePath): bool
    {
        return in_array($this->getSourceExtension($sourcePath), self::FINAL_BACKUP_EXTENSIONS, true);
    }

 
    public function isFinishedSyncBackupFile(string $sourcePath): bool
    {
        return $this->isFinalizedBackupFile($sourcePath) || $this->getSourceExtension($sourcePath) === Archiver::TMP_BACKUP_EXTENSION;
    }






    public function getArtifactFileName(string $sourcePath): string
    {
        return self::BASE_NAME . '-' . substr(md5(basename($sourcePath)), 0, self::NAME_HASH_LENGTH) . '.' . $this->getSourceExtension($sourcePath);
    }

    private function getSourceExtension(string $sourcePath): string
    {
        return strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    }
}
