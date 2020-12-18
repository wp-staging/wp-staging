<?php

// If child class has `init` method, it will be executed with constructor to prepare the class for job execution
// Such as setting -if needed- total steps, current step etc.

namespace WPStaging\Component\Job;

use WPStaging\Component\Task\TaskResponseDto;
use WPStaging\Framework\Adapter\Directory;
use WPStaging\Framework\Filesystem\Filesystem;
use WPStaging\Core\WPStaging;

abstract class AbstractJob implements JobInterface
{
    /**
     * @return object
     */
    abstract public function execute();

    /**
     * @return string
     */
    abstract public function getJobName();

    /**
     * @param TaskResponseDto $response
     * @return TaskResponseDto
     */
    protected function getResponse(TaskResponseDto $response)
    {
        $response->setJob(substr($this->findCurrentJob(), 3));
        return $response;
    }

    protected function findCurrentJob()
    {
        $class = explode('\\', static::class);
        return end($class);
    }

    /**
     * Clean anything that is left over from executing the job
     * @return void
     */
    protected function clean()
    {
        /** @var Directory $directory */
        $directory = WPStaging::getInstance()->get(Directory::class);
        (new Filesystem)->delete($directory->getCacheDirectory() . $this->getJobName());
    }

    /**
     * @param string $notation
     * @param mixed $value
     */
    protected function injectTaskRequest($notation, $value)
    {
        if (!isset($_POST['wpstg']) || !is_array($_POST['wpstg'])) {
            $_POST['wpstg'] = [];
        }

        if (!isset($_POST['wpstg']['tasks']) || !is_array($_POST['wpstg']['tasks'])) {
            $_POST['wpstg']['tasks'] = [];
        }

        $data = &$_POST['wpstg']['tasks'];
        $keys = explode('.', $notation);
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = [];
            }
            $data = &$data[$key];
        }

        if (!is_array($value)) {
            $data = $value;
            return;
        }

        if (!is_array($data)) {
            $data = [];
        }

        $data = array_merge($data, $value);
    }
}
