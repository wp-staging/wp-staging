<?php

namespace WPStaging\Framework;

use WPStaging\Framework\Settings\Settings;
use WPStaging\Framework\DI\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    protected function registerClasses()
    {
        $this->container->singleton(Settings::class);
    }

    protected function addHooks()
    {
        add_action('admin_init', $this->container->callback(Settings::class, 'registerSettings'), 100, 1);
        add_action('wp_ajax_wpstg_purge_queue_table', $this->container->callback(Settings::class, 'ajaxPurgeQueueTable'), 100, 1); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_http_auth_ping', $this->container->callback(Settings::class, 'ajaxHttpAuthPing'), 100, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_nopriv_wpstg_http_auth_ping', $this->container->callback(Settings::class, 'ajaxHttpAuthPing'), 100, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg_test_http_auth', $this->container->callback(Settings::class, 'ajaxTestHttpAuth'), 100, 0); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
