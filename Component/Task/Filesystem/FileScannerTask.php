<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Component\Task\Filesystem;

use Exception;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use RuntimeException;
use WPStaging\Component\Task\AbstractTask;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Queue\Storage\BufferedCacheStorage;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Cache\AbstractCache;
use WPStaging\Framework\Utils\Cache\BufferedCache;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Filesystem\DirectoryScanner;
use WPStaging\Framework\Filesystem\FileScanner;
use WPStaging\Framework\Filesystem\Filesystem;

class FileScannerTask extends AbstractTask
{
    use ResourceTrait;

    const REQUEST_NOTATION = 'filesystem.file.scanner';
    const REQUEST_DTO_CLASS = FileScannerRequestDto::class;
    const TASK_NAME = 'filesystem_file_scanner';
    const TASK_TITLE = 'Scanning Files in %d Directories';

    /** @var FileScannerRequestDto */
    public $requestDto;

    /** @var FileScanner */
    private $scanner;

    /** @var BufferedCache */
    private $fileCache;

    /** @var array */
    private $files;

    public function __construct(FileScanner $scanner, LoggerInterface $logger, Cache $cache)
    {
        parent::__construct($logger, $cache);
        $this->files = [];

        $scanner->setQueueByName();
        $this->scanner = $scanner;

        $this->fileCache = clone $scanner->getCache();
        $this->fileCache->setLifetime(DAY_IN_SECONDS);
        $this->fileCache->setFilename(FileScanner::DATA_CACHE_FILE);
    }

    public function __destruct()
    {
        parent::__destruct();

        if ($this->files) {
            $this->fileCache->append($this->files);
        }
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->prepare();
        while ($this->shouldContinue()) {
            $this->scanCurrentDirectory();
        }

        $this->updateSteps();
        $this->logger->info(sprintf('Scanned %d files', $this->requestDto->getSteps()->getTotal()));
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

    /**
     * @inheritDoc
     */
    public function getStatusTitle(array $args = [])
    {
        $total = isset($args[0]) ? $args[0] : 0;
        if ($this->requestDto && $this->requestDto->getSteps()) {
            $total = $this->requestDto->getSteps()->getTotal();
        }
        return sprintf(__(self::TASK_TITLE, 'wp-staging'), $total);
    }

    protected function scanCurrentDirectory()
    {
        $files = null;
        try {
            $files = $this->scanner->scanCurrentPath($this->requestDto->getExcluded());
        } catch (FinishedQueueException $e) {
            $this->logger->info('Finished scanning files');
            $this->requestDto->getSteps()->finish();
            return;
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        // No directories found here, skip it
        if ($files === null || $files->count() < 1) {
            return;
        }

        foreach ($files as $file) {
            if ($this->isThreshold()) {
                return;
            }
            $relativePath = str_replace(ABSPATH, null, $file->getPathname());
            $this->files[] = $relativePath;
        }
    }

    protected function findRequestDto()
    {
        parent::findRequestDto();

        if ($this->requestDto->getSteps()->getTotal() > 0) {
            return;
        }

        $this->requestDto->setIncluded(array_map(static function($dir) {
            return str_replace(ABSPATH, null, $dir);
        }, $this->requestDto->getIncluded()));

        /** @noinspection NullPointerExceptionInspection */
        if ($this->scanner->getQueue()->count() < 1) {
            $this->initiateQueue();
        }

        $this->requestDto->getSteps()->setTotal(1);
    }

    protected function getCaches()
    {
        $caches = parent::getCaches();
        $caches[] = $this->fileCache;
        /** @noinspection NullPointerExceptionInspection */
        $caches[] = $this->scanner->getQueue()->getStorage()->getCache();
        return $caches;
    }

    private function initiateQueue()
    {
        if ($this->requestDto->getIncluded()) {
            $this->scanner->setNewQueueItems($this->requestDto->getIncluded());
        }

        $directoryData = $this->cache->getPath() . DirectoryScanner::DATA_CACHE_FILE . '.' . AbstractCache::EXTENSION;
        if (!file_exists($directoryData)) {
            throw new RuntimeException(sprintf(
                'File %s does not exists. Need to Scan Directories first',
                $directoryData
            ));
        }

        $queueFile = $this->cache->getPath() . BufferedCacheStorage::FILE_PREFIX . FileScanner::QUEUE_CACHE_FILE;
        $queueFile .= '.' . AbstractCache::EXTENSION;

        if (!(new Filesystem)->copy($directoryData, $queueFile)) {
            throw new RuntimeException('Failed to copy %s as file scanner queue %s', $directoryData, $queueFile);
        }
    }

    /**
     * @return bool
     */
    private function shouldContinue()
    {
        return !$this->isThreshold() && !$this->requestDto->getSteps()->isFinished();
    }

    private function updateSteps()
    {
        // Update steps
        $steps = $this->requestDto->getSteps();
        $steps->setTotal($steps->getTotal() + count($this->files));

        /** @noinspection NullPointerExceptionInspection */
        if ($this->scanner->getQueue()->count() > 0) {
            return;
        }

        $steps = $this->requestDto->getSteps();
        $total = $steps->getTotal() - 1;
        $total = $total >= 0 ? $total : 0;

        $steps->setTotal($total);
        $steps->setCurrent($total);
    }
}
