<?php

namespace WPStaging\Backend\Modules\Views\Forms;

use WPStaging\Core\Forms\Elements\Color;
use WPStaging\Core\Forms\Elements\Numerical;
use WPStaging\Core\Forms\Elements\Select;
use WPStaging\Core\Forms\Elements\SelectMultiple;
use WPStaging\Core\Forms\Elements\Text;
use WPStaging\Core\Forms\Elements\Toggle;
use WPStaging\Core\Forms\Form;
use WPStaging\Backend\Modules\Views\Tabs\Tabs;
use WPStaging\Framework\Assets\Assets;
use WPStaging\Framework\Facades\Hooks;
use WPStaging\Framework\Job\Dto\JobDataDto;

/**
 * Builds and manages the settings form for WP Staging plugin configuration
 *
 * This class generates the settings form structure for the WordPress admin interface.
 * It creates form elements for various plugin settings including:
 * - Database copy and search/replace query limits
 * - File copy limits and batch sizes
 * - CPU load priority and request delays
 * - Feature toggles (optimizer, debug mode, compression)
 * - User access permissions and role management
 * - Admin bar customization
 *
 * The class dynamically builds forms based on available tabs and handles both
 * free and pro version settings appropriately.
 */
class Settings
{

    /**
     * @var array
     */
    private $form = [];

    /**
     * @var Tabs
     */
    private $tabs;

    /**
     * Settings constructor.
     * @param Tabs $tabs
     */
    public function __construct($tabs)
    {
        $this->tabs = $tabs;

        foreach ($this->tabs->get() as $id => $name) {
            if (!method_exists($this, $id)) {
                continue;
            }

            $this->{$id}();
        }
    }

    private function general()
    {
        $this->form["general"] = new Form();

        $settings = json_decode(json_encode(get_option("wpstg_settings", [])));

       // DB Copy Query Limit
        $element = new Numerical(
            "wpstg_settings[queryLimit]",
            [
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0,
            ]
        );

        $this->form["general"]->add(
            $element->setLabel(__("DB Copy Query Limit", "wp-staging"))
            ->setDefault(isset($settings->queryLimit) ? $settings->queryLimit : 10000),
            'wpstg-settings-query-limit'
        );
       // DB Search & Replace Query Limit
        $element = new Numerical(
            "wpstg_settings[querySRLimit]",
            [
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0,
            ]
        );

        $this->form["general"]->add(
            $element->setLabel(__("DB Search & Replace Limit", "wp-staging"))
            ->setDefault(isset($settings->querySRLimit) ? $settings->querySRLimit : 5000),
            'wpstg-settings-query-sr-limit'
        );

        $options = ['1' => '1', '10' => '10', '50' => '50', '250' => '250', '500' => '500', '1000' => '1000'];
       // DB Copy Query Limit
        $element = new Select(
            "wpstg_settings[fileLimit]",
            $options,
            [
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0,
            ]
        );

        $defaultFileLimit = (defined('WPSTG_IS_DEV') && WPSTG_IS_DEV || defined('WPSTG_TEST') && WPSTG_TEST) ? 500 : 50;

        $this->form["general"]->add(
            $element->setLabel(__("File Copy Limit", "wp-staging"))
            ->setDefault(isset($settings->fileLimit) ? $settings->fileLimit : $defaultFileLimit),
            'wpstg-settings-file-limit'
        );


       // File Copy Batch Size
        $element = new Numerical(
            "wpstg_settings[maxFileSize]",
            [
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0,
            ]
        );

        $this->form["general"]->add(
            $element->setLabel(__("Maximum File Size (MB)", "wp-staging"))
            ->setDefault(isset($settings->maxFileSize) ? $settings->maxFileSize : 8),
            'wpstg-settings-max-file-size'
        );

       // File Copy Batch Size
        $element = new Numerical(
            "wpstg_settings[batchSize]",
            [
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 999999,
                "min"   => 0,
            ]
        );

        $this->form["general"]->add(
            $element->setLabel(__("File Copy Batch Size", "wp-staging"))
            ->setDefault(isset($settings->batchSize) ? $settings->batchSize : 2),
            'wpstg-settings-batch-size'
        );

       // CPU load priority
        $element = new Select(
            "wpstg_settings[cpuLoad]",
            [
                "high"   => __("High", "wp-staging"),
                "medium" => __("Medium", "wp-staging"),
                "low"    => __("Low", "wp-staging"),
            ]
        );

        $defaultCpuPriority = defined('WPSTG_IS_DEV') && WPSTG_IS_DEV ? 'high' : 'low';

        $this->form["general"]->add(
            $element->setLabel(__("CPU Load Priority", "wp-staging"))
            ->setDefault(isset($settings->cpuLoad) ? $settings->cpuLoad : $defaultCpuPriority),
            'wpstg-settings-cpu-load'
        );

       // Delay Between Requests
        $element = new Numerical(
            "wpstg_settings[delayRequests]",
            [
                "class" => "medium-text",
                "step"  => 1,
                "max"   => 5,
                "min"   => 0,
            ]
        );

        $this->form["general"]->add(
            $element->setLabel(__("Delay Between Requests", "wp-staging"))
            ->setDefault((isset($settings->delayRequests)) ? $settings->delayRequests : 0),
            'wpstg-settings-delay-requests'
        );


       // Optimizer
        $element = new Toggle(
            "wpstg_settings[optimizer]",
            ['1' => ""]
        );

        $this->form["general"]->add(
            $element->setLabel(__("Optimizer", "wp-staging"))
            ->setDefault((isset($settings->optimizer)) ? $settings->optimizer : null),
            'wpstg-settings-optimizer'
        );


        // Disable admin authorization
        if (!defined('WPSTGPRO_VERSION')) {
            $element = new Toggle(
                "wpstg_settings[disableAdminLogin]",
                ['1' => '']
            );

            $this->form["general"]->add(
                $element->setLabel(__("Disable admin authorization", "wp-staging"))
                ->setDefault((isset($settings->disableAdminLogin)) ? $settings->disableAdminLogin : null),
                'wpstg-settings-disable-admin-login'
            );
        }

        // Keep permalinks
        if (defined('WPSTGPRO_VERSION')) {
            $element = new Toggle(
                "wpstg_settings[keepPermalinks]",
                ['1' => '']
            );

            $this->form["general"]->add(
                $element->setLabel(__("Keep Permalinks", "wp-staging"))
                ->setDefault((isset($settings->keepPermalinks)) ? $settings->keepPermalinks : null),
                'wpstg-settings-keep-permalinks'
            );
        }

       // Debug Mode
        $element = new Toggle(
            "wpstg_settings[debugMode]",
            ['1' => '']
        );

        $this->form["general"]->add(
            $element->setLabel(__("Debug Mode", "wp-staging"))
            ->setDefault((isset($settings->debugMode)) ? $settings->debugMode : null),
            'wpstg-settings-debug-mode'
        );

       // Remove Data on Uninstall?
        $element = new Toggle(
            "wpstg_settings[unInstallOnDelete]",
            ['1' => '']
        );

        $this->form["general"]->add(
            $element->setLabel(__("Remove Data on Uninstall?", "wp-staging"))
            ->setDefault((isset($settings->unInstallOnDelete)) ? $settings->unInstallOnDelete : null),
            'wpstg-settings-uninstall-on-delete'
        );

        // Get user roles
        if (defined('WPSTGPRO_VERSION')) {
            $element = new SelectMultiple('wpstg_settings[userRoles][]', $this->getUserRoles());
            $this->form["general"]->add(
                $element->setLabel(__("Access Permissions", "wp-staging"))
                ->setDefault((isset($settings->userRoles)) ? $settings->userRoles : 'administrator'),
                'wpstg-settings-access-permissions'
            );

            $usersWithStagingAccess = new Text('wpstg_settings[usersWithStagingAccess]', []);
            $this->form["general"]->add(
                $usersWithStagingAccess->setLabel(__("Users With Staging Access", "wp-staging"))
                ->setDefault(isset($settings->usersWithStagingAccess) ? $settings->usersWithStagingAccess : ''),
                'wpstg-settings-users-with-staging-access'
            );
        }

        $element = new Color(
            "wpstg_settings[adminBarColor]",
            []
        );

        $this->form["general"]->add(
            $element->setLabel(__("Admin Bar Background Color", "wp-staging"))
            ->setDefault((isset($settings->adminBarColor)) ? $settings->adminBarColor : Assets::DEFAULT_ADMIN_BAR_BG),
            'wpstg-settings-admin-bar-color'
        );

        // Compress Backups
        if (defined('WPSTGPRO_VERSION')) {
            $element = new Toggle(
                "wpstg_settings[enableCompression]",
                ['1' => '']
            );

            $isMultiPartEnabled = Hooks::applyFilters(JobDataDto::FILTER_IS_MULTIPART_BACKUP, false);

            if (!$isMultiPartEnabled) {
                $this->form["general"]->add(
                    $element->setLabel(__("Compress Backups", "wp-staging"))
                    ->setDefault((isset($settings->enableCompression)) ? $settings->enableCompression : null),
                    'wpstg-settings-enable-compression'
                );
            } else {
                $this->form["general"]->add(
                    $element->setLabel(__("Compress Backups (Incompatible with Multipart Backups)", "wp-staging"))
                    ->setAttribute('disabled', 'disabled')
                    ->setDefault(''),
                    'wpstg-settings-enable-compression'
                );
            }
        }
    }

    /**
     * Get available user Roles
     * @return array
     */
    private function getUserRoles()
    {
        $userRoles = [];
        foreach (get_editable_roles() as $key => $value) {
            $userRoles[$key] = $key;
        }

        return array_merge(['all' => __('Allow access from all visitors', 'wp-staging')], $userRoles);
    }

    /**
     * @param string $name
     * @return array|Form
     */
    public function get($name = null)
    {
        return ($name === null) ? $this->form : $this->form[$name];
    }
}
