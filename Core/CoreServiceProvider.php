<?php

/**
 * Handles the registration of the plugin Core services.
 *
 * @package WPStaging\Core
 */

namespace WPStaging\Core;

use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\DatabaseInterface;
use WPStaging\Framework\BackgroundProcessing\BackgroundProcessingServiceProvider;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Notices\NoticesHandler;
use WPStaging\Vendor\Psr\Log\LoggerInterface;

/**
 * Class CoreServiceProvider
 *
 * @package WPStaging\Core
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register the plugin core Services.
     *
     * @since TBD
     *
     */
    public function register()
    {
        $this->registerEarlyBindings();
    }

    /**
     * Registers a set of bindings and service providers that could be required before
     * booting the service provider.
     */
    private function registerEarlyBindings()
    {
        $this->container->bind(LoggerInterface::class, Logger::class);
        $this->container->bind(DatabaseInterface::class, Database::class);
        $this->container->make(NoticesHandler::class);
        $this->container->setVar("database", $this->container->make(DatabaseInterface::class));
    }

    /**
     * Binds and sets up implementations at boot time.
     */
    public function boot()
    {
        $this->container->register(BackgroundProcessingServiceProvider::class);
    }
}
