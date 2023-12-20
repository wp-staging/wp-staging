<?php

namespace WPStaging\Basic\Backup;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Dto\JobDataDto;
use WPStaging\Backup\Job\AbstractJob;
use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Backup\Job\Jobs\JobBackup;
use WPStaging\Backup\Job\Jobs\JobRestore;
use WPStaging\Backup\Service\Database\Exporter\AbstractExporter;
use WPStaging\Backup\Service\Database\Exporter\DDLExporter;
use WPStaging\Backup\Service\Database\Exporter\DDLExporterProvider;
use WPStaging\Backup\Service\Database\Exporter\RowsExporter;
use WPStaging\Backup\Service\Database\Exporter\RowsExporterProvider;
use WPStaging\Backup\Service\Database\Importer\BasicDatabaseSearchReplacer;
use WPStaging\Backup\Service\Database\Importer\DatabaseSearchReplacerInterface;
use WPStaging\Backup\Service\Multipart\MultipartInjection;
use WPStaging\Backup\Service\Multipart\MultipartRestoreInterface;
use WPStaging\Backup\Service\Multipart\MultipartRestorer;
use WPStaging\Backup\Service\Multipart\MultipartSplitInterface;
use WPStaging\Backup\Service\Multipart\MultipartSplitter;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask;
use WPStaging\Framework\DI\ServiceProvider;

/**
 * Class BackupServiceProvider
 *
 * Responsible for injecting classes which are to be used in FREE/BASIC version only
 */
class BackupServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->when(JobBackup::class)
                ->needs(JobDataDto::class)
                ->give(JobBackupDataDto::class);

        $this->container->when(JobRestore::class)
                ->needs(JobDataDto::class)
                ->give(JobRestoreDataDto::class);

        $container = $this->container;

        $this->container->when(JobBackupProvider::class)
                ->needs(AbstractJob::class)
                ->give(function () use (&$container) {
                    return $container->make(JobBackup::class);
                });

        $this->container->when(JobRestoreProvider::class)
                ->needs(AbstractJob::class)
                ->give(function () use (&$container) {
                    return $container->make(JobRestore::class);
                });

        $this->container->when(DDLExporterProvider::class)
                ->needs(AbstractExporter::class)
                ->give(function () use (&$container) {
                    return $container->make(DDLExporter::class);
                });

        $this->container->when(RowsExporterProvider::class)
                ->needs(AbstractExporter::class)
                ->give(function () use (&$container) {
                    return $container->make(RowsExporter::class);
                });

        foreach (MultipartInjection::MULTIPART_CLASSES as $classId) {
            $this->container->when($classId)
                    ->needs(MultipartSplitInterface::class)
                    ->give(MultipartSplitter::class);
        }

        foreach (MultipartInjection::RESTORE_CLASSES as $classId) {
            $this->container->when($classId)
                    ->needs(MultipartRestoreInterface::class)
                    ->give(MultipartRestorer::class);
        }

        $this->container->when(RestoreDatabaseTask::class)
                ->needs(DatabaseSearchReplacerInterface::class)
                ->give(BasicDatabaseSearchReplacer::class);
    }
}
