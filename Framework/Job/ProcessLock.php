<?php

namespace WPStaging\Framework\Job;

use RuntimeException;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Traits\ResourceTrait;

class ProcessLock
{
    use ResourceTrait;

    const LOCK_FILE_NAME = '.wpstg_process_locked';

    /** @var string */
    private $lockFile;

    /**
     * The open, flock()ed handle of the lock this request holds, or null when it holds none.
     *
     * It is static so that every ProcessLock instance of a request shares one lock identity, and it
     * stays open for as long as the lock is held: the kernel releases an flock() when the last
     * descriptor referring to it is closed, which PHP does at the end of the request - including
     * when the request dies from a fatal error, a timeout or a killed worker. The lock therefore
     * lives exactly as long as the process that owns it, so a crashed owner never blocks anyone.
     *
     * @var resource|null
     */
    private static $handle = null;

    public function __construct()
    {
        $this->lockFile = trailingslashit(WPStaging::getContentDir()) . self::LOCK_FILE_NAME;
    }

    /**
     * Acquire the global process lock for the current request.
     *
     * The lock is an exclusive, non-blocking flock() on an open descriptor. When two workers race -
     * e.g. the background processing loopback and a wp-cron request spawned milliseconds apart - the
     * kernel hands the lock to exactly one of them, so they can never both run the same job. The
     * previous check-then-file_put_contents() implementation let both "acquire" and corrupt it.
     *
     * @throws ProcessLockedException When another live request already holds the lock.
     * @throws RuntimeException       When the lock file cannot be created at all.
     * @return void
     */
    public function lockProcess()
    {
        // Re-entrant: this request already holds the lock, e.g. a background worker running several
        // job steps within one request.
        if (self::$handle !== null) {
            return;
        }

        $handle = $this->openLockFile();

        if (!$this->acquireHandle($handle)) {
            fclose($handle);

            throw ProcessLockedException::processAlreadyLocked();
        }

        $this->writeLockedAt($handle);

        self::$handle = $handle;
    }

    /**
     * Release the lock held by this request. Never touches a lock held by another request.
     *
     * @return void
     */
    public function unlockProcess()
    {
        $handle = self::$handle;

        if ($handle === null) {
            return;
        }

        self::$handle = null;

        // The file is emptied, never unlinked. Deleting it would let a contender that already
        // decided the old lock was stale unlink the file out from under the next owner's lock,
        // leaving two winners on two different inodes.
        ftruncate($handle, 0);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * Tell whether another live request holds the lock, without keeping it.
     *
     * @param int|null $timeout Staleness timeout in seconds, used only as a fallback on filesystems
     *                          that cannot lock. Null derives it from the request time limit.
     * @throws ProcessLockedException When another live request holds the lock.
     * @return void
     */
    public function checkProcessLocked($timeout = null)
    {
        if (self::$handle !== null) {
            return;
        }

        $handle     = $this->openLockFile();
        $wouldBlock = 0;

        // A shared lock answers "is somebody holding this exclusively?" without making concurrent
        // readers wait for each other. It is dropped again immediately.
        if (flock($handle, LOCK_SH | LOCK_NB, $wouldBlock)) {
            flock($handle, LOCK_UN);
            fclose($handle);

            return;
        }

        fclose($handle);

        if ($wouldBlock || !$this->isLockRecordStale($timeout)) {
            throw ProcessLockedException::processAlreadyLocked();
        }
    }

    /**
     * @param resource $handle
     * @return bool True when this request now owns the lock.
     */
    private function acquireHandle($handle): bool
    {
        $wouldBlock = 0;

        if (flock($handle, LOCK_EX | LOCK_NB, $wouldBlock)) {
            return true;
        }

        // $wouldBlock is set only when the lock was refused because somebody else holds it. Any
        // other failure means the filesystem cannot lock at all (an NFS mount without a lock
        // daemon, an exotic stream); treating that as contention would wedge the plugin forever, so
        // fall back to the timestamp record - best effort, but never worse than no lock at all.
        if ($wouldBlock) {
            return false;
        }

        return $this->isLockRecordStale();
    }

    /**
     * Record when the lock was taken. Only the fallback path above reads it back, so a record that
     * cannot be written fully is cleared rather than left half-written: the flock() is what actually
     * guarantees exclusion.
     *
     * @param resource $handle
     * @return void
     */
    private function writeLockedAt($handle)
    {
        $lockedAt = (string)time();

        ftruncate($handle, 0);
        rewind($handle);

        if (fwrite($handle, $lockedAt) !== strlen($lockedAt)) {
            ftruncate($handle, 0);
        }

        fflush($handle);
    }

    /**
     * @param int|null $timeout
     * @return bool True when no owner recorded itself recently, so the lock can be taken over.
     */
    private function isLockRecordStale($timeout = null): bool
    {
        if (is_null($timeout)) {
            $timeout = min(120, $this->getTimeLimit());
        }

        if (!file_exists($this->lockFile)) {
            return true;
        }

        $lockedAt = file_get_contents($this->lockFile);

        // An empty record is a released lock, a non-numeric one is a corrupt lock. Neither may wedge
        // the queue.
        if (!is_numeric($lockedAt)) {
            return true;
        }

        return (int)$lockedAt < time() - $timeout;
    }

    /**
     * @throws RuntimeException When the lock file cannot be opened or created.
     * @return resource
     */
    private function openLockFile()
    {
        // fopen() warns when the path is not writable; capture that with a scoped error handler
        // instead of the @ operator (project code style rule).
        $error = '';
        set_error_handler(function ($errno, $errstr) use (&$error) {
            $error = $errstr;

            return true;
        });

        // 'c+' creates the file when it is missing and never truncates it, so opening the file can
        // neither disturb a lock another request holds on it nor publish a half-initialized record.
        $handle = fopen($this->lockFile, 'c+');

        restore_error_handler();

        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open the process lock file %s. %s', $this->lockFile, $error));
        }

        return $handle;
    }
}
