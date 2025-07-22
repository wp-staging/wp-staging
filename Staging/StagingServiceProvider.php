<?php

namespace WPStaging\Staging;

use WPStaging\Framework\Adapter\Database;
use WPStaging\Framework\Adapter\DatabaseInterface;
use WPStaging\Framework\DI\FeatureServiceProvider;
use WPStaging\Framework\Job\Dto\JobDataDto;
use WPStaging\Framework\ThirdParty\MalCare;
use WPStaging\Staging\Ajax\Create;
use WPStaging\Staging\Ajax\Create\PrepareCreate;
use WPStaging\Staging\Ajax\Delete\PrepareDelete;
use WPStaging\Staging\Ajax\Delete;
use WPStaging\Staging\Ajax\Listing;
use WPStaging\Staging\Ajax\Delete\DeleteConfirm;
use WPStaging\Staging\Ajax\Repair;
use WPStaging\Staging\Ajax\Setup;
use WPStaging\Staging\Dto\Job\StagingSiteCreateDataDto;
use WPStaging\Staging\Dto\Job\StagingSiteDeleteDataDto;
use WPStaging\Staging\Jobs\StagingSiteCreate;
use WPStaging\Staging\Jobs\StagingSiteDelete;
use WPStaging\Staging\Tasks\StagingSite\CleanupStagingTablesTask;

class StagingServiceProvider extends FeatureServiceProvider
{
    protected function registerClasses()
    {
        $this->container->when(StagingSiteDelete::class)
                ->needs(JobDataDto::class)
                ->give(StagingSiteDeleteDataDto::class);

        $this->container->when(StagingSiteCreate::class)
                ->needs(JobDataDto::class)
                ->give(StagingSiteCreateDataDto::class);

        $this->container->when(CleanupStagingTablesTask::class)
                ->needs(DatabaseInterface::class)
                ->give(Database::class);
    }

    protected function addHooks()
    {
        $this->enqueueAjaxListeners();
    }

    protected function enqueueAjaxListeners()
    {
        add_action('wp_ajax_wpstg--staging-site--prepare-delete', $this->container->callback(PrepareDelete::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--staging-site--delete-confirmation', $this->container->callback(DeleteConfirm::class, 'ajaxConfirm')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--staging-site--delete', $this->container->callback(Delete::class, 'ajaxDelete')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--staging-site--listing', $this->container->callback(Listing::class, 'ajaxListing')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--staging-site--fix-option', $this->container->callback(Repair::class, 'ajaxFixOption')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--staging-site--report-option', $this->container->callback(Repair::class, 'ajaxReportOption')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action(StagingSiteCreate::ACTION_CLONING_COMPLETE, $this->container->callback(MalCare::class, 'maybeDisableMalCare'));
        $this->enqueueStagingAjaxListeners();
    }

    protected function enqueueStagingAjaxListeners()
    {
        if (!defined('WPSTG_NEW_STAGING') || !WPSTG_NEW_STAGING) {
            return;
        }

        add_action('wp_ajax_wpstg--staging-site--setup', $this->container->callback(Setup::class, 'ajaxSetup')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--staging-site--prepare-create', $this->container->callback(PrepareCreate::class, 'ajaxPrepare')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
        add_action('wp_ajax_wpstg--staging-site--create', $this->container->callback(Create::class, 'render')); // phpcs:ignore WPStaging.Security.AuthorizationChecked
    }
}
