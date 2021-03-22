<?php

namespace WPStaging\Command\Database;

use WPStaging\Command\Database\Snapshot\CreateSnapshotCommand;
use WPStaging\Command\Database\Snapshot\SnapshotDto;
use WPStaging\Command\Database\Snapshot\SnapshotHandler;
use WPStaging\Command\Database\Snapshot\UpdateSnapshotCommand;
use WPStaging\Framework\Command\HandlerInterface;
use WPStaging\Core\WPStaging;

class SnapshotFactory
{
    // TODO PHP7.1; visibility
    const CREATE_SNAPSHOT = 'create';
    const UPDATE_SNAPSHOT = 'update';
    const DELETE_SNAPSHOT = 'delete';

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
                $handler->addCommand(WPStaging::getInstance()->get(CreateSnapshotCommand::class));
                return;
            case self::UPDATE_SNAPSHOT:
                $handler->addCommand(WPStaging::getInstance()->get(UpdateSnapshotCommand::class));
                return;
            case self::DELETE_SNAPSHOT:
                // todo Implement Delete Snapshot Command
                return;
        }
    }
}
