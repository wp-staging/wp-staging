<?php

/**
 * An exception used to signal no binding was found for container ID.
 *
 * @package lucatume\DI52
 */
namespace WPStaging\Vendor\lucatume\DI52;

use WPStaging\Vendor\Psr\Container\NotFoundExceptionInterface;
/**
 * Class NotFoundException
 *
 * @package lucatume\DI52
 */
class NotFoundException extends \WPStaging\Vendor\lucatume\DI52\ContainerException implements \WPStaging\Vendor\Psr\Container\NotFoundExceptionInterface
{
}
