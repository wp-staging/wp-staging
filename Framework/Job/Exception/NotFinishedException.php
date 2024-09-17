<?php

/**
 * An exception thrown in the context of the Job execution when something is not finished
 *
 * @package WPStaging\Framework\Job\Exception
 */

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Framework\Exceptions\WPStagingException;

/**
 * Class NotFinishedException
 * Use this exception, when a process is not finished
 * @package WPStaging\Framework\Job\Exception
 */
class NotFinishedException extends WPStagingException
{
}
