<?php

namespace WPStaging\Basic\Backup;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Job\AbstractJob;
use WPStaging\Backup\Job\JobBackupProvider;
use WPStaging\Backup\Job\JobRestoreProvider;
use WPStaging\Backup\Job\Jobs\JobBackup;
use WPStaging\Backup\Job\Jobs\JobRestore;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Backup\Service\Compression\CompressionInterface;
use WPStaging\Backup\Service\Compression\NonCompressionService;
use WPStaging\Backup\Service\Database\Exporter\AbstractExporter;
use WPStaging\Backup\Service\Database\Exporter\DDLExporter;
use WPStaging\Backup\Service\Database\Exporter\DDLExporterProvider;
use WPStaging\Backup\Service\Database\Exporter\RowsExporter;
use WPStaging\Backup\Service\Database\Exporter\RowsExporterProvider;
use WPStaging\Backup\Service\Database\Importer\BasicDatabaseSearchReplacer;
use WPStaging\Backup\Service\Database\Importer\BasicSubsiteManager;
use WPStaging\Backup\Service\Database\Importer\DatabaseSearchReplacerInterface;
use WPStaging\Backup\Service\Database\Importer\SubsiteManagerInterface;
use WPStaging\Backup\Service\FileBackupService;
use WPStaging\Backup\Service\FileBackupServiceProvider;
use WPStaging\Backup\Service\ServiceInterface;
use WPStaging\Backup\Service\ZlibCompressor;
use WPStaging\Backup\Task\Tasks\JobRestore\RestoreDatabaseTask;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Backup\Ajax\FileList;
use WPStaging\Backup\Ajax\Listing;

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

        $this->container->when(ZlibCompressor::class)
                ->needs(CompressionInterface::class)
                ->give(NonCompressionService::class);

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

        $this->container->when(FileBackupServiceProvider::class)
                ->needs(ServiceInterface::class)
                ->give(function () use (&$container) {
                    return $container->make(FileBackupService::class);
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

        $this->container->when(RestoreDatabaseTask::class)
                ->needs(DatabaseSearchReplacerInterface::class)
                ->give(BasicDatabaseSearchReplacer::class);

        $this->container->when(DatabaseImporter::class)
                ->needs(SubsiteManagerInterface::class)
                ->give(BasicSubsiteManager::class);
    }

    protected function addHooks()
    {
        add_action('wp_ajax_wpstg--backups--listing', $this->container->callback(Listing::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--backups--restore--file-list', $this->container->callback(FileList::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
