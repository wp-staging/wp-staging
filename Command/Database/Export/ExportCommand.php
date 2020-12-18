<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types & type-hints

namespace WPStaging\Command\Database\Export;

use Exception;
use WPStaging\Pro\Library\Mysqldump\Mysqldump;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Framework\Database\TableService;
use WPStaging\Framework\Command\CommandInterface;

class ExportCommand implements CommandInterface
{
    const FORMAT_GZIP = Mysqldump::GZIP;
    const FORMAT_BZIP2 = Mysqldump::BZIP2;
    const FORMAT_SQL = Mysqldump::NONE;

    /** @var ExportDto */
    private $dto;

    /** @var TableManager */
    private $tableService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ExportDto $dto, TableService $tableService = null, LoggerInterface $logger = null)
    {
        $this->dto = $dto;
        $this->logger = $logger;
        $this->tableService = $tableService;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function execute()
    {
        $this->generateSqlFile();
    }

    /**
     * @throws Exception
     */
    protected function generateSqlFile()
    {
        $dumper = new Mysqldump(
            sprintf('mysql:host=%s;port=%d;dbname=%s', $this->dto->getHost(), $this->dto->getPort(), $this->dto->getName()),
            $this->dto->getUsername(),
            $this->dto->getPassword(),
            [
                'compress' => $this->dto->getFormat(),
                'include-tables' => $this->getIncludeTables(),
                'version' => $this->dto->getVersion(),
            ]
        );

        try {
            $dumper->start($this->dto->getFullPath());
            return $this->dto->getFullPath();
        }
        catch (Exception $e) {
            $this->logger->alert($e->getMessage());
            throw new ExportException(sprintf(
                'Failed to export database. Database Name: %s | Prefix: %s | File Path: %s | %s',
                $this->dto->getUsername() .':' . $this->dto->getPassword() . '@' . $this->dto->getHost() . ':' . $this->dto->getPort() . ' - ' . $this->dto->getName(),
                $this->dto->getPrefix(),
                $this->dto->getFullPath(),
                $e->getMessage()
            ));
        }
    }

    protected function getIncludeTables()
    {
        $tables = $this->tableService->findTableNamesStartWith($this->dto->getPrefix());
        $data = [];
        foreach ($tables as $table) {
            $data[] = $table->getName();
        }
        return $data;
    }
}
