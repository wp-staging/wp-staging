<?php

namespace WPStaging\Backup\Service\Database\Importer\Insert;

use function WPStaging\functions\debug_log;

/**
 * @deprecated
 */

class ExtendedInserter extends TransactionInserter
{
    protected $extendedQuery = '';

    /**
     * @param string sql query
     * @throws \Exception
     */
    public function processQuery(&$queryToInsert)
    {
        if ($this->doQueryExceedsMaxAllowedPacket($queryToInsert)) {
            return null;
        }

        // We are processing a relatively large row
/*        if (false && $lengthQueryToInsert > 1 * MB_IN_BYTES) {
            $this->execExtendedQuery();
            $this->commit();

            WPStaging::getInstance()->getContainer()->make(SingleInserter::class)->exec($queryToInsert);

            return;
        }*/

        $this->maybeStartTransaction();

        $this->extendInsert($queryToInsert);

        if (strlen($this->extendedQuery) > $this->limitedMaxAllowedPacket) {
            $this->execExtendedQuery();
        }

        $this->maybeCommit();
    }

    public function commit()
    {
        if (empty($this->extendedQuery)) {
            return;
        }

        $this->maybeStartTransaction();

        $this->execExtendedQuery(true);

        parent::commit();
    }

    /**
     * Execute an transaction query
     * @param bool Commit the transaction
     * @throws \Exception
     */
    public function execExtendedQuery($isCommitting = false)
    {
        if (empty($this->extendedQuery)) {
            return;
        }

        $this->extendedQuery .= ';';

        $success = $this->exec($this->extendedQuery);

        if ($success) {
            $this->currentTransactionSize += strlen($this->extendedQuery);

            $this->extendedQuery = '';
            $this->jobRestoreDataDto->setTableToRestore('');
        } else {
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                $query = substr($this->extendedQuery, 0, 1000);
                debug_log("Extended Inserter Failed Query: {$query}");
            }

            $this->extendedQuery = '';
            $this->jobRestoreDataDto->setTableToRestore('');

            /*
             * If this INSERT fail, commit the transaction to start a fresh one in the next request.
             * Only do this if we are not committing already, to prevent loops.
             */
            if (!$isCommitting) {
                $this->commit();
            }

            throw new \RuntimeException(sprintf(
                'Failed to insert extended query. Query: %s Reason Code: %s Reason Message: %s',
                $this->extendedQuery,
                $this->client->errno(),
                $this->client->error()
            ));
        }
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
        } else {
            if ($this->jobRestoreDataDto->getTableToRestore() !== $insertingIntoTableName) {
                $this->commit();
                if (!empty($this->extendedQuery)) {
                    throw new \UnexpectedValueException('Query is not empty, cannot proceed.');
                }

                $this->jobRestoreDataDto->setTableToRestore($insertingIntoTableName);
                $this->extendedQuery .= $insertingIntoHeader;
                $isFirstValue = true;
            }
        }

        if ($isFirstValue) {
            $this->extendedQuery .= $matches[2];
        } else {
            $this->extendedQuery .= ",$matches[2]";
        }
    }
}
