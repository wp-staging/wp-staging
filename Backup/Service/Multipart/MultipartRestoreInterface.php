<?php

namespace WPStaging\Backup\Service\Multipart;

use WPStaging\Backup\Dto\Job\JobRestoreDataDto;
use WPStaging\Backup\Dto\StepsDto;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Backup\Service\Database\Importer\DatabaseSearchReplacerInterface;
use WPStaging\Backup\Service\Extractor;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

interface MultipartRestoreInterface
{
    public function prepareExtraction(JobRestoreDataDto $jobDataDto, LoggerInterface $logger, StepsDto $stepsDto, Extractor $extractorService);

    public function setNextExtractedFile(JobRestoreDataDto $jobDataDto, LoggerInterface $logger);

    public function prepareDatabaseRestore(JobRestoreDataDto $jobDataDto, LoggerInterface $logger, DatabaseImporter $databaseRestore, StepsDto $stepsDto, DatabaseSearchReplacerInterface $databaseSearchReplacer, $backupsDirectory);
}
