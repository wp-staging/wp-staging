<?php

namespace WPStaging\Basic\Staging;

use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Staging\Ajax\Setup;
use WPStaging\Staging\Service\AbstractStagingSetup;
use WPStaging\Staging\Service\StagingSetup;

/**
 * Class StagingServiceProvider
 *
 * Responsible for injecting classes which are to be used in FREE/BASIC version only
 */
class StagingServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->when(Setup::class)
                ->needs(AbstractStagingSetup::class)
                ->give(StagingSetup::class);
    }
}
