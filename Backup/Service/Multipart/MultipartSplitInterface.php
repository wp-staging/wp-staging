<?php

namespace WPStaging\Backup\Service\Multipart;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Compressor;

interface MultipartSplitInterface
{
    public function setBackupPartInfo(JobBackupDataDto $jobDataDto, Compressor $compressor);

    public function setupCompressor(JobBackupDataDto $jobDataDto, Compressor $compressor, $identifier, $stepsSet);

    public function maybeIncrementBackupFileIndex(JobBackupDataDto $jobDataDto, Compressor $compressor, $identifier, $path);

    public function updateMultipartMetadata(JobBackupDataDto $jobDataDto, BackupMetadata $backupMetadata, $category, $categoryIndex);

    public function incrementFileCountInPart(JobBackupDataDto $jobDataDto, $category, $categoryIndex);

    public function setupDatabaseFilename(JobBackupDataDto $jobDataDto, $wpdb, $cacheDirectory, $partFilename);
}
