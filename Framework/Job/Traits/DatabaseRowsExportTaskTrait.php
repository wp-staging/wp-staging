<?php

namespace WPStaging\Framework\Job\Traits;

use Throwable;
use WPStaging\Framework\Job\Dto\Task\RowsExporterTaskDto;
use WPStaging\Framework\Job\Dto\TaskResponseDto;
use WPStaging\Framework\Utils\Times;





trait DatabaseRowsExportTaskTrait
{



    protected function exportDatabaseRows(): TaskResponseDto
    {
        do {
            $this->rowsExporter->setTableIndex($this->stepsDto->getCurrent());
            if (!$this->rowsExporter->initiate()) {
                $this->stepsDto->incrementCurrentStep();
                $this->currentTaskDto->reset();
                $this->persistStepsDto();
                $this->setCurrentTaskDto($this->currentTaskDto);
                continue;
            }

            try {
                $this->rowsExporter->export();
            } catch (Throwable $exception) {
                $this->rowsExporter->unlockTables();
            }

            $exporterDto = $this->rowsExporter->getRowsExporterDto();
            $this->currentTaskDto->fromRowExporterDto($exporterDto);
            $this->setCurrentTaskDto($this->currentTaskDto);

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
                $this->setCurrentTaskDto($this->currentTaskDto);
                $this->persistStepsDto();
            }
        } while (!$this->stepsDto->isFinished() && !$this->isThreshold());

        return $this->generateResponse(false);
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
