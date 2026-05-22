<?php

namespace WPStaging\Backend\DashboardWidget;

use WPStaging\Framework\DI\ServiceProvider;

/**
 * Registers the WP Staging admin dashboard widget on `wp_dashboard_setup`.
 */
class DashboardWidgetServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->singleton(DashboardWidget::class);
    }

    protected function addHooks()
    {
        add_action('wp_dashboard_setup', $this->container->callback(DashboardWidget::class, 'register'));
    }
}
