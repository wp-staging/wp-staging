<?php

namespace WPStaging\Duplicator;

use WPStaging\Duplicator\Ajax\MemoryExhaust;
use WPStaging\Framework\DI\ServiceProvider;

/**
 * Use this class to register common classes and hooks used by backup and cloning process
 */
class DuplicatorServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    protected function addHooks()
    {
        add_action('wp_ajax_wpstg--detect-memory-exhaust', $this->container->callback(MemoryExhaust::class, 'ajaxResponse')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
