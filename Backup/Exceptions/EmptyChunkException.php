<?php

/**
 * An exception thrown in the context of the Backup execution during compression for empty chunk
 *
 * @package WPStaging\Backup\Exceptions
 */

namespace WPStaging\Backup\Exceptions;

use WPStaging\Framework\Exceptions\WPStagingException;

/**
 * @package WPStaging\Backup\Exceptions
 */
class EmptyChunkException extends WPStagingException
{
}
