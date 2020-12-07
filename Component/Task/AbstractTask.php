<?php

namespace WPStaging\Component\Task;

use WPStaging\Vendor\Psr\Log\LoggerInterface;
use WPStaging\Component\Dto\AbstractRequestDto;
use WPStaging\Framework\Traits\RequestNotationTrait;
use WPStaging\Framework\Traits\TimerTrait;
use WPStaging\Framework\Utils\Cache\AbstractCache;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Core\Utils\Logger;

abstract class AbstractTask implements TaskInterface
{
    use TimerTrait;
    use RequestNotationTrait;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Cache */
    protected $cache;

    /** @var AbstractRequestDto */
    protected $requestDto;

    /** @var bool */
    protected $prepared;

    // TODO RPoC
    /** @var string|null */
    protected $jobName;

    /** @var int|null */
    protected $jobId;

    /** @var bool */
    protected $debug;

    public function __construct(LoggerInterface $logger, Cache $cache)
    {
        $this->initiateStartTime();

        /** @var Logger logger */
        $this->logger = $logger;
        $this->cache = clone $cache;

        $this->cache->setLifetime(HOUR_IN_SECONDS);
        $this->cache->setFilename('task_' . $this->getTaskName());

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    public function __destruct()
    {
        if (!$this->requestDto) {
            return;
        }

        if ($this->requestDto->getSteps()->isFinished()) {
            $this->cache->delete();
            return;
        }

        $this->cache->save($this->requestDto->toArray());
    }

    /**
     * @return object
     */
    abstract public function execute();

    /**
     * @return string
     */
    abstract public function getTaskName();

    /**
     * @inheritDoc
     */
    abstract public function getStatusTitle(array $args = []);

    /**
     * @return string
     */
    abstract public function getRequestNotation();

    /**
     * @return string
     */
    abstract public function getRequestDtoClass();

    public function prepare()
    {
        if ($this->prepared) {
            return;
        }

        $this->findRequestDto();
        $this->prepared = true;
    }

    public function setRelativeCacheDirectory($path)
    {
        /** @var AbstractCache $cache */
        foreach ($this->getCaches() as $cache) {
            $fullPath = trailingslashit($cache->getPath() . $path);
            $cache->setPath($fullPath);
        }
    }

    public function setRequestDto(AbstractRequestDto $dto)
    {
        $this->requestDto = $dto;
    }

    /**
     * @return TaskResponseDto
     */
    public function generateResponse()
    {
        $steps = $this->requestDto->getSteps();
        $steps->incrementCurrentStep();

        // TODO Hydrate
        $response = $this->getResponseDto();
        $response->setStatus($steps->isFinished());
        $response->setPercentage($steps->getPercentage());
        $response->setTotal($steps->getTotal());
        $response->setStep($steps->getCurrent());
        $response->setTask($this->getTaskName());
        $response->setRunTime($this->getRunningTime());
        $response->setStatusTitle($this->getStatusTitle());
        $response->addMessage($this->logger->getLastLogMsg()); // TODO grab current task's logs, not last log message!

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        /** @noinspection PhpParamsInspection */
        $this->logger->setFileName(sprintf('%s__%s__%s',
            $this->getJobId(),
            $this->getJobName(),
            date('Y_m_d__H')
        ));

        return $response;
    }

    /**
     * @return string|null
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * @param string|null $jobName
     */
    public function setJobName($jobName)
    {
        $this->jobName = $jobName;
        // TODO RPoC?
        $this->setRelativeCacheDirectory($jobName);
    }

    /**
     * @return string|int|null
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @param string|int|null $jobId
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;
    }

    /**
     * Finds via cache or $_POST the request data.
     * If nothing is provided, sets and empty RequestDto for the task
     * @return void
     */
    protected function findRequestDto()
    {
        if ($this->requestDto) {
            return;
        }

        $data = $this->cache->get([]);
        $postData = $this->resolvePostRequestData('tasks.' . $this->getRequestNotation());
        if ($postData) {
            $data = array_replace_recursive($data, $postData);
        }

        $dtoClass = $this->getRequestDtoClass();
        /** @var AbstractRequestDto $dto */
        $this->requestDto = (new $dtoClass);
        $this->requestDto->hydrate($data);

        if ($this->debug) {
            $this->logger->debug($this->getTaskName() . ' Request: ' . json_encode($this->requestDto));
        }
    }

    protected function getResponseDto()
    {
        return new TaskResponseDto;
    }

    /**
     * @return Cache[]|AbstractCache[]|array
     */
    protected function getCaches()
    {
        return [
            $this->cache,
        ];
    }
}
