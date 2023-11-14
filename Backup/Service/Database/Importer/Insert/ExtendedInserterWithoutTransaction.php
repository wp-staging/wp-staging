<?php

namespace WPStaging\Backup\Service\Database\Importer\Insert;

use function WPStaging\functions\debug_log;

/**
 * This class restores mysql extended queries with the --extended-inserts options. It does not use transactions.
 * Extended inserts combines multiple rows into one INSERT statement. This reduces the size of a SQL dump and improves the INSERT speed.
 */
class ExtendedInserterWithoutTransaction extends QueryInserter
{
    protected $extendedQuery = '';

    /**
     * @param string $queryToInsert
     *
     * @return bool True if a query was executed successfully.
     * @return bool False if a query was executed but failed.
     * @return null If no query was executed.
     */
    public function processQuery(&$queryToInsert)
    {
        if ($this->doQueryExceedsMaxAllowedPacket($queryToInsert)) {
            return null;
        }

        $this->extendInsert($queryToInsert);

        if (strlen($this->extendedQuery) >= $this->limitedMaxAllowedPacket) {
            return $this->execExtendedQuery();
        }

        return null;
    }

    /**
     * @return void
     */
    public function commit()
    {
        $this->execExtendedQuery();
    }

    /**
     * @return bool|null
     */
    public function execExtendedQuery()
    {
        if (empty($this->extendedQuery)) {
            return null;
        }

        $this->extendedQuery .= ';';

        $success = $this->exec($this->extendedQuery);

        if ($success) {
            $this->extendedQuery = '';
            $this->jobRestoreDataDto->setTableToRestore('');
            return true;
        } else {
            $this->showError();
            $this->extendedQuery = '';
            $this->jobRestoreDataDto->setTableToRestore('');
            return false;
        }
    }

    /**
     * @return void
     */
    protected function showError()
    {
        /**
         * @link https://mariadb.com/kb/en/mariadb-error-codes/
         */
        switch ($this->client->errno()) {
            case 1153:
            case 2006:
                $this->logger->warning(__('The error message means got a packet bigger than max_allowed_packet bytes.', 'wp-staging'));
                break;
            case 1030:
                $this->logger->warning(__('Engine changed to InnoDB, as it your MySQL server does not support MyISAM.', 'wp-staging'));
                break;
            case 1071:
            case 1709:
                $this->logger->warning(__('Row format changed to DYNAMIC, as it would exceed the maximum length according to your MySQL settings. To not see this message anymore, please upgrade your MySQL version or increase the row format.', 'wp-staging'));
                break;
            case 1214:
                $this->logger->warning(__('FULLTEXT removed from query, as your current MySQL version does not support it. To not see this message anymore, please upgrade your MySQL version.', 'wp-staging'));
                break;
            case 1226:
                /*
                 * Code: ER_USER_LIMIT_REACHED
                 * Format: User '%s' has exceeded the '%s' resource (current value: %ld)
                 */
                if (stripos($this->client->error(), 'max_queries_per_hour') !== false) {
                    $this->logger->warning(__('Your server has reached the maximum allowed queries per hour set by your admin or hosting provider. Please increase MySQL max_queries_per_hour_limit. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>', 'wp-staging'));
                } elseif (stripos($this->client->error(), 'max_updates_per_hour') !== false) {
                    $this->logger->warning(__('Your server has reached the maximum allowed updates per hour set by your admin or hosting provider. Please increase MySQL max_updates_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>', 'wp-staging'));
                } elseif (stripos($this->client->error(), 'max_connections_per_hour') !== false) {
                    $this->logger->warning(__('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_connections_per_hour. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>', 'wp-staging'));
                } elseif (stripos($this->client->error(), 'max_user_connections') !== false) {
                    $this->logger->warning(__('Your server has reached the maximum allowed connections per hour set by your admin or hosting provider. Please increase MySQL max_user_connections. <a href="https://wp-staging.com/docs/mysql-database-error-codes/" target="_blank">Technical details</a>', 'wp-staging'));
                }
                break;
            case 1813:
                $this->logger->warning(__('Could not restore the database. MySQL returned the error code 1813, which is related to a tablespace error that WP STAGING can\'t handle. Please contact your hosting company.', 'wp-staging'));
        }

        if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
            $this->logger->warning(sprintf(__('ExtendedInserterWithoutTransaction Failed Query: %s', 'wp-staging'), substr($this->extendedQuery, 0, 1000)));
            debug_log(sprintf(__('ExtendedInserterWithoutTransaction Failed Query: %s', 'wp-staging'), substr($this->extendedQuery, 0, 1000)));
        }

        $additionalInfo = '';
        $currentDbVersion = $this->database->getSqlVersion($compact = true);
        $backupDbVersion = $this->jobRestoreDataDto->getBackupMetadata()->getSqlServerVersion();
        if ($backupDbVersion !== $currentDbVersion) {
            $additionalInfo = sprintf(__(' Your current MySQL version is %s. If this issue persists, try using the same MySQL version used to create this Backup (%s).', 'wp-staging'), $currentDbVersion, $backupDbVersion);
        }

        $this->logger->warning(sprintf(__('Could not restore the query. MySQL has returned the error code %d, with message "%s".', 'wp-staging'), $this->client->errno(), $this->client->error()) . $additionalInfo);
    }

    /**
     * @throws \Exception
     */
    protected function extendInsert(&$insertQuery)
    {
        preg_match('#^INSERT INTO `(.+?(?=`))` VALUES (\(.+\));$#', $insertQuery, $matches);

        if (count($matches) !== 3) {
            throw new \Exception("Skipping INSERT query: $insertQuery");
        }

        // eg: wpstgtmp_posts
        $insertingIntoTableName = $matches[1];

        $insertingIntoHeader = "INSERT INTO `$insertingIntoTableName` VALUES ";

        $isFirstValue = false;

        if (empty($this->jobRestoreDataDto->getTableToRestore())) {
            if (!empty($this->extendedQuery)) {
                throw new \UnexpectedValueException('Query is not empty, cannot proceed.');
            }

            $this->jobRestoreDataDto->setTableToRestore($insertingIntoTableName);
            $this->extendedQuery .= $insertingIntoHeader;
            $isFirstValue = true;
        } elseif ($this->jobRestoreDataDto->getTableToRestore() !== $insertingIntoTableName) {
            $this->execExtendedQuery();
            if (!empty($this->extendedQuery)) {
                throw new \UnexpectedValueException('Query is not empty, cannot proceed.');
            }

            $this->jobRestoreDataDto->setTableToRestore($insertingIntoTableName);
            $this->extendedQuery .= $insertingIntoHeader;
            $isFirstValue = true;
        }

        // Making sure extending the query not exceed max allowed packet
        if (!$isFirstValue && strlen($this->extendedQuery . ",$matches[2]") >= $this->limitedMaxAllowedPacket) {
            $this->execExtendedQuery();
            if (!empty($this->extendedQuery)) {
                throw new \UnexpectedValueException('Query is not empty, cannot proceed.');
            }

            $this->jobRestoreDataDto->setTableToRestore($insertingIntoTableName);
            $this->extendedQuery .= $insertingIntoHeader;
            $isFirstValue = true;
        }

        if ($isFirstValue) {
            $this->extendedQuery .= $matches[2];
        } else {
            $this->extendedQuery .= ",$matches[2]";
        }
    }
}
