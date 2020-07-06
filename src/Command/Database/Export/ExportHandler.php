<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types & type-hints

namespace WPStaging\Command\Database\Export;

use Exception;
use Psr\Log\LoggerInterface;
use WPStaging\Manager\Database\TableManager;
use WPStaging\Manager\FileSystem\DirectoryManager;
use WPStaging\Plugin;

// TODO Check PDO extension installation: \WPStaging\Component\Snapshot\AjaxExport:38
class ExportHandler
{
    const EXPORT_DIRNAME = 'snapshots/export';

    /** @var Plugin */
    private $plugin;

    /** @var DirectoryManager */
    private $directoryManager;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param Plugin $plugin
     * @param DirectoryManager $directoryManager
     * @param LoggerInterface $logger
     */
    public function __construct(Plugin $plugin, DirectoryManager $directoryManager, LoggerInterface $logger)
    {
        $this->plugin = $plugin;
        $this->directoryManager = $directoryManager;
        $this->logger = $logger;
    }

    /**
     * @param string $prefix
     *
     * @return string
     * @throws Exception
     */
    public function handle($prefix)
    {
        $dto = (new ExportDto)->hydrate([
            'prefix' => $prefix,
            'directory' => $this->generatePath(),
            'format' => $this->provideFormat(),
            'version' => $this->plugin->getVersion(),
        ]);

        $command = new ExportCommand($dto, new TableManager, $this->logger);
        $command->execute();
        return $dto->getFullPath();
    }

    /**
     * @return string
     */
    public function generatePath()
    {
        $dirname = sprintf(
            '%s/%s/',
            $this->plugin->getDomain(),
            self::EXPORT_DIRNAME
        );
        return $this->directoryManager->provideCustomUploadsDirectory($dirname);
    }

    private function provideFormat()
    {
        if (!function_exists('gzwrite')) {
            return ExportCommand::FORMAT_SQL;
        }
        return ExportCommand::FORMAT_GZIP;
    }
}
