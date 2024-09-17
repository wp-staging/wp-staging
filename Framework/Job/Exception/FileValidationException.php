<?php

/**
 * An exception thrown in the context of the Job execution if validation for a certain file fails
 *
 * @package WPStaging\Framework\Job\Exception
 */

namespace WPStaging\Framework\Job\Exception;

use WPStaging\Framework\Exceptions\WPStagingException;

/**
 * @package WPStaging\Framework\Job\Exception
 */
class FileValidationException extends WPStagingException
{
}
