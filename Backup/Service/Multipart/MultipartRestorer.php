<?php

namespace WPStaging\Backup\Service\Multipart;

use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Backup\Service\Database\Importer\DatabaseSearchReplacerInterface;
use WPStaging\Backup\Service\Extractor;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

class MultipartRestorer implements MultipartRestoreInterface
{
    public function prepareExtraction(JobRestoreDataDto $jobDataDto, LoggerInterface $logger, StepsDto $stepsDto, Extractor $extractorService)
    {
        // no-op
    }

    public function setNextExtractedFile(JobRestoreDataDto $jobDataDto, LoggerInterface $logger)
    {
        // no-op
    }

    public function prepareDatabaseRestore(JobRestoreDataDto $jobDataDto, LoggerInterface $logger, DatabaseImporter $databaseRestore, StepsDto $stepsDto, DatabaseSearchReplacerInterface $databaseSearchReplacer, $backupsDirectory)
    {
        // no-op
    }
}
