<?php

namespace WPStaging\Command\Database;

use WPStaging\Command\Database\Snapshot\CreateSnapshotCommand;
use WPStaging\Command\Database\Snapshot\SnapshotDto;
use WPStaging\Command\Database\Snapshot\SnapshotHandler;
use WPStaging\Command\Database\Snapshot\UpdateSnapshotCommand;
use WPStaging\Framework\Command\CommandInterface;
use WPStaging\Framework\Command\HandlerInterface;
use WPStaging\WPStaging;

class SnapshotFactory
{
    // TODO PHP7.1; visibility
    const CREATE_SNAPSHOT = 'create';
    const UPDATE_SNAPSHOT = 'update';
    const DELETE_SNAPSHOT = 'delete';

    private static $container;

    /**
     * @param SnapshotDto $dto
     * @param string|null $action
     *
     * @return SnapshotHandler
     */
    public static function make(SnapshotDto $dto, $action = null)
    {
        $handler = new SnapshotHandler($dto);

        if ($action) {
            self::makeAction($handler, $action);
            return $handler;
        }

        foreach([self::CREATE_SNAPSHOT, self::UPDATE_SNAPSHOT, self::DELETE_SNAPSHOT] as $item) {
            self::makeAction($handler, $item);
        }

        return $handler;
    }

    private static function makeAction(HandlerInterface $handler, $action)
    {
        switch($action) {
            case self::CREATE_SNAPSHOT:
                $handler->addCommand(self::getCommand(CreateSnapshotCommand::class));
                return;
            case self::UPDATE_SNAPSHOT:
                $handler->addCommand(self::getCommand(UpdateSnapshotCommand::class));
                return;
            case self::DELETE_SNAPSHOT:
                // todo Implement Delete Snapshot Command
                return;
        }
    }

    /**
     * @param string $command
     *
     * @return CommandInterface|null
     */
    private static function getCommand($command)
    {
        // TODO RPoC v3.0.0
        if (!self::$container) {
            self::$container = WPStaging::getContainer();
        }

        /** @var CommandInterface|null $command */
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $command = self::$container->get($command);
        return $command;
    }
}
