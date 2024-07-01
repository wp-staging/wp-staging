<?php

/**
 * An exception thrown in the context of the Backup execution when we want to skip something.
 *
 * @package WPStaging\Backup\Exceptions
 */

namespace WPStaging\Backup\Exceptions;

use WPStaging\Framework\Exceptions\WPStagingException;

/**
 * Class BackupSkipItemException
 * Use this exception, when you want to skip an item/process/task/step/file in the backup process.
 * @package WPStaging\Backup\Exceptions
 */
class BackupSkipItemException extends WPStagingException
{
}
