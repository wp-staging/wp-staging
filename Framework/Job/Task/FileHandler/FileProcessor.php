<?php

namespace WPStaging\Framework\Job\Task\FileHandler;

use WPStaging\Framework\Job\Interfaces\FileTaskInterface;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * Class FileProcessor
 *
 * This class applies the Chain of Responsibility pattern.
 *
 * @package WPStaging\Framework\Job\Task\FileHandler
 */
class FileProcessor
{
    private $moveHandler;
    private $deleteHandler;

    public function __construct(MoveHandler $moveHandler, DeleteHandler $deleteHandler)
    {
        $this->moveHandler   = $moveHandler;
        $this->deleteHandler = $deleteHandler;
    }

    public function handle($action, $source, $destination, FileTaskInterface $fileTask, LoggerInterface $logger)
    {
        $this->moveHandler->setContext($fileTask, $logger);
        $this->deleteHandler->setContext($fileTask, $logger);

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
