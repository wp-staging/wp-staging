<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; type-hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Command\Database\Snapshot;

use WPStaging\Pro\Snapshot\Entity\Snapshot;
use WPStaging\Pro\Snapshot\Repository\SnapshotRepository;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Collection\Collection;
use WPStaging\Framework\Collection\OptionCollection;
use WPStaging\Framework\Command\CommandInterface;

abstract class AbstractSnapshotCommand implements CommandInterface
{

    /** @var Database */
    protected $database;

    /** @var SnapshotRepository */
    protected $repository;

    /** @var SnapshotDto */
    protected $dto;

    /** @var Snapshot[]|OptionCollection */
    protected $snapshots;

    abstract protected function saveSnapshots();

    public function __construct(Database $database, SnapshotRepository $repository, SnapshotDto $dto = null)
    {
        $this->database = $database;
        $this->repository = $repository;
        $this->setDto($dto);

        $this->snapshots = $this->repository->findAll()? : new OptionCollection(Snapshot::class);
    }

    /**
     * @param SnapshotDto $dto
     */
    public function setDto(SnapshotDto $dto = null)
    {
        if (!$dto) {
            return;
        }

        $this->dto = $dto;

        if (!$this->dto->getSourcePrefix()) {
            $this->dto->setSourcePrefix($this->database->getPrefix());
        }
    }

    /**
     * @throws SnapshotCommandException
     */
    protected function validateSnapshot()
    {
        if ($this->database->getPrefix() === $this->dto->getTargetPrefix()) {
            throw new SnapshotCommandException('You are trying to process production tables!');
        }

        if ($this->dto->getSourcePrefix() === $this->dto->getTargetPrefix()) {
            throw new SnapshotCommandException('You are trying to process same tables!');
        }
    }
}
