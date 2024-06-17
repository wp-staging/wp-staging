<?php

/**
 * An exception thrown in the context of the Backup execution if validation for a certain file fails
 *
 * @package WPStaging\Backup\Exceptions
 */

namespace WPStaging\Backup\Exceptions;

use WPStaging\Framework\Exceptions\WPStagingException;

/**
 * @package WPStaging\Backup\Exceptions
 */
class FileValidationException extends WPStagingException
{
}
