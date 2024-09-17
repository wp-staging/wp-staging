<?php

/**
 * @noinspection PhpPropertyOnlyWrittenInspection
 * @see          \WPStaging\Framework\Traits\ArrayableTrait::toArray
 */

namespace WPStaging\Backup\Dto\Task\Backup\Response;

use WPStaging\Framework\Job\Dto\TaskResponseDto;

class FinalizeBackupResponseDto extends TaskResponseDto
{
    /**
     * Return md5 hash as string if not multipart backup, otherwise return array of md5 for each multipart backup part
     * @var array|string|null
     */
    private $backupMd5;

    /** @var int|null */
    private $backupSize;

    /** @var bool */
    private $isLocalBackup;

    /** @var bool */
    private $isMultipartBackup;

    /**
     * @param array|string|null $backupMd5
     */
    public function setBackupMd5($backupMd5)
    {
        $this->backupMd5 = $backupMd5;
    }

    /**
     * @param int|null $backupSize
     */
    public function setBackupSize($backupSize)
    {
        $this->backupSize = $backupSize;
    }

    /**
     * Returns the Backup MD5 string.
     *
     * @return array|string|null
     */
    public function getBackupMd5()
    {
        return $this->backupMd5;
    }

    /**
     * @param bool $isLocalBackup
     */
    public function setIsLocalBackup($isLocalBackup)
    {
        $this->isLocalBackup = $isLocalBackup;
    }

    /**
     * @return bool
     */
    public function getIsLocalBackup()
    {
        return $this->isLocalBackup;
    }

    /**
     * @param bool $isMultipartBackup
     */
    public function setIsMultipartBackup($isMultipartBackup)
    {
        $this->isMultipartBackup = $isMultipartBackup;
    }

    /**
     * @return bool
     */
    public function getIsMultipartBackup()
    {
        return $this->isMultipartBackup;
    }
}
