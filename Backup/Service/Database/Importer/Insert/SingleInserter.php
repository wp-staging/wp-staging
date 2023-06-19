<?php

namespace WPStaging\Backup\Service\Database\Importer\Insert;

/**
 * @deprecated
 */

class SingleInserter extends TransactionInserter
{
    public function processQuery(&$insertQuery)
    {
        if ($this->doQueryExceedsMaxAllowedPacket($insertQuery)) {
            return null;
        }

        $this->maybeStartTransaction();

        if (!$this->exec($insertQuery)) {
            throw new \RuntimeException(sprintf(
                'Failed to insert single query. Reason Code: %s Reason Message: %s',
                $this->client->errno(),
                $this->client->error()
            ));
        }

        $this->currentTransactionSize += strlen($insertQuery);

        $this->maybeCommit();
    }
}
