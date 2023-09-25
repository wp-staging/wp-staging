<?php

namespace WPStaging\Basic;

use WPStaging\Basic\Ajax\ProCronsCleaner;
use WPStaging\Basic\Backup\BackupServiceProvider;
use WPStaging\Basic\Notices\BasicNotices;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Notices\Notices;

/**
 * Class BasicServiceProvider
 *
 * A Service Provider for binds code to just in Free version.
 *
 * @package WPStaging\Basic
 */
class BasicServiceProvider extends ServiceProvider
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
        // This is to tell the container to use the BASIC feature
        $this->container->setVar('WPSTG_PRO', false);

        $this->container->register(BackupServiceProvider::class);
    }
}
