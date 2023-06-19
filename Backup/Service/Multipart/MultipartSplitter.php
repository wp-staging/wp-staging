<?php

namespace WPStaging\Backup\Service\Multipart;

use WPStaging\Backup\Dto\Job\JobBackupDataDto;
use WPStaging\Backup\Entity\BackupMetadata;
use WPStaging\Backup\Service\Compressor;

class MultipartSplitter implements MultipartSplitInterface
{
    public function setBackupPartInfo(JobBackupDataDto $jobDataDto, Compressor $compressor)
    {
        // no-op
    }

    public function setupCompressor(JobBackupDataDto $jobDataDto, Compressor $compressor, $identifier, $stepsSet)
    {
        // no-op
    }

    public function maybeIncrementBackupFileIndex(JobBackupDataDto $jobDataDto, Compressor $compressor, $identifier, $path)
    {
        // no-op
    }

    public function updateMultipartMetadata(JobBackupDataDto $jobDataDto, BackupMetadata $backupMetadata, $category, $categoryIndex)
    {
        // no-op
    }

    public function incrementFileCountInPart(JobBackupDataDto $jobDataDto, $category, $categoryIndex)
    {
        // no-op
    }

    public function setupDatabaseFilename(JobBackupDataDto $jobDataDto, $wpdb, $cacheDirectory, $partFilename)
    {
        // no-op
    }
}
