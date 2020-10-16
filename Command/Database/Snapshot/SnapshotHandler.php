<?php

namespace WPStaging\Command\Database\Snapshot;

use WPStaging\Framework\Command\CommandInterface;
use WPStaging\Framework\Command\HandlerInterface;
use SplObjectStorage;

class SnapshotHandler implements HandlerInterface
{
    const PREFIX_AUTOMATIC = 'wpsa';
    const PREFIX_MANUAL = 'wpsm';

    /** @var SplObjectStorage */
    private $commands;

    /** @var SnapshotDto */
    private $dto;

    public function __construct(SnapshotDto $dto)
    {
        $this->dto = $dto;
        $this->commands = new SplObjectStorage;
    }

    public function addCommand(CommandInterface $command)
    {
        /** @var AbstractSnapshotCommand $command */
        if ($this->commands->contains($command)) {
            return;
        }

        $command->setDto($this->dto);

        $this->commands->attach($command);
    }

    /**
     * @param string|null $action
     */
    public function handle($action = null)
    {
        /** @var CommandInterface $command */
        foreach($this->commands as $command) {
            $command->execute();
        }
    }
}
