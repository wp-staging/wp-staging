<?php

namespace WPStaging\Backup\Service\Multipart;

use WPStaging\Backup\Service\Compressor;
use WPStaging\Backup\Task\Tasks\JobBackup\DatabaseBackupTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupMuPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupOtherFilesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupPluginsTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupThemesTask;
use WPStaging\Backup\Task\Tasks\JobBackup\BackupUploadsTask;
use WPStaging\Backup\Task\Tasks\JobRestore\ExtractFilesTask;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask;

class MultipartInjection
{
    const MULTIPART_CLASSES = [
        BackupMuPluginsTask::class,
        BackupOtherFilesTask::class,
        BackupPluginsTask::class,
        BackupThemesTask::class,
        BackupUploadsTask::class,
        Compressor::class,
        DatabaseBackupTask::class,
    ];

    const RESTORE_CLASSES = [
        ExtractFilesTask::class,
        RestoreDatabaseTask::class,
    ];
}
