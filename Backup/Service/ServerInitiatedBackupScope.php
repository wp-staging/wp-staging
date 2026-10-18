<?php

namespace WPStaging\Backup\Service;

use WPStaging\Backup\Entity\BackupMetadata;






class ServerInitiatedBackupScope
{



    public static function forCurrentContext(): array
    {
        if (!is_multisite()) {
            return [
                'backupType'    => BackupMetadata::BACKUP_TYPE_SINGLE,
                'subsiteBlogId' => null,
            ];
        }

        if (self::mayBackUpWholeNetwork()) {
            return [
                'backupType'    => BackupMetadata::BACKUP_TYPE_MULTISITE,
                'subsiteBlogId' => null,
            ];
        }

        return [
            'backupType'    => BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE,
            'subsiteBlogId' => get_current_blog_id(),
        ];
    }

    private static function mayBackUpWholeNetwork(): bool
    {
        if (is_user_logged_in()) {
            return is_super_admin();
        }

        return is_main_site();
    }

 
    public static function isLimitedToCurrentSite(): bool
    {
        return self::forCurrentContext()['backupType'] === BackupMetadata::BACKUP_TYPE_NETWORK_SUBSITE;
    }
}
