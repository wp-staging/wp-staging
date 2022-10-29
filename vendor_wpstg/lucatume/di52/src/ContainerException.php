<?php

/**
 * An exception thrown while trying to build or resolve a binding in the container.
 *
 * @package lucatume\DI52
 */
namespace WPStaging\Vendor\lucatume\DI52;

use WPStaging\Vendor\Psr\Container\ContainerExceptionInterface;
/**
 * Class ContainerException
 *
 * @package lucatume\DI52
 */
class ContainerException extends \Exception implements \WPStaging\Vendor\Psr\Container\ContainerExceptionInterface
{
}
