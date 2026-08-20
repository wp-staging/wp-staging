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

 
    private $lockFile;












    private static $handle = null;

    public function __construct()
    {
        $this->lockFile = trailingslashit(WPStaging::getContentDir()) . self::LOCK_FILE_NAME;
    }













    public function lockProcess()
    {
 
 
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






    public function unlockProcess()
    {
        $handle = self::$handle;

        if ($handle === null) {
            return;
        }

        self::$handle = null;

 
 
 
        ftruncate($handle, 0);
        flock($handle, LOCK_UN);
        fclose($handle);
    }















    public function checkProcessLocked($timeout = null)
    {
        if (self::$handle !== null) {
            return;
        }

        $handle     = $this->openLockFile();
        $wouldBlock = 0;

 
 
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





    private function acquireHandle($handle): bool
    {
        $wouldBlock = 0;

        if (flock($handle, LOCK_EX | LOCK_NB, $wouldBlock)) {
            return true;
        }

 
 
 
 
        if ($wouldBlock) {
            return false;
        }

        return $this->isLockRecordStale();
    }









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





    private function isLockRecordStale($timeout = null): bool
    {
        if (is_null($timeout)) {
            $timeout = min(120, $this->getTimeLimit());
        }

        if (!file_exists($this->lockFile)) {
            return true;
        }

        $lockedAt = file_get_contents($this->lockFile);

 
 
        if (!is_numeric($lockedAt)) {
            return true;
        }

        return (int)$lockedAt < time() - $timeout;
    }





    private function openLockFile()
    {
 
 
        $error = '';
        set_error_handler(function ($errno, $errstr) use (&$error) {
            $error = $errstr;

            return true;
        });

 
 
        $handle = fopen($this->lockFile, 'c+');

        restore_error_handler();

        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open the process lock file %s. %s', $this->lockFile, $error));
        }

        return $handle;
    }
}
