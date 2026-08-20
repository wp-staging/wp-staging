<?php







namespace WPStaging\Core;

use WPStaging\Core\Utils\Logger;
use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\DatabaseInterface;
use WPStaging\Framework\BackgroundProcessing\BackgroundProcessingServiceProvider;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Notices\NoticesHandler;
use WPStaging\Vendor\Psr\Log\LoggerInterface;






class CoreServiceProvider extends ServiceProvider
{






    public function register()
    {
        $this->registerEarlyBindings();
    }





    private function registerEarlyBindings()
    {
        $this->container->bind(LoggerInterface::class, Logger::class);
        $this->container->bind(DatabaseInterface::class, Database::class);
        $this->container->make(NoticesHandler::class);
        $this->container->setVar("database", $this->container->make(DatabaseInterface::class));
    }




    public function boot()
    {
        $this->container->register(BackgroundProcessingServiceProvider::class);
    }
}
