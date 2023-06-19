<?php

namespace WPStaging\Backup\Task\RestoreFileHandlers;

use WPStaging\Backup\Task\FileRestoreTask;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * Class RestoreFileProcessor
 *
 * This class applies the Chain of Responsibility pattern.
 *
 * @package WPStaging\Backup\Abstracts\Task\RestoreFileHandlers
 */
class RestoreFileProcessor
{
    private $moveHandler;
    private $deleteHandler;

    public function __construct(MoveHandler $moveHandler, DeleteHandler $deleteHandler)
    {
        $this->moveHandler   = $moveHandler;
        $this->deleteHandler = $deleteHandler;
    }

    public function handle($action, $source, $destination, FileRestoreTask $fileRestoreTask, LoggerInterface $logger)
    {
        $this->moveHandler->setContext($fileRestoreTask, $logger);
        $this->deleteHandler->setContext($fileRestoreTask, $logger);

        switch ($action) {
            case 'move':
                $this->moveHandler->handle($source, $destination);
                break;
            case 'delete':
                $this->deleteHandler->handle($source, $destination);
                break;
        }
    }
}
