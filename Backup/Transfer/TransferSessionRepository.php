<?php

namespace WPStaging\Backup\Transfer;

use wpdb;




class TransferSessionRepository
{
 
    const STALE_PREPARING_SECONDS = 2 * HOUR_IN_SECONDS;

 
    private $table;

    public function __construct(TransferSessionTable $table)
    {
        $this->table = $table;
    }

 
    public function insert(TransferSession $session): int
    {
        $wpdb = $this->getWpdbWithTable();
        if ($wpdb === null) {
            return 0;
        }

        $now = current_time('mysql', true);

        $session->createdAt = $now;
        $session->updatedAt = $now;

        $inserted = $wpdb->insert($this->table->getFullTableName(), $this->toStorableRow($session->toRow()));

        if ($inserted === false) {
            return 0;
        }

        $session->id = (int)$wpdb->insert_id;

        return $session->id;
    }

    public function update(TransferSession $session): bool
    {
        if ($session->id === 0) {
            return false;
        }

        $wpdb = $this->getWpdbWithTable();
        if ($wpdb === null) {
            return false;
        }

        $session->updatedAt = current_time('mysql', true);

        $updated = $wpdb->update($this->table->getFullTableName(), $this->toStorableRow($session->toRow()), ['id' => $session->id]);

        return $updated !== false;
    }

 
    public function findById(int $id)
    {
        $sessions = $this->selectSessions('id = %d LIMIT 1', $id);

        return $sessions[0] ?? null;
    }

 
    public function findActiveByBackupId(string $backupId): array
    {
        return $this->selectSessions(
            'backup_id = %s AND status IN (%s, %s) ORDER BY id DESC',
            $backupId,
            TransferSessionStatus::PREPARING,
            TransferSessionStatus::READY
        );
    }

 
    public function findBySourcePath(string $sourcePath): array
    {
        return $this->selectSessions('source_path = %s ORDER BY id DESC', $sourcePath);
    }







    public function findDueForCleanup(int $limit = 50, int $maxAttempts = 5): array
    {
        return $this->selectSessions(
            "transfer_dir IS NOT NULL AND transfer_dir != ''
               AND cleanup_attempts < %d
               AND (
                    (status IN (%s, %s) AND delete_after IS NOT NULL AND delete_after <= %s)
                 OR (status IN (%s, %s, %s))
                 OR (status = %s AND updated_at IS NOT NULL AND updated_at <= %s)
               )
             ORDER BY cleanup_attempts ASC, id ASC
             LIMIT %d",
            $maxAttempts,
            TransferSessionStatus::PREPARING,
            TransferSessionStatus::READY,
            current_time('mysql', true),
            TransferSessionStatus::REVOKED,
            TransferSessionStatus::COMPLETED,
            TransferSessionStatus::FAILED,
            TransferSessionStatus::PREPARING,
            $this->getPastDateTime(self::STALE_PREPARING_SECONDS),
            $limit
        );
    }





    public function findWithArtifact(int $blogId = 0): array
    {
        $condition = "transfer_dir IS NOT NULL AND transfer_dir != ''";

        if ($blogId > 0) {
            return $this->selectSessions($condition . ' AND blog_id = %d', $blogId);
        }

        return $this->selectSessions($condition);
    }






    public function getKnownTransferDirectoryNames(int $maxAttempts = 5): array
    {
        $wpdb = $this->getWpdbWithTable();
        if ($wpdb === null) {
            return [];
        }

        $tableName = $this->table->getFullTableName();

        $paths = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT transfer_dir FROM `{$tableName}` WHERE status IN (%s, %s) AND cleanup_attempts < %d",
                TransferSessionStatus::PREPARING,
                TransferSessionStatus::READY,
                $maxAttempts
            )
        );

        $names = [];
        foreach ((array)$paths as $path) {
            $path = TransferSession::fromRow(['transfer_dir' => $path])->transferDir;
            if ($path === '') {
                continue;
            }

            $names[] = basename(untrailingslashit(wp_normalize_path($path)));
        }

        return $names;
    }

 
    public function purgeTerminalRows(int $olderThanSeconds): int
    {
        $wpdb = $this->getWpdbWithTable();
        if ($wpdb === null) {
            return 0;
        }

        $tableName = $this->table->getFullTableName();
        $cutoff    = $this->getPastDateTime($olderThanSeconds);

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$tableName}` WHERE status IN (%s, %s, %s, %s) AND updated_at IS NOT NULL AND updated_at <= %s",
                TransferSessionStatus::EXPIRED,
                TransferSessionStatus::REVOKED,
                TransferSessionStatus::COMPLETED,
                TransferSessionStatus::FAILED,
                $cutoff
            )
        );

        return $deleted === false ? 0 : (int)$deleted;
    }

 
    private function getPastDateTime(int $secondsAgo): string
    {
        return gmdate('Y-m-d H:i:s', time() - $secondsAgo);
    }






    private function selectSessions(string $condition, ...$args): array
    {
        $wpdb = $this->getWpdbWithTable();
        if ($wpdb === null) {
            return [];
        }

        $query = "SELECT * FROM `{$this->table->getFullTableName()}` WHERE {$condition}";

        if (!empty($args)) {
            $query = $wpdb->prepare($query, $args);
        }

        $sessions = [];
        foreach ((array)$wpdb->get_results($query, ARRAY_A) as $row) {
            $sessions[] = TransferSession::fromRow((array)$row);
        }

        return $sessions;
    }







    private function toStorableRow(array $row): array
    {
        $dateColumns = ['expires_at', 'delete_after', 'created_at', 'updated_at', 'last_accessed_at', 'completed_at', 'revoked_at'];

        foreach ($dateColumns as $column) {
            if (isset($row[$column]) && $row[$column] === '') {
                $row[$column] = null;
            }
        }

        return $row;
    }

 
    private function getWpdbWithTable()
    {
        global $wpdb;

        if (!$wpdb instanceof wpdb) {
            return null;
        }

        $this->table->ensureTable();

        return $wpdb;
    }
}
