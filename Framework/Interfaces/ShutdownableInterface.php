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
     * Run before anything else hooked to `shutdown`.
     *
     * The action is a shared bus: one callback calling exit() ends the whole shutdown
     * sequence, and one that fatals abandons the rest of the action, in both cases taking
     * the callbacks behind it with them. Going first means an unrelated plugin cannot
     * starve the write that tells the next request where the work got to.
     *
     * @var int
     */
    const SHUTDOWN_PRIORITY = -10000;

    /**
     * This code will be hooked to the "shutdown" WordPress action.
     *
     * @return void
     */
    public function onWpShutdown();
}
