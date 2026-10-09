<?php

namespace WPStaging\Backup\Transfer;

use WPStaging\Framework\Database\CustomTable;





class TransferSessionTable extends CustomTable
{
 
    const TABLE_NAME = 'wpstg_transfer_sessions';

 
    const TABLE_VERSION_KEY = 'wpstg_transfer_sessions_table_version';







    public function getFullTableName()
    {
        global $wpdb;

        return $wpdb->base_prefix . self::TABLE_NAME;
    }

 
    protected function getTableName()
    {
        return self::TABLE_NAME;
    }

 
    protected function getTableVersionKey()
    {
        return self::TABLE_VERSION_KEY;
    }

 
    protected function getTableVersion()
    {
        return '1.2.0';
    }






    protected function getCreateTableSql()
    {
        global $wpdb;
        $collate   = $wpdb->collate;
        $tableName = $this->getFullTableName();

        $sql = "CREATE TABLE {$tableName} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            backup_id VARCHAR(191) NOT NULL DEFAULT '',
            source_path TEXT DEFAULT NULL,
            public_token_hash CHAR(64) NOT NULL DEFAULT '',
            public_token_prefix VARCHAR(16) DEFAULT NULL,
            transfer_dir TEXT DEFAULT NULL,
            transfer_file_path TEXT DEFAULT NULL,
            transfer_url TEXT DEFAULT NULL,
            artifact_mode VARCHAR(32) NOT NULL DEFAULT '',
            status VARCHAR(32) NOT NULL DEFAULT 'preparing',
            file_size BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            delete_after DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT NULL,
            created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            blog_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            download_started_count INT(10) UNSIGNED NOT NULL DEFAULT 0,
            cleanup_attempts INT(10) UNSIGNED NOT NULL DEFAULT 0,
            last_accessed_at DATETIME DEFAULT NULL,
            completed_at DATETIME DEFAULT NULL,
            revoked_at DATETIME DEFAULT NULL,
            error_message TEXT DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY public_token_hash (public_token_hash),
            KEY backup_id (backup_id),
            KEY status (status),
            KEY delete_after (delete_after)
            )";

        if (!empty($collate)) {
            $sql .= " COLLATE {$collate}";
        }

        return $sql;
    }

 
    public function invalidateCache()
    {
    }
}
