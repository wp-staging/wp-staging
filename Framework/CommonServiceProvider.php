<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Analytics\AnalyticsCleanup;
use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Framework\Filesystem\DebugLogReader;
use WPStaging\Framework\Filesystem\DiskWriteCheck;
use WPStaging\Framework\Filesystem\LogCleanup;
use WPStaging\Framework\Utils\DBPermissions;

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
        $this->container->make(DebugLogReader::class)->listenDeleteLogRequest();

        add_action('wpstg_daily_event', [$this, 'cleanupLogs'], 25, 0);
        add_action('wpstg_daily_event', [$this, 'cleanupAnalytics'], 25, 0);
        add_action("wp_ajax_wpstg_check_user_permissions", $this->container->callback(DBPermissions::class, 'ajaxCheckDBPermissions'));
    }

    public function cleanupLogs()
    {
        $this->container->make(LogCleanup::class)->cleanOldLogs();
    }

    public function cleanupAnalytics()
    {
        $this->container->make(AnalyticsCleanup::class)->cleanupOldAnalytics();
    }
}
