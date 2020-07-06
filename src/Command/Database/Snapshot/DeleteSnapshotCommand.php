<?php

namespace WPStaging\Command\Database\Snapshot;

use WPStaging\Manager\Database\TableManager;
use WPStaging\Manager\SnapshotManager;

class DeleteSnapshotCommand extends AbstractSnapshotCommand
{
    /**
     * @param bool force deletes snapshot from listing even if there are no tables in db
     * @throws SnapshotCommandException
     */
    public function execute()
    {
        $this->validateSnapshot();

        $tables = (new TableManager)->findStartsWith($this->dto->getTargetPrefix());

        if (!$tables && $tables->count() < 1) {
            throw new SnapshotCommandException('DeleteSnapshot tables do not exist: ', $this->dto->getTargetPrefix());
        }

        $this->database->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach($tables as $table) {
            $this->database->exec('DROP TABLE IF EXISTS ' . $table->getName());
        }
        $this->database->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->saveSnapshots();
    }

    protected function saveSnapshots()
    {
        (new SnapshotManager)->deleteByPrefix($this->dto->getTargetPrefix());
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    protected function validateSnapshot()
    {
        parent::validateSnapshot();
        if (!$this->snapshots->doesIncludeId($this->dto->getTargetPrefix())) {
            throw new SnapshotCommandException(
                'DeleteSnapshot prefix does not exist: ' . $this->dto->getTargetPrefix()
            );
        }
    }
}
