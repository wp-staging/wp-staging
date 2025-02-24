<?php

namespace WPStaging\Staging\Service;

/**
 * @package WPStaging\Staging\Service
 */
class StagingSetup extends AbstractStagingSetup
{
    const JOB_NEW_STAGING_SITE = 'new';

    const JOB_UPDATE = 'update';

    const JOB_RESET = 'reset';

    /**
     * @return void
     */
    public function renderNetworkCloneSettings()
    {
        // no-op for free version
    }

    public function getAdvanceSettingsTitle(): string
    {
        return esc_html__("Advanced Settings (Requires Pro Version)", "wp-staging");
    }

    /**
     * @return void
     */
    public function renderAdvanceSettingsHeader()
    {
        echo $this->templateEngine->render('staging/_partials/advance-settings-header.php'); // phpcs:ignore
    }

    public function renderAdvanceSettings(string $name, string $label, string $description, bool $checked = false, string $additionalClasses = '', string $dataId = '')
    {
        // We disable the settings by default on FREE version.
        $this->renderSettings($name, $label, $description, $checked, true, $additionalClasses, $dataId);
    }

    /**
     * @return void
     */
    public function renderNewAdminSettings()
    {
        $fields = [
            [
                'label'          => esc_html__('Email: ', 'wp-staging'),
                'name'           => 'wpstg-new-admin-email',
                'type'           => 'email',
                'placeholder'    => '',
                'value'          => '',
                'autocapitalize' => false,
                'disabled'       => true,
            ],
            [
                'label'          => esc_html__('Password: ', 'wp-staging'),
                'name'           => 'wpstg-new-admin-password',
                'type'           => 'password',
                'placeholder'    => '',
                'value'          => '',
                'autocapitalize' => false,
                'autocomplete'   => false,
                'disabled'       => true,
            ]
        ];

        $this->renderSettingsFields($fields);
    }

    /**
     * @return void
     */
    public function renderCustomDirectorySettings()
    {
        $fields = [
            [
                'label'          => esc_html__('Destination Path: ', 'wp-staging'),
                'name'           => 'wpstg_clone_dir',
                'type'           => 'text',
                'placeholder'    => ABSPATH,
                'value'          => '',
                'autocapitalize' => false,
                'disabled'       => true,
            ],
            [
                'label'          => esc_html__('Target Hostname: ', 'wp-staging'),
                'name'           => 'wpstg_clone_hostname',
                'type'           => 'text',
                'placeholder'    => get_site_url(),
                'value'          => '',
                'autocapitalize' => false,
                'disabled'       => true,
            ]
        ];

        $this->renderSettingsFields($fields);
    }

    /**
     * Avoid renaming the 'wpstg-db-user' field to 'wpstg-db-username' or simply 'username',
     * and 'wpstg-db-pass' to 'wpstg-db-password' or 'password'.
     * Renaming may lead to unintended autofill behavior if the fields are disabled.
     * @return void
     */
    public function renderExternalDatabaseSettings()
    {
        $fields = [
            [
                'label'          => esc_html__('Server: ', 'wp-staging'),
                'name'           => 'wpstg-db-server',
                'type'           => 'text',
                'placeholder'    => 'localhost',
                'value'          => '',
                'autocapitalize' => false,
                'disabled'       => true,
            ],
            [
                'label'          => esc_html__('User: ', 'wp-staging'),
                'name'           => 'wpstg-db-user',
                'type'           => 'text',
                'placeholder'    => '',
                'value'          => '',
                'autocapitalize' => false,
                'disabled'       => true,
            ],
            [
                'label'          => esc_html__('Password: ', 'wp-staging'),
                'name'           => 'wpstg-db-pass',
                'type'           => 'password',
                'placeholder'    => '',
                'value'          => '',
                'autocapitalize' => false,
                'autocomplete'   => false,
                'disabled'       => true,
            ],
            [
                'label'          => esc_html__('Database: ', 'wp-staging'),
                'name'           => 'wpstg-db-database',
                'type'           => 'text',
                'placeholder'    => '',
                'value'          => '',
                'autocapitalize' => false,
                'disabled'       => true,
            ],
            [
                'label'          => esc_html__('Database Prefix: ', 'wp-staging'),
                'name'           => 'wpstg-db-prefix',
                'type'           => 'text',
                'placeholder'    => 'wp_',
                'value'          => '',
                'autocapitalize' => false,
                'disabled'       => true,
            ],
            [
                'label'          => esc_html__('Enable SSL: ', 'wp-staging'),
                'name'           => 'wpstg-db-ssl',
                'type'           => 'checkbox',
                'value'          => 'true',
                'checked'        => false,
                'disabled'       => true,
            ]
        ];

        $this->renderSettingsFields($fields);
    }

    /**
     * @return void
     */
    public function renderDisableWooSchedulerSettings()
    {
        // no-op for free version
    }
}
