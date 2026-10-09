<?php

namespace WPStaging\Framework\Job\Traits;

use Exception;
use RuntimeException;
use Throwable;
use WPStaging\Framework\Database\Exporter\AbstractExporter;
use WPStaging\Framework\Job\Dto\Task\RowsExporterTaskDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Utils\Times;





trait DatabaseRowsExportTaskTrait
{
 
    private $secondsBetweenRowsExportCheckpointWrites = 5;

 
    private $rowsExportCheckpointWrittenAt = null;




    protected function exportDatabaseRows(): TaskResponseDto
    {
        $this->resumeFromLastRowsExportCheckpoint();
        if ($this->stepsDto->isFinished()) {
            return $this->generateResponse(false);
        }

        do {
            $this->rowsExporter->setTableIndex($this->stepsDto->getCurrent());
            if (!$this->rowsExporter->initiate()) {
                $this->stepsDto->incrementCurrentStep();
                $this->currentTaskDto->reset();
                $this->currentTaskDto->sqlWrittenBytesStep = $this->stepsDto->getCurrent();
                $this->persistStepsDto();
                $this->setCurrentTaskDto($this->currentTaskDto);
                continue;
            }

            try {
                $this->rowsExporter->export();
            } catch (Throwable $exception) {
                $this->rowsExporter->unlockTables();
                throw $exception instanceof Exception ? $exception : new RuntimeException($exception->getMessage(), 0, $exception);
            }

            $writtenBytes = $this->measureWrittenBytesOfDump();
            $exporterDto  = $this->rowsExporter->getRowsExporterDto();
            $this->currentTaskDto->fromRowExporterDto($exporterDto);

            $srcTable = $this->rowsExporter->getTableBeingExported();
            $this->logger->info(sprintf(
                'Preparing table %s: %s of %s records.',
                $srcTable,
                number_format_i18n($this->currentTaskDto->rowsOffset),
                number_format_i18n($this->currentTaskDto->totalRows)
            ));

            $this->logger->debug(sprintf(
                'Preparing table %s: Query time: %s. Batch Size: %s. Last query json: %s.',
                $srcTable,
                Times::formatQueryTime($this->jobDataDto->getDbRequestTime()),
                $this->jobDataDto->getBatchSize(),
                $this->jobDataDto->getLastQueryInfoJSON()
            ));

            if ($exporterDto->isFinished()) {
                $this->stepsDto->incrementCurrentStep();
                $this->currentTaskDto->reset();
                $this->jobDataDto->setTableAverageRowLength(0);
            }

            $this->commitRowsExportCheckpoint($writtenBytes);
        } while (!$this->stepsDto->isFinished() && !$this->isThreshold());

        return $this->generateResponse(false);
    }








    private function resumeFromLastRowsExportCheckpoint()
    {
        $writtenBytes = $this->measureWrittenBytesOfDump();
        $checkpoint   = $this->currentTaskDto->sqlWrittenBytes;
        if ($checkpoint === null) {
            $this->commitRowsExportCheckpoint($writtenBytes);
            return;
        }

        $this->stepsDto->setCurrent($this->currentTaskDto->sqlWrittenBytesStep);
        $result = $this->rowsExporter->truncateTo($checkpoint);

        if ($result === AbstractExporter::TRUNCATE_FAILED) {
            throw new RuntimeException('Preparing database records: Could not rewind the database dump to the last exported row, so resuming would duplicate or lose rows.');
        }

        if ($result === AbstractExporter::TRUNCATE_NOT_NEEDED) {
            return;
        }

        $this->logger->info(sprintf(
            'Preparing database records: Discarded %s of rows from a request that did not finish, and will export them again.',
            size_format($writtenBytes - $checkpoint)
        ));
    }





    private function measureWrittenBytesOfDump(): int
    {
        $writtenBytes = $this->rowsExporter->getWrittenBytes();
        if ($writtenBytes === AbstractExporter::BYTES_UNKNOWN) {
            throw new RuntimeException('Preparing database records: Could not measure the database dump.');
        }

        return $writtenBytes;
    }








    private function commitRowsExportCheckpoint(int $writtenBytes)
    {
        $this->currentTaskDto->sqlWrittenBytes     = $writtenBytes;
        $this->currentTaskDto->sqlWrittenBytesStep = $this->stepsDto->getCurrent();
        $this->setCurrentTaskDto($this->currentTaskDto);
        if ($this->isRowsExportCheckpointDueForJobCache()) {
            $this->persistJobDataDto();
            $this->rowsExportCheckpointWrittenAt = microtime(true);
        }

        $this->persistStepsDto();
    }

 
    private function isRowsExportCheckpointDueForJobCache(): bool
    {
        if ($this->rowsExportCheckpointWrittenAt === null) {
            return true;
        }

        return microtime(true) - $this->rowsExportCheckpointWrittenAt >= $this->secondsBetweenRowsExportCheckpointWrites;
    }

 
    protected function getCurrentTaskType(): string
    {
        return RowsExporterTaskDto::class;
    }




    protected function setupRowsExporterForStagingTables()
    {
        $tables = $this->jobDataDto->getStagingTables();
        $this->rowsExporter->setStagingPrefix($this->jobDataDto->getDatabasePrefix());
        $this->rowsExporter->inject($this->logger, $this->jobDataDto, $this->currentTaskDto->toRowsExporterDto());
        $this->rowsExporter->setFileName($this->directory->getCacheDirectory() . $this->jobDataDto->getId() . '.wpstgdbtmp.sql');
        $this->rowsExporter->setTables($tables);
        $this->rowsExporter->setTablesToExclude($this->getTablesExcludedFromRowsExport());
        $this->rowsExporter->prefixSpecialFields();
        if (!$this->stepsDto->getTotal()) {
            $this->stepsDto->setCurrent(0);
            $this->stepsDto->setTotal(count($tables));
        }
    }






    private function getTablesExcludedFromRowsExport(): array
    {
        $rowsExporter = $this->rowsExporter;

        return array_merge(
            $this->jobDataDto->getExcludedTables(),
            apply_filters($rowsExporter::FILTER_EXCLUDE_TABLES_DATA, $rowsExporter::TABLES_EXCLUDED_FROM_DATA_COPYING)
        );
    }
}
