<?php

namespace WPStaging\Framework\Job\Task\FileHandler;




class DeleteHandler extends FileHandler
{
    public function handle($source, $destination)
    {
        $thresholdReached = false;
        $this->lock($destination);
        try {
            $deleted = $this->filesystem
                ->setRecursive(true)
                ->setShouldStop(function () use (&$thresholdReached) {
                    $thresholdReached = $this->fileTask->isThreshold();
                    return $thresholdReached;
                })
                ->delete($destination, true, true);
        } catch (\Exception $e) {
            $this->unlock();
            $this->logger->warning(sprintf(
                __('%s: PHP does not have permission to delete %s! This folder might still be in your filesystem, please clear it manually.', 'wp-staging'),
                call_user_func([$this->fileTask, 'getTaskTitle']),
                $destination
            ));

            return;
        }

        $this->unlock();

        if (!$deleted && !$thresholdReached && $this->filesystem->isEmptyDir($destination)) {
            $this->logger->warning(sprintf(
                __('%s: PHP does not have permission to delete %s! This folder might still be in your filesystem, please clear it manually.', 'wp-staging'),
                call_user_func([$this->fileTask, 'getTaskTitle']),
                $destination
            ));

            return;
        }

        if (!$deleted) {
            $this->fileTask->retryLastActionInNextRequest();
            $this->logger->debug(sprintf(
                __('%s: %s could not be entirely deleted in this request. Retrying it again...', 'wp-staging'),
                call_user_func([$this->fileTask, 'getTaskTitle']),
                $destination
            ));
        }
    }
}
