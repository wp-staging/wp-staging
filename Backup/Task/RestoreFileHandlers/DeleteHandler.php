<?php

namespace WPStaging\Backup\Task\RestoreFileHandlers;

class DeleteHandler extends RestoreFileHandler
{
    public function handle($source, $destination)
    {
        $this->lock($destination);
        try {
            $deleted = $this->filesystem
                ->setRecursive(true)
                ->setShouldStop(function () {
                    return $this->fileRestoreTask->isThreshold();
                })
                ->delete($destination, true, true);
        } catch (\Exception $e) {
            $this->unlock();
            $this->logger->warning(sprintf(
                __('%s: PHP does not have permission to delete %s! This folder might still be in your filesystem, please clear it manually.', 'wp-staging'),
                call_user_func([$this->fileRestoreTask, 'getTaskTitle']),
                $destination
            ));

            return;
        }

        $this->unlock();

        // Don't enqueue if the folder is empty
        if (!$deleted && $this->filesystem->isEmptyDir($destination)) {
            $this->logger->warning(sprintf(
                __('%s: PHP does not have permission to delete %s! This folder might still be in your filesystem, please clear it manually.', 'wp-staging'),
                call_user_func([$this->fileRestoreTask, 'getTaskTitle']),
                $destination
            ));

            return;
        }

        if (!$deleted) {
            $this->fileRestoreTask->retryLastActionInNextRequest();
            $this->logger->debug(sprintf(
                __('%s: %s could not be entirely deleted in this request. Retrying it again...', 'wp-staging'),
                call_user_func([$this->fileRestoreTask, 'getTaskTitle']),
                $destination
            ));
        }
    }
}
