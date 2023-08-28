<?php

namespace WPStaging\Backup\Task\RestoreFileHandlers;

class MoveHandler extends RestoreFileHandler
{

    /**
     * @param string $source
     * @param string $destination
     * @return void
     */
    public function handle($source, $destination)
    {
        $parentDirectory = dirname($destination);

        if (!is_dir($parentDirectory)) {
            $parentDirectoryCreated = wp_mkdir_p($parentDirectory);

            if (!$parentDirectoryCreated) {
                $this->logger->warning(sprintf(
                    __('%s: Parent directory of destination did not exist and could not be created, skipping! Parent directory: %s File that was skipped: %s', 'wp-staging'),
                    call_user_func([$this->fileRestoreTask, 'getTaskTitle']),
                    $parentDirectory,
                    $destination
                ));

                return;
            }
        }

        $this->lock($source);
        $moved = @rename($source, $destination);
        $this->unlock();

        if (!$moved) {
            $relativeSourcePathForLogging      = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $source);
            $relativeDestinationPathForLogging = str_replace($this->filesystem->normalizePath(ABSPATH, true), '', $destination);

            $message   = 'Maybe a file permission issue?';
            $lastError = error_get_last();
            if (!empty($lastError) && !empty($lastError['message'])) {
                if (substr($lastError['message'], 0, 7) === 'rename(') {
                    $message = preg_replace('@^rename.*?:\s+@', '', $lastError['message']);
                }
            }

            $this->logger->warning(sprintf(
                __('%s: Could not move "%s" to "%s". %s.', 'wp-staging'),
                call_user_func([$this->fileRestoreTask, 'getTaskTitle']),
                $relativeSourcePathForLogging,
                $relativeDestinationPathForLogging,
                $message
            ));
        }
    }
}
