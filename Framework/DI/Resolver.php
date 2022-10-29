<?php

namespace WPStaging\Framework\DI;

use WPStaging\Framework\Interfaces\ShutdownableInterface;

class Resolver extends \WPStaging\Vendor\lucatume\DI52\Builders\Resolver
{
    /**
     * Allows to enqueue the ShutdownableInterface hook
     * on classes resolved by the DI container, such as
     * dependencies injected in the __construct.
     */
    public function resolve($id, array $buildLine = null)
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
