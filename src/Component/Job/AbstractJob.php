<?php

// If child class has `init` method, it will be executed with constructor to prepare the class for job execution
// Such as setting -if needed- total steps, current step etc.

namespace WPStaging\Component\Job;

use Psr\Log\LoggerInterface;
use WPStaging\Component\Job\Dto\CloneDto;
use WPStaging\Component\Job\Dto\StepsDto;
use WPStaging\Plugin;
use WPStaging\Utils\Logger;
use WPStaging\WPStaging;

// TODO RPoC Perhaps Trait depending on usage
abstract class AbstractJob
{
    /** @var LoggerInterface|null */
    protected $logger;

    /** @var StepsDto */
    protected $steps;

    public function __construct(StepsDto $steps = null)
    {
        $this->logger = $this->get(LoggerInterface::class);
        $this->steps = $steps?: new StepsDto;

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    /**
     * @return object
     */
    abstract public function execute();

    /**
     * @noinspection PhpUnused
     * @return LoggerInterface|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @noinspection PhpUnused
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return object
     */
    public function generateResponse()
    {
        $this->steps->incrementCurrentStep();
        $isFinished = $this->steps->isFinished();
        return (object)[
            'status' => $isFinished,
            'percentage' => $this->steps->getPercentage(),
            'total' => $this->steps->getTotal(),
            'step' => $this->steps->getCurrent(),
            'job' => $this->generateCurrentJob(),
            'last_msg' => $this->logger->getLastLogMsg(), // TODO grab current task's logs, not last log message!
            'running_time' => $this->getTime() - time(),
            'job_done' => $isFinished,
            'isForceSave' => true, // TODO remove when REF
        ];
    }

    /**
     * @return float
     */
    protected function getTime()
    {
        $time = explode(' ', microtime());
        return (float)$time[1] + (float)$time[0];
    }

    protected function generateCurrentJob()
    {
        $class = explode('\\', static::class);
        return end($class);
    }

    /**
     * @param string $service
     *
     * @return object|null
     */
    protected function get($service)
    {
        /** @var Plugin|null $plugin */
        $plugin = WPStaging::getInstance()->get(Plugin::class);
        if (!$plugin) {
            return null;
        }

        return $plugin->getContainer()->get($service);
    }
}
