<?php

namespace WPStaging\Framework\Analytics;

use Throwable;
use WPStaging\Framework\Job\Exception\DiskNotWritableException;
use WPStaging\Framework\Job\Exception\FileValidationException;
use WPStaging\Framework\Job\Exception\ProcessLockedException;
use WPStaging\Framework\Job\Exception\TaskHealthException;
use WPStaging\Framework\Job\Exception\ThresholdException;







class ErrorCode
{
    const UNKNOWN            = 'unknown';
    const DISK_FULL          = 'disk_full';
    const FILE_VALIDATION    = 'file_validation';
    const PROCESS_LOCKED     = 'process_locked';
    const TASK_HEALTH        = 'task_health';
    const THRESHOLD_EXCEEDED = 'threshold_exceeded';
    const MEMORY_EXHAUSTED   = 'memory_exhausted';
    const DB_RESTORE_QUERY   = 'db_restore_query';
    const REQUEST_FAILED     = 'request_failed';





    const DB_RESTORE_QUERY_PREFIX = 'db_restore_query_';

 
    private static $exceptionMap = [
        DiskNotWritableException::class => self::DISK_FULL,
        FileValidationException::class  => self::FILE_VALIDATION,
        ProcessLockedException::class   => self::PROCESS_LOCKED,
        TaskHealthException::class      => self::TASK_HEALTH,
        ThresholdException::class       => self::THRESHOLD_EXCEEDED,
    ];







    public static function fromThrowable(Throwable $throwable): string
    {
        foreach (self::$exceptionMap as $class => $code) {
            if ($throwable instanceof $class) {
                return $code;
            }
        }

        return self::UNKNOWN;
    }





    public static function forDatabaseRestoreQuery(int $mysqlErrorNumber): string
    {
        if ($mysqlErrorNumber <= 0) {
            return self::DB_RESTORE_QUERY;
        }

        return self::DB_RESTORE_QUERY_PREFIX . $mysqlErrorNumber;
    }





    public static function sanitize(string $code): string
    {
        $code = strtolower(preg_replace('/[^A-Za-z0-9_]/', '', $code));

        return $code === '' ? self::UNKNOWN : substr($code, 0, 64);
    }
}
