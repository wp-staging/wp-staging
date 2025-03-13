<?php

/**
 * Provides method to dynamic calculate the batch size for the table
 * depending upon its average row length.
 *
 * @package WPStaging\Framework\Traits
 */

namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Adapter\Database\MysqliAdapter;
use WPStaging\Framework\Adapter\Database\SqliteAdapter;
use WPStaging\Framework\Job\Dto\JobDataDto;

/**
 * Trait BatchSizeCalculateTrait
 *
 * @package WPStaging\Framework\Traits
 */
trait BatchSizeCalculateTrait
{
    /**
     * @param string $databaseName
     * @param string $table
     * @param int $offset
     * @param string $requestId
     * @param JobDataDto $jobDataDto
     * @param InterfaceDatabaseClient|MysqliAdapter|SqliteAdapter $db
     * @return float|int
     */
    protected function calculateBatchSize(string $databaseName, string $table, int &$offset, string $requestId, JobDataDto $jobDataDto, $db)
    {
        $batchSize = null;

        $freeMemory = $this->getScriptMemoryLimit() - $this->getMemoryUsage();

        // Fetch the average row length of the current table, if need be. This is to get the maximum possible batch size
        if (empty($jobDataDto->getTableAverageRowLength())) {
            if (isset($db->isSQLite) && $db->isSQLite) {
                $averageRowLength = $db->getAverageRowLengthSQLite($table); // @phpstan-ignore-line
            } else {
                $averageRowLength = $db->query("SELECT AVG_ROW_LENGTH FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '$databaseName';")->fetch_assoc();
            }
            if (!empty($averageRowLength) && is_array($averageRowLength) && array_key_exists('AVG_ROW_LENGTH', $averageRowLength)) {
                $jobDataDto->setTableAverageRowLength(max(absint($averageRowLength['AVG_ROW_LENGTH']), 1));

                // @phpstan-ignore-next-line
                $batchSize = ($freeMemory / $jobDataDto->getTableAverageRowLength()) / 4;
            }
        } else {
            $batchSize = ($freeMemory / $jobDataDto->getTableAverageRowLength()) / 4;
        }

        // This can happen if we can't fetch the AVG_ROW_LENGTH from the table
        if ($batchSize === null) {
            $batchSize = 5000;
        }

        // Lower the fetch limits if we couldn't store them in memory last time
        if (!empty($jobDataDto->getLastQueryInfoJSON())) {
            $lastQueryInfo = json_decode($jobDataDto->getLastQueryInfoJSON(), true);
            if (count($lastQueryInfo) === 4) {
                $previousRequestId = $lastQueryInfo[0];
                if ($previousRequestId === $requestId) {
                    list($requestId, $table, $oldOffset, $batchSize) = array_replace([$requestId, $table, $offset, $batchSize], $lastQueryInfo);

                    if ($batchSize <= 1000) {
                        $batchSize = $batchSize / 2;
                    } else {
                        $batchSize = $batchSize / 3;
                    }

                    if ((!$this->useMemoryExhaustFix) || ($offset > $oldOffset)) {
                        $offset = $oldOffset;
                    }

                    /* Kept for debugging purpose
                    if ($batchSize < 1) {
                        throw new \RuntimeException(sprintf(
                        'There is one row in the database that is bigger than the memory available to PHP, which makes it impossible to extract using PHP. More info: Maximum PHP Memory: %s | Row is in table: %s | Offset: %s',
                        size_format($this->getScriptMemoryLimit()),
                        $table,
                        $offset
                        ));
                    }*/
                }
            }
        }

        // At least 1, max 5k, integer
        $maxBatchSize = $jobDataDto->getIsSlowMySqlServer() ? 100 : 5000;
        $minBatchSize = 1;
        $batchSize    = max($minBatchSize, $batchSize);
        $batchSize    = min($maxBatchSize, $batchSize);
        $batchSize    = ceil($batchSize);

        $jobDataDto->setBatchSize($batchSize);

        return $batchSize;
    }
}
