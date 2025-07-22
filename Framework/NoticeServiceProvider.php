<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\DI\ServiceProvider;

class NoticeServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->singleton(Notices::class);
    }

    protected function addHooks()
    {
        add_action(Notices::ACTION_ADMIN_NOTICES, $this->container->callback(Notices::class, 'renderNotices'));
    }
}
