<?php

namespace WPStaging\Framework\DI;

use lucatume\DI52\NotFoundException;
use WPStaging\Framework\Interfaces\ShutdownableInterface;
use WPStaging\Vendor\lucatume\DI52\Builders\Resolver as BaseResolver;

class Resolver extends BaseResolver
{
    /**
     * Allows to enqueue the ShutdownableInterface hook
     * on classes resolved by the DI container, such as
     * dependencies injected in the __construct.
     * @template T
     *
     * @param  string|class-string<T>|mixed  $id         Either the id of a bound implementation, a class name or an
     *                                                   object to resolve.
     * @param  string[]|null                 $buildLine  The build line to append the resolution leafs to, or `null` to
     *                                                   use the current one.
     *
     * @return T|mixed The resolved value or instance.
     * @phpstan-return ($id is class-string ? T : mixed)
     *
     * @throws NotFoundException If the id is a string that is not bound and is not an existing, concrete, class.
     */
    public function resolve($id, $buildLine = null)
    {
        $instance = parent::resolve($id, $buildLine);
        if (is_object($instance) && $instance instanceof ShutdownableInterface) {
            if (!has_action('shutdown', [$instance, 'onWpShutdown'])) {
                add_action('shutdown', [$instance, 'onWpShutdown']);
            }
        }

        return $instance;
    }
}
