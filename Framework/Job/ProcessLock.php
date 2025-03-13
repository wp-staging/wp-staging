<?php

namespace WPStaging\Framework\Job;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Traits\ResourceTrait;

class ProcessLock
{
    use ResourceTrait;

    const LOCK_FILE_NAME = '.wpstg_process_locked';

    private $lockFile;

    public function __construct()
    {
        $this->lockFile = trailingslashit(WPStaging::getContentDir()) . self::LOCK_FILE_NAME;
    }

    /**
     * @throws ProcessLockedException When the process is locked.
     * @return void
     */
    public function lockProcess()
    {
        $this->checkProcessLocked();
        file_put_contents($this->lockFile, time());
    }

    /**
     * @return void
     */
    public function unlockProcess()
    {
        if (!file_exists($this->lockFile)) {
            return;
        }

        unlink($this->lockFile);
    }

    /**
     * @param int|null $timeout The timeout, in seconds, to lock the process. Leave null to automatically set one.
     * @return void
     *
     * @throws ProcessLockedException When the process is locked.
     */
    public function checkProcessLocked($timeout = null)
    {
        if (is_null($timeout)) {
            $timeout = min(120, $this->getTimeLimit());
        }

        if (!file_exists($this->lockFile)) {
            return;
        }

        $processLocked = file_get_contents($this->lockFile);

        if (!$processLocked) {
            return;
        }

        if (!is_numeric($processLocked)) {
            $this->unlockProcess();

            return;
        }

        /*
         * Something is locking the process.
         *
         * Let's make sure the lock was placed in the last couple minutes, or else we unstuck it,
         * as a task is not supposed to run for this long (at least not in web requests).
         *
         * A process can get stuck when a Job fails to shutdown gracefully, for instance.
         */
        if ($processLocked < time() - $timeout) {
            $this->unlockProcess();

            return;
        }

        throw ProcessLockedException::processAlreadyLocked();
    }
}
