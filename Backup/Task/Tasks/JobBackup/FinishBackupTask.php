<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Backup\Task\Tasks\JobBackup;

use WPStaging\Framework\Analytics\Actions\AnalyticsBackupCreate;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Dto\TaskResponseDto;
use WPStaging\Backup\Dto\Task\Backup\Response\FinalizeBackupResponseDto;
use WPStaging\Backup\Entity\ListableBackup;
use WPStaging\Backup\Task\BackupTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Utils\Cache\TransientCache;

class FinishBackupTask extends BackupTask
{
    /** @var AnalyticsBackupCreate */
    protected $analyticsBackupCreate;

    /** @var TransientCache */
    protected $transientCache;

    const OPTION_LAST_BACKUP = 'wpstg_last_backup_info';

    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, AnalyticsBackupCreate $analyticsBackupCreate, TransientCache $transientCache)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);

        $this->analyticsBackupCreate = $analyticsBackupCreate;
        $this->transientCache        = $transientCache;
    }

    public static function getTaskName()
    {
        return 'backup_finish';
    }

    public static function getTaskTitle()
    {
        return 'Finalizing Backup';
    }

    public function execute()
    {
        $backupFilePath = $this->jobDataDto->getBackupFilePath();

        $this->analyticsBackupCreate->enqueueFinishEvent($this->jobDataDto->getId(), $this->jobDataDto);

        $this->saveBackupsInDB();

        $this->stepsDto->finish();

        $this->jobDataDto->setEndTime(time());

        update_option(static::OPTION_LAST_BACKUP, [
            'endTime'          => time(), // Unix timestamp is timezone independent
            'duration'         => $this->jobDataDto->getDuration(),
            'JobBackupDataDto' => $this->jobDataDto,
        ], false);

        // Delete the transient cache for the backup file index to make sure it is checked again now
        $this->transientCache->delete(TransientCache::KEY_INVALID_BACKUP_FILE_INDEX);

        return $this->overrideGenerateResponse($this->makeListableBackup($backupFilePath));
    }

    /**
     * @param null|ListableBackup $backup
     *
     * @return FinalizeBackupResponseDto|TaskResponseDto
     */
    private function overrideGenerateResponse(ListableBackup $backup = null)
    {
        add_filter('wpstg.task.response', function ($response) use ($backup) {

            $md5 = $backup ? $backup->md5BaseName : null;
            if ($this->jobDataDto->getIsMultipartBackup()) {
                $md5 = $this->getPartsMd5();
            }

            if ($response instanceof FinalizeBackupResponseDto) {
                $response->setBackupMd5($md5);
                $response->setBackupSize($backup ? size_format($backup->size) : null);
                $response->setIsLocalBackup($this->jobDataDto->isLocalBackup());
                $response->setIsMultipartBackup($this->jobDataDto->getIsMultipartBackup());
            }

            return $response;
        });

        return $this->generateResponse();
    }

    /**
     * Retains backups that if at least one remote storage is set.
     *
     * @return void
     */
    protected function saveBackupsInDB()
    {
        // Used in PRO version
    }

    protected function getResponseDto()
    {
        return new FinalizeBackupResponseDto();
    }

    /**
     * This is used to display the "Download Modal" after the backup completes.
     *
     * @param string $backupFilePath
     *
     * @return ListableBackup
     * @see string src/Backend/public/js/wpstg-admin.js, search for "wpstg--backups--backup"
     *
     */
    protected function makeListableBackup($backupFilePath)
    {
        clearstatcache();
        $backupFilePath      = (string)$backupFilePath;
        $backup              = new ListableBackup();
        $backup->md5BaseName = md5(basename($backupFilePath));
        $backup->size        = filesize($backupFilePath);

        return $backup;
    }

    protected function getPartsMd5()
    {
        $md5 = [];
        foreach ($this->jobDataDto->getMultipartFilesInfo() as $multipartInfo) {
            $md5[] = md5($multipartInfo['destination']);
        }

        return $md5;
    }
}
