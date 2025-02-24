<?php

namespace WPStaging\Basic;

use WPStaging\Basic\Ajax\ProCronsCleaner;
use WPStaging\Basic\Backup\BackupServiceProvider;
use WPStaging\Basic\Notices\BasicNotices;
use WPStaging\Basic\Staging\StagingServiceProvider;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Notices\Notices;

/**
 * Class BootstrapServiceProvider
 *
 * A Service Provider for binds code to just in Free version.
 *
 * @package WPStaging\Basic
 */
class BootstrapServiceProvider extends ServiceProvider
{
    /**
     * Enqueue hooks.
     *
     * @return void
     */
    protected function addHooks()
    {
        add_action("wp_ajax_wpstg_clean_pro_crons", $this->container->callback(ProCronsCleaner::class, 'ajaxCleanProCrons')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action(Notices::BASIC_NOTICES_ACTION, $this->container->callback(BasicNotices::class, 'renderNotices')); // phpcs:ignore WPStaging.Security.FirstArgNotAString, WPStaging.Security.AuthorizationChecked
    }

    protected function registerClasses()
    {
        $this->container->register(BackupServiceProvider::class);
        $this->container->register(StagingServiceProvider::class);
    }
}
