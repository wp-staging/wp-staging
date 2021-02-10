<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Component\Job;

use WPStaging\Component\Task\AbstractTask;
use WPStaging\Component\Task\TaskInterface;
use WPStaging\Component\Task\TaskResponseDto;
use WPStaging\Framework\Queue\Queue;
use WPStaging\Framework\Queue\Storage\CacheStorage;
use WPStaging\Framework\Utils\Cache\Cache;
use WPStaging\Core\WPStaging;

abstract class AbstractQueueJob extends AbstractJob
{
    /** @var Cache */
    private $jobCache;

    /** @var QueueJobDto */
    protected $dto;

    /** @var Queue */
    protected $queue;

    /**
     * @var CacheStorage $queueCache Controls the cache file for the queue.
     */
    protected $queueCache;

    /** @var TaskInterface */
    protected $currentTask;

    public function __construct(Cache $jobCache, Queue $queue, CacheStorage $queueCache, QueueJobDto $dto)
    {
        $this->queue      = $queue;
        $this->jobCache   = $jobCache;
        $this->queueCache = $queueCache;
        $this->dto        = $dto;

        $this->setUp();
    }

    public function __destruct()
    {
        if ($this->dto->isStatusCheck()) {
            return;
        }

        if ($this->dto->isFinished()) {
            $this->clean();

            return;
        }

        $this->jobCache->save($this->dto->toArray());
    }

    abstract protected function initiateTasks();

    /**
     * @return QueueJobDto
     */
    public function getDto()
    {
        return $this->dto;
    }

    protected function clean()
    {
        parent::clean();
        $this->queue->reset();
    }

    protected function prepare()
    {
        if (method_exists($this, 'provideRequestDto')) {
            $this->provideRequestDto();
        }

        if (method_exists($this, 'injectRequests')) {
            $this->injectRequests();
        }

        $this->saveCurrentStatusTitle();
    }

    protected function setUp()
    {
        $this->setQueue();

        $this->jobCache->setLifetime(HOUR_IN_SECONDS);
        $this->jobCache->setFilename('job_' . $this->getJobName());
        $this->jobCache->setPath(trailingslashit($this->jobCache->getPath() . $this->getJobName()));

        if (!empty($_POST['reset']) && ($_POST['reset'] === true || $_POST['reset'] === 'true')) {
            /** @noinspection DynamicInvocationViaScopeResolutionInspection */
            AbstractJob::clean();
        }

        $this->dto->setInit(true);
        $this->dto->setFinished(false);

        $data = $this->jobCache->get([]);
        if ($data) {
            $this->dto->hydrate($data);
        }

        // TODO RPoC Hack
        $this->dto->setStatusCheck(!empty($_GET['action']) && $_GET['action'] === 'wpstg--snapshots--status');

        if ($this->dto->isStatusCheck()) {
            return;
        }

        if ($this->dto->getId() === null) {
            $this->dto->setId(time());
        }

        if ($this->dto->isInit() && method_exists($this, 'init')) {
            $this->init();
        }

        if ($this->dto->isInit()) {
            $this->queue->reset();
            $this->initiateTasks();
        }

        $this->dto->setInit(false);
        /** @var AbstractTask currentTask */
        $this->currentTask = WPStaging::getInstance()->get($this->queue->pop());
        if (!$this->currentTask) {
            return;
        }
        $this->currentTask->setJobId($this->dto->getId());
        $this->currentTask->setJobName($this->getJobName());
        $this->currentTask->setDebug(defined('WPSTG_DEBUG') && WPSTG_DEBUG);
    }

    protected function setQueue()
    {
        $this->queue->setName($this->findCurrentJob());

        $this->queueCache->getCache()->setPath(
            trailingslashit($this->queueCache->getCache()->getPath() . $this->getJobName())
        );

        $this->queue->setStorage($this->queueCache);
    }

    protected function addTasks(array $tasks = [])
    {
        foreach ($tasks as $task) {
            $this->queue->push($task);
        }
    }

    /**
     * @inheritDoc
     */
    protected function getResponse(TaskResponseDto $response)
    {
        $response = parent::getResponse($response);

        // TODO RPoC below
        // Task is not done yet, add it to beginning of the queue again
        if (!$response->isStatus()) {
            // TODO PHP7.x; $this->currentTask::class;
            $className = get_class($this->currentTask);
            $this->queue->prepend($className);
        }

        if ($response->isStatus() && $this->queue->count() === 0) {
            $this->dto->setFinished(true);

            return $response;
        }

        $response->setStatus(false);

        return $response;
    }

    /**
     * Provides arguments to be used within saveCurrentStatusTitle()
     * In most use cases it will be overwritten but in case it is forgotten, it will not throw error
     * @return array
     */
    protected function findCurrentStatusTitleArgs()
    {
        return [];
    }

    protected function saveCurrentStatusTitle()
    {
        // Might happen when xdebug enabled so saving us some extra reading unnecessary log output
        if (!$this->currentTask) {
            return;
        }

        $args  = $this->findCurrentStatusTitleArgs();
        $title = $this->currentTask->getStatusTitle($args);
        $this->dto->setCurrentStatusTitle($title);
        $this->jobCache->save($this->dto->toArray());
    }
}
