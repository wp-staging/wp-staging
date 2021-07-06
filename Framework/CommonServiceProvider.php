<?php

namespace WPStaging\Framework;

use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Filesystem\DiskWriteCheck;

/**
 * Class CommonServiceProvider
 *
 * A Service Provider for binds common to both Free and Pro.
 *
 * @package WPStaging\Framework
 */
class CommonServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->singleton(DiskWriteCheck::class);
    }
}
