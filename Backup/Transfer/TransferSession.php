<?php

namespace WPStaging\Backup\Transfer;

use ReflectionClass;
use ReflectionProperty;
use WPStaging\Framework\Facades\DataEncryption;

use function WPStaging\functions\debug_log;





class TransferSession
{
 
    public $id = 0;

 
    public $backupId = '';

 
    public $sourcePath = '';

 
    public $publicTokenHash = '';

 
    public $publicTokenPrefix = '';

 
    public $transferDir = '';

 
    public $transferFilePath = '';

 
    public $transferUrl = '';

 
    public $artifactMode = '';

 
    public $status = TransferSessionStatus::PREPARING;

 
    public $fileSize = 0;

 
    public $expiresAt = '';

 
    public $deleteAfter = '';

 
    public $createdAt = '';

 
    public $updatedAt = '';

 
    public $createdBy = 0;

 
    public $blogId = 0;

 
    public $downloadStartedCount = 0;

 
    public $cleanupAttempts = 0;

 
    public $lastAccessedAt = '';

 
    public $completedAt = '';

 
    public $revokedAt = '';

 
    public $errorMessage = '';

 
    const ENCRYPTED_PROPERTIES = ['transferDir', 'transferFilePath', 'transferUrl'];

 
    const ARTIFACT_PATH_PROPERTIES = ['transferDir', 'transferFilePath'];

 
    private static $columnMap;

 
    private $hasUnreadableSecrets = false;

 
    private $hasUnreadableArtifactPaths = false;

 
    public static function fromRow(array $row): self
    {
        $session = new self();

        $unreadableColumns = [];

        foreach (self::getColumnMap() as $property => $column) {
            if (!array_key_exists($column, $row)) {
                continue;
            }

            $value = $row[$column];

            if (is_int($session->{$property})) {
                $session->{$property} = (int)$value;
                continue;
            }

            $value                = $value === null ? '' : (string)$value;
            $session->{$property} = self::decryptIfNeeded($property, $value);

            if ($value !== '' && $session->{$property} === '') {
                $unreadableColumns[] = $column;

                if (in_array($property, self::ARTIFACT_PATH_PROPERTIES, true)) {
                    $session->hasUnreadableArtifactPaths = true;
                }
            }
        }

        if (empty($unreadableColumns)) {
            return $session;
        }

        $session->hasUnreadableSecrets = true;

        if ($session->hasUnreadableArtifactPaths && $session->id !== 0) {
            debug_log(sprintf(
                'WP STAGING: Transfer session #%d was encrypted with WordPress security keys this site no longer has, so %s could not be read. Its backup is held back until the sweep has removed every folder no readable session claims.',
                $session->id,
                implode(', ', $unreadableColumns)
            ));
        }

        return $session;
    }

 
    public function hasUnreadableSecrets(): bool
    {
        return $this->hasUnreadableSecrets;
    }





    public function hasUnreadableArtifactPaths(): bool
    {
        return $this->hasUnreadableArtifactPaths;
    }

 
    private static function decryptIfNeeded(string $property, string $value): string
    {
        if ($value === '' || !in_array($property, self::ENCRYPTED_PROPERTIES, true)) {
            return $value;
        }

        if (!DataEncryption::isEncrypted($value)) {
            return $value;
        }

        $decrypted = DataEncryption::decrypt($value);

        return DataEncryption::isEncrypted($decrypted) ? '' : $decrypted;
    }







    public function toRow(): array
    {
        $row = [];

        foreach (self::getColumnMap() as $property => $column) {
            if ($property === 'id') {
                continue;
            }

            if ($this->hasUnreadableSecrets && in_array($property, self::ENCRYPTED_PROPERTIES, true)) {
                continue;
            }

            $row[$column] = $this->encryptIfNeeded($property, $this->{$property});
        }

        return $row;
    }





    private function encryptIfNeeded(string $property, $value)
    {
        if ($value === '' || !in_array($property, self::ENCRYPTED_PROPERTIES, true)) {
            return $value;
        }

        return DataEncryption::encrypt((string)$value);
    }

 
    public function isReady(): bool
    {
        return $this->status === TransferSessionStatus::READY && $this->transferUrl !== '';
    }

 
    public function isExpired(int $now): bool
    {
        return $this->expiresAt !== '' && strtotime($this->expiresAt . ' UTC') <= $now;
    }







    public function toResponse(): array
    {
        return [
            'sessionId'          => $this->id,
            'transferUrl'        => $this->transferUrl,
            'fileName'           => $this->sourcePath === '' ? '' : basename($this->sourcePath),
            'artifactMode'       => $this->artifactMode,
            'artifactModeLabel'  => TransferArtifactMode::getLabel($this->artifactMode),
            'expiresAt'          => $this->expiresAt,
            'expiresAtFormatted' => $this->expiresAt === '' ? '' : get_date_from_gmt($this->expiresAt, get_option('date_format') . ' ' . get_option('time_format')),
        ];
    }

 
    private static function getColumnMap(): array
    {
        if (self::$columnMap !== null) {
            return self::$columnMap;
        }

        self::$columnMap = [];
        foreach ((new ReflectionClass(self::class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            self::$columnMap[$property->getName()] = strtolower((string)preg_replace('/[A-Z]/', '_$0', $property->getName()));
        }

        return self::$columnMap;
    }
}
