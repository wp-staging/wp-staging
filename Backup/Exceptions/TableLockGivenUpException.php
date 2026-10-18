<?php

namespace WPStaging\Backup\Exceptions;




class TableLockGivenUpException extends BackupRuntimeException
{
    const CODE_TABLE_LOCK_GIVEN_UP = 111;






    public static function forTable(string $table, int $attempts): TableLockGivenUpException
    {
        return new self(
            sprintf(
                /* translators: 1: database table name, 2: number of lock requests that timed out */
                __('Table %1$s is held by another query and its lock timed out %2$d times. The backup stopped so the site does not wait behind it; run it again when the site is less busy.', 'wp-staging'),
                $table,
                $attempts
            ),
            self::CODE_TABLE_LOCK_GIVEN_UP
        );
    }






    public static function forRefusedLock(string $table, string $reason): TableLockGivenUpException
    {
        return new self(
            sprintf(
                /* translators: 1: database table name, 2: database lock error */
                __('The backup stopped because table %1$s could not be locked. Database error: %2$s', 'wp-staging'),
                $table,
                $reason
            ),
            self::CODE_TABLE_LOCK_GIVEN_UP
        );
    }
}
