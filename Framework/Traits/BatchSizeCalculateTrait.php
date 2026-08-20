<?php








namespace WPStaging\Framework\Traits;

use WPStaging\Framework\Adapter\Database\InterfaceDatabaseClient;
use WPStaging\Framework\Adapter\Database\MysqliAdapter;
use WPStaging\Framework\Adapter\Database\SqliteAdapter;
use WPStaging\Framework\Job\Dto\JobDataDto;






trait BatchSizeCalculateTrait
{









    protected function calculateBatchSize(string $databaseName, string $table, int &$offset, string $requestId, JobDataDto $jobDataDto, $db)
    {
        $batchSize = null;

        $freeMemory = $this->getScriptMemoryLimit() - $this->getMemoryUsage();

 
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

 
        if ($batchSize === null) {
            $batchSize = 5000;
        }

 
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










                }
            }
        }

 
        $maxBatchSize = $jobDataDto->getIsSlowMySqlServer() ? 100 : 5000;
        $minBatchSize = 1;
        $batchSize    = max($minBatchSize, $batchSize);
        $batchSize    = min($maxBatchSize, $batchSize);
        $batchSize    = ceil($batchSize);

        $jobDataDto->setBatchSize((int)$batchSize);

        return $batchSize;
    }
}
