<?php

namespace WPStaging\Framework\Interfaces;

/**
 * Interface ShutdownableInterface
 *
 * @see \WPStaging\Framework\DI\Container::make
 *
 * @package WPStaging\Framework\Interfaces
 */
interface ShutdownableInterface
{
    /**
     * This code will be hooked to the "shutdown" WordPress action.
     *
     * @return void
     */
    public function onWpShutdown();
}
