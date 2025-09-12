<?php

namespace WPStaging\Basic\Staging;

use WPStaging\Framework\DI\ServiceProvider;
use WPStaging\Staging\Ajax\Create;
use WPStaging\Staging\Ajax\Create\PrepareCreate;
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

    protected function addHooks()
    {
        $this->enqueueStagingAjaxListeners();
    }

    protected function enqueueStagingAjaxListeners()
    {
        if (!defined('WPSTG_NEW_STAGING') || !WPSTG_NEW_STAGING) {
            return;
        }

        add_action('wp_ajax_wpstg--staging-site--prepare-create', $this->container->callback(PrepareCreate::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--staging-site--create', $this->container->callback(Create::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
