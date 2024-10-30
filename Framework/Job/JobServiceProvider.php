<?php

namespace WPStaging\Framework\Job;

use WPStaging\Framework\DI\FeatureServiceProvider;
use WPStaging\Framework\Job\Ajax\Status;

class JobServiceProvider extends FeatureServiceProvider
{
    protected function registerClasses()
    {
        // no-op
    }

    protected function addHooks()
    {
        $this->enqueueAjaxListeners();
    }

    protected function enqueueAjaxListeners()
    {
        add_action('wp_ajax_wpstg--job--status', $this->container->callback(Status::class, 'ajaxProcess')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        //add_action('wp_ajax_wpstg--job--cancel', $this->container->callback(Cancel::class, 'ajaxProcess')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
