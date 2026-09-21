<?php

namespace WPStaging\Framework\Job\Traits;

use Exception;
use RuntimeException;
use WPStaging\Backup\Dto\Service\DatabaseImporterDto;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Database\SearchReplace;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Vendor\Psr\Log\LoggerInterface;




trait DatabaseDumpImportTaskTrait
{
    use DatabaseImportTaskTrait;

 
    protected $databaseImporter;

 
    protected $databaseImporterDto;

 
    protected $directory;

    public function __construct(Directory $directory, LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, DatabaseImporter $databaseImporter)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->databaseImporter    = $databaseImporter;
        $this->directory           = $directory;
        $this->databaseImporterDto = new DatabaseImporterDto();
    }








    protected function importDatabaseDumpForThisRequest($tablePrefix): TaskResponseDto
    {
        $this->setupDatabaseImporterForDump($tablePrefix);

        $startedAt       = microtime(true);
        $queriesExecuted = $this->stepsDto->getCurrent();
        $totalQueries    = $this->stepsDto->getTotal();

        $this->stopWhenDatabaseDumpHasNoQueries($totalQueries);
        $this->setupExecutionTime();
        $this->importDatabaseDump($tablePrefix);
        $this->stepsDto->setCurrent($this->databaseImporterDto->getCurrentIndex());
        $this->logImportSpeedAndAdjustExecutionTime($queriesExecuted, $totalQueries, $startedAt);

        return $this->generateResponse(false);
    }






    private function setupDatabaseImporterForDump($tablePrefix)
    {
        $this->databaseImporterDto->setTmpPrefix($tablePrefix);

        $this->databaseImporter->setup($this->databaseImporterDto, true, "");
        $databaseFile = $this->directory->getCacheDirectory() . $this->jobDataDto->getId() . '.wpstgdbtmp.sql';
        $this->requireNonEmptyDatabaseFile($databaseFile);

        $this->databaseImporter->setWarningLogCallable([$this->logger, 'warning']);
        $this->databaseImporter->setNoticeLogCallable([$this->logger, 'notice']);
        $this->databaseImporter->setFile($databaseFile);
        $this->databaseImporter->seekLine($this->stepsDto->getCurrent());

        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setTotal($this->databaseImporter->getTotalLines());
        }

        $this->databaseImporterDto->setTotalLines($this->databaseImporter->getTotalLines());

        $this->databaseImporter->setSearchReplace(new SearchReplace());
    }





    private function importDatabaseDump($tablePrefix)
    {
        $this->databaseImporter->init($tablePrefix);

        try {
            while (!$this->isThreshold()) {
                $this->executeNextDatabaseImporterQuery();
            }
        } catch (Exception $exception) {
            $this->handleDatabaseImporterStop($exception);
            return;
        }

        $this->databaseImporter->updateIndex();
    }
}
