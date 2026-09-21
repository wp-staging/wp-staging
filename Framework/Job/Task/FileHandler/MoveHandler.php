<?php

namespace WPStaging\Framework\Job\Task\FileHandler;

use WPStaging\Core\WPStaging;




class MoveHandler extends FileHandler
{





    public function handle($source, $destination)
    {
        $parentDirectory = dirname($destination);

        if (!is_dir($parentDirectory)) {
            $parentDirectoryCreated = wp_mkdir_p($parentDirectory);

            if (!$parentDirectoryCreated) {
                $this->logger->warning(sprintf(
                    __('%s: Parent directory of destination did not exist and could not be created, skipping! Parent directory: %s File that was skipped: %s', 'wp-staging'),
                    call_user_func([$this->fileTask, 'getTaskTitle']),
                    $parentDirectory,
                    $destination
                ));

                return;
            }
        }

        $this->lock($source);
        $moved = @rename($source, $destination);
        if (!$moved) {
            $moved = $this->filesystem->moveFileOrDir($source, $destination);
        }

        $this->unlock();

        if (!$moved) {
            $relativeSourcePathForLogging      = $this->filesystem->getPathRelativeToAbspath($source);
            $relativeDestinationPathForLogging = $this->filesystem->getPathRelativeToAbspath($destination);

            $message   = 'Maybe a file permission issue?';
            $lastError = error_get_last();
            if (!empty($lastError['message']) && substr($lastError['message'], 0, 7) === 'rename(') {
                $message = preg_replace('@^rename.*?:\s+@', '', $lastError['message']);
                $message = $this->redactInstallationRoot($message, ABSPATH, WPStaging::isWindowsOs());
            }

            $this->logger->warning(sprintf(
                __('%s: Could not move "%s" to "%s". %s.', 'wp-staging'),
                call_user_func([$this->fileTask, 'getTaskTitle']),
                $relativeSourcePathForLogging,
                $relativeDestinationPathForLogging,
                $message
            ));
        }
    }









    protected function redactInstallationRoot(string $message, string $rootPath, bool $isWindows): string
    {
        $rootPath = rtrim($isWindows ? str_replace('\\', '/', $rootPath) : $rootPath, '/');
        if ($rootPath === '') {
            return $message;
        }

        $rootPattern = preg_quote($rootPath, '~');
        $separator   = $isWindows ? '[/\\\\]' : '/';
        if ($isWindows) {
            $rootPattern = str_replace('/', $separator, $rootPattern);
        }

        return preg_replace('~' . $rootPattern . '(?=' . $separator . '|[\s):;\'\"]|$)~' . ($isWindows ? 'i' : ''), '[ABSPATH]', $message);
    }
}
