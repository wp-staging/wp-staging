<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Component\Task\Database;

use WPStaging\Vendor\Psr\Log\LoggerInterface;
use RuntimeException;
use WPStaging\Component\Task\AbstractTask;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Database\TableService;
use WPStaging\Core\Utils\Logger;

class RenameTablesTask extends AbstractTask
{

    const REQUEST_NOTATION = 'database.tables.rename';
    const REQUEST_DTO_CLASS = RenameTablesRequestDto::class;
    const TASK_NAME = 'database_tables_rename';
    const TASK_TITLE = 'Renaming Tables';

    /** @var TableService */
    private $service;

    /** @var RenameTablesRequestDto */
    protected $requestDto;

    public function __construct(TableService $service, LoggerInterface $logger, Cache $cache)
    {
        parent::__construct($logger, $cache);
        $this->service = $service;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->prepare();

        // TODO shall we check for locks and force unlock? such as;
        // `SHOW OPEN TABLES WHERE in_use > 0;`
        // `SHOW PROCESSLIST;`
        // `KILL {PROCESS_ID};`
        $tables = $this->service->findTableStatusStartsWith($this->requestDto->getSource());

        if (!$tables) {
            throw new RuntimeException('Failed to find tables with prefix: ' . $this->requestDto->getSource());
        }

        // Renaming table is rather instant thing to do thus all in one action!
        $sqlRename = 'RENAME TABLE ';
        $sqlDropTarget = 'DROP TABLE IF EXISTS ';
        $sqlDropSource = 'DROP TABLE IF EXISTS ';
        foreach ($tables as $table) {
            $newName = $this->requestDto->getTarget();
            $newName .= str_replace($this->requestDto->getSource(), null, $table->getName());

            $sqlRename .= $table->getName() . ' TO ' . $newName . ',';
            $sqlDropTarget .= $newName . ',';
            $sqlDropSource .= $table->getName() . ',';
        }

        /** @var Database $database */
        $database = $this->service->getDatabase();
        $database->exec('SET FOREIGN_KEY_CHECKS = 0');
        $database->exec(trim($sqlDropTarget, ','));
        $database->exec(trim($sqlRename, ','));
        $database->exec(trim($sqlDropSource, ','));
        $database->exec('SET FOREIGN_KEY_CHECKS = 1');

        wp_cache_flush();

        $this->logger->log(
            Logger::TYPE_INFO,
            sprintf('Replaced %s to %s', $this->requestDto->getSource(), $this->requestDto->getTarget())
        );

        return $this->generateResponse();
    }

    /**
     * @inheritDoc
     */
    public function getTaskName()
    {
        return self::TASK_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getRequestNotation()
    {
        return self::REQUEST_NOTATION;
    }

    /**
     * @inheritDoc
     */
    public function getRequestDtoClass()
    {
        return self::REQUEST_DTO_CLASS;
    }

    public function getStatusTitle(array $args = [])
    {
        return __(self::TASK_TITLE, 'wp-staging');
    }

    public function getCacheFiles()
    {
        return [
            $this->cache->getFilePath(),
        ];
    }
}
