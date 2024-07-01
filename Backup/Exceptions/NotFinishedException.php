<?php

/**
 * An exception thrown in the context of the Backup execution when something is not finished
 *
 * @package WPStaging\Backup\Exceptions
 */

namespace WPStaging\Backup\Exceptions;

use WPStaging\Framework\Exceptions\WPStagingException;

/**
 * Class NotFinishedException
 * Use this exception, when a process is not finished
 * @package WPStaging\Backup\Exceptions
 */
class NotFinishedException extends WPStagingException
{
}
