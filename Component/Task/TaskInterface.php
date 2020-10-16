<?php


// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; return types && type-hints

namespace WPStaging\Component\Task;

interface TaskInterface
{

    public function execute();

    /**
     * Set relative cache directory for a task. This is only optional
     * @param string $path
     * @return void
     */
    public function setRelativeCacheDirectory($path);

    /**
     * Set task title, arguments used to feed information to the title such as;
     * sprintf('There are %d directories', $args[0]);
     * @param array $args
     * @return string
     */
    public function getStatusTitle(array $args = []);

    /**
     * Prepares the task for the execution.
     * @return void
     */
    public function prepare();

    /**
     * @return string|null
     */
    public function getJobName();

    /**
     * @param string|null $jobName
     */
    public function setJobName($jobName);

    /**
     * @return string|int|null
     */
    public function getJobId();

    /**
     * @param string|int|null $jobId
     */
    public function setJobId($jobId);
}
