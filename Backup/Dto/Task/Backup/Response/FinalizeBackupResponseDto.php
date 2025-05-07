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
    private $isLocalBackup = true;

    /** @var bool */
    private $isMultipartBackup = false;

    /** @var bool */
    private $isGlitchInBackup = false;

    /** @var string */
    private $glitchReason = '';

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

    /**
     * @param bool $isGlitchInBackup
     * @return void
     */
    public function setIsGlitchInBackup(bool $isGlitchInBackup)
    {
        $this->isGlitchInBackup = $isGlitchInBackup;
    }

    public function getIsGlitchInBackup(): bool
    {
        return $this->isGlitchInBackup;
    }

    /**
     * @param string $glitchReason
     * @return void
     */
    public function setGlitchReason(string $glitchReason)
    {
        $this->glitchReason = $glitchReason;
    }

    public function getGlitchReason(): string
    {
        return $this->glitchReason;
    }
}
