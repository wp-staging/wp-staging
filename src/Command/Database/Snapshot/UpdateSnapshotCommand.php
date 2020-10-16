<?php

namespace WPStaging\Command\Database\Snapshot;

use DateTime;
use WPStaging\Pro\Snapshot\Entity\Snapshot;
use WPStaging\Framework\Database\TableService;

class UpdateSnapshotCommand extends AbstractSnapshotCommand
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection DuplicatedCode
     */
    public function execute()
    {
        $this->validateSnapshot();

        $tables = (new TableService)->findTableNamesStartWith();
        if (!$tables) {
            throw new SnapshotCommandException('UpdateSnapshot failed to get tables');
        }

        $this->database->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach($tables as $table) {
            $newTableName = $this->dto->getTargetPrefix() . $table->getName();
            // Why this way? Because of schema changes, do not go shortcut with UPDATE query!
            $this->database->exec('DROP TABLE IF EXISTS ' . $newTableName);
            $this->database->exec('OPTIMIZE TABLE '. $table->getName());
            $this->database->exec('CREATE TABLE ' . $newTableName . ' LIKE ' . $table->getName());
            $this->database->exec('INSERT INTO ' . $newTableName . ' SELECT * FROM ' . $table->getName());
        }
        $this->database->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->saveSnapshots();
    }

    protected function saveSnapshots()
    {
        /** @var Snapshot $snapshot */
        $snapshot = $this->snapshots->findById($this->dto->getTargetPrefix());
        $snapshot->setUpdatedAt(new DateTime);
        $this->repository->save($this->snapshots);
    }

    protected function validateSnapshot()
    {
        parent::validateSnapshot();
        if (!$this->snapshots->doesIncludeId($this->dto->getTargetPrefix())) {
            throw new SnapshotCommandException(
                'UpdateSnapshot prefix does not exist: ' . $this->dto->getTargetPrefix()
            );
        }
    }
}
