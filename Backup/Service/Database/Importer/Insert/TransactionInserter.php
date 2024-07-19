<?php
namespace WPStaging\Backup\Service\Database\Importer\Insert;
abstract class TransactionInserter extends QueryInserter
{
    protected $currentTransactionSize = 0;
    protected function maybeStartTransaction()
    {
        if ($this->jobRestoreDataDto->getTransactionStarted()) {
            return;
        }
        $query = 'START TRANSACTION;';
        if ($this->exec($query)) {
            $this->jobRestoreDataDto->setTransactionStarted(true);
        } else {
            throw new \UnexpectedValueException(sprintf(
                'Failed to start transaction; Reason: %d - %s',
                $this->client->errno(),
                $this->client->error()
            ));
        }
    }
    public function maybeCommit()
    {
        if (!$this->jobRestoreDataDto->getTransactionStarted()) {
            return false;
        }
        if ($this->currentTransactionSize >= $this->maxInnoDbLogSize || $this->isThreshold()) {
            $this->commit();
            return true;
        }
        return false;
    }
    public function commit()
    {
        if (!$this->jobRestoreDataDto->getTransactionStarted()) {
            return;
        }
        $query = 'COMMIT;';
        if ($this->exec($query)) {
            $this->jobRestoreDataDto->setTransactionStarted(false);
            $this->currentTransactionSize = 0;
        } else {
            throw new \UnexpectedValueException(sprintf(
                'Failed to commit transaction; Reason: %d - %s',
                $this->client->errno(),
                $this->client->error()
            ));
        }
    }
}
