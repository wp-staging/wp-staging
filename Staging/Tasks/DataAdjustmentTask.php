<?php

namespace WPStaging\Staging\Tasks;

use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\Queue\SeekableQueueInterface;
use WPStaging\Framework\Job\Dto\StepsDto;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Framework\Utils\Urls;
use WPStaging\Staging\Interfaces\AdvanceStagingOptionsInterface;
use WPStaging\Staging\Interfaces\StagingDatabaseDtoInterface;
use WPStaging\Staging\Interfaces\StagingNetworkDtoInterface;
use WPStaging\Staging\Interfaces\StagingOperationDtoInterface;
use WPStaging\Staging\Interfaces\StagingSiteDtoInterface;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

abstract class DataAdjustmentTask extends StagingTask
{
    /** @var JobDataDto|StagingOperationDtoInterface|StagingDatabaseDtoInterface|StagingSiteDtoInterface|AdvanceStagingOptionsInterface|StagingNetworkDtoInterface */
    protected $jobDataDto; // @phpstan-ignore-line

    /**
     * @var Urls
     */
    protected $urls;

    /**
     * @param LoggerInterface $logger
     * @param Cache $cache
     * @param StepsDto $stepsDto
     * @param SeekableQueueInterface $taskQueue
     * @param Urls $urls
     */
    public function __construct(LoggerInterface $logger, Cache $cache, StepsDto $stepsDto, SeekableQueueInterface $taskQueue, Urls $urls)
    {
        parent::__construct($logger, $cache, $stepsDto, $taskQueue);
        $this->urls = $urls;
    }
}
