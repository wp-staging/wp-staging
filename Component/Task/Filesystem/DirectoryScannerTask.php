<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Component\Task\Filesystem;

use Exception;
use InvalidArgumentException;
use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Component\Task\AbstractTask;
use WPStaging\Framework\Queue\FinishedQueueException;
use WPStaging\Framework\Traits\ResourceTrait;
use WPStaging\Framework\Utils\Cache\BufferedCache;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Filesystem\DirectoryScannerControl;

/**
 * Class DirectoryScannerTask
 *
 * @see DirectoryScannerControl
 *
 * @package WPStaging\Component\Task\Filesystem
 */
class DirectoryScannerTask extends AbstractTask
{
    use ResourceTrait;

    const REQUEST_NOTATION = 'filesystem.directory.scanner';
    const REQUEST_DTO_CLASS = DirectoryScannerRequestDto::class;
    const TASK_NAME = 'filesystem_directory_scanner';
    const TASK_TITLE = 'Scanning Directories';

    /** @var DirectoryScannerRequestDto */
    public $requestDto;

    /** @var DirectoryScannerControl */
    private $scannerControl;

    /** @var BufferedCache */
    private $directoryCache;

    /** @var array */
    private $directories;

    public function __construct(DirectoryScannerControl $scannerControl, LoggerInterface $logger, Cache $cache)
    {
        parent::__construct($logger, $cache);
        $this->directories = [];

        $scannerControl->setQueueByName();
        $this->scannerControl = $scannerControl;

        $this->directoryCache = clone $scannerControl->getCache();
        $this->directoryCache->setLifetime(DAY_IN_SECONDS);
        $this->directoryCache->setFilename(DirectoryScannerControl::DATA_CACHE_FILE);
    }

    public function __destruct()
    {
        parent::__destruct();

        if ($this->directories) {
            $this->directoryCache->append($this->directories);
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
        $this->logger->info(sprintf('Scanned %d directories', $this->requestDto->getSteps()->getTotal()));
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
        return __(self::TASK_TITLE, 'wp-staging');
    }

    protected function scanCurrentDirectory()
    {
        $directories = null;
        try {
            $directories = $this->scannerControl->scanCurrentPath($this->requestDto->getExcluded());
        } catch (FinishedQueueException $e) {
            $this->logger->info('Finished scanning directories');
            $this->requestDto->getSteps()->finish();
            return;
        } catch (InvalidArgumentException $e) {
            // This happens when a symlink is there and we don't follow them.
        } catch (Exception $e) {
            $this->logger->warning($e->getMessage());
        }

        // No directories found here, skip it
        if (empty($directories)) {
            return;
        }

        foreach ($directories as $directory) {
            if ($this->isThreshold()) {
                return;
            }
            $relativePath = str_replace(ABSPATH, null, $directory);
            $this->scannerControl->addToNewQueue($relativePath);
            $this->directories[] = $relativePath;
        }
        $this->directories = array_unique($this->directories);
    }

    protected function findRequestDto()
    {
        parent::findRequestDto();

        if (!$this->requestDto->getIncluded()) {
            $this->requestDto->setIncluded([ ABSPATH ]);
        }

        if ($this->requestDto->getSteps()->getTotal() > 0) {
            return;
        }

        $this->directories = array_map(static function($dir) {
            return str_replace(ABSPATH, null, $dir);
        }, $this->requestDto->getIncluded());

        /** @noinspection NullPointerExceptionInspection */
        if ($this->scannerControl->getQueue()->count() < 1) {
            $this->scannerControl->setNewQueueItems($this->directories);
        }

        $totalSteps = count($this->requestDto->getIncluded());
        if ($totalSteps < 0) {
            $totalSteps = 0;
        }
        $this->requestDto->getSteps()->setTotal($totalSteps);

        // Exclude WP Staging related directories and Cache dir
        // There is no need to backup WP Staging as you shouldn't restore WPSTG backup without WPSTG having installed
        // Don't backup / restore cache as it can be problematic
        $excludedDirs = $this->requestDto->getExcluded();

        $adapter = $this->scannerControl->getDirectory();

        $excludedDirs[] = WPSTG_PLUGIN_DIR;
        $excludedDirs[] = $adapter->getPluginUploadsDirectory();
        $excludedDirs[] = WP_CONTENT_DIR . '/cache';
        $this->requestDto->setExcluded($excludedDirs);
    }

    protected function getCaches()
    {
        $caches = parent::getCaches();
        $caches[] = $this->directoryCache;
        $caches[] = $this->scannerControl->getCache();
        /** @noinspection NullPointerExceptionInspection */
        $caches[] = $this->scannerControl->getQueue()->getStorage()->getCache();
        return $caches;
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
        $steps->setTotal($steps->getTotal() + count($this->directories));

        /** @noinspection NullPointerExceptionInspection */
        if ($this->scannerControl->getQueue()->count() > 0) {
            return;
        }
        
        $total = $steps->getTotal() - count($this->requestDto->getIncluded());
        $total = $total >= 0 ? $total : 0;

        $steps->setTotal($total);
        $steps->setCurrent($total);
    }
}
