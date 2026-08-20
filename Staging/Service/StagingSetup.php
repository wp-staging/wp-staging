<?php

namespace WPStaging\Staging\Service;




class StagingSetup extends AbstractStagingSetup
{



    public function renderNetworkCloneSettings()
    {
 
    }

    public function getAdvanceSettingsTitle(): string
    {
        return esc_html__("Advanced Settings (Requires Pro Version)", "wp-staging");
    }




    public function renderAdvanceSettingsHeader()
    {
        echo $this->templateEngine->render('staging/_partials/advance-settings-header.php'); // phpcs:ignore
    }




    public function renderAdvanceSettings(string $name, string $label, string $description, bool $checked = false, string $additionalClasses = '', string $dataId = '', string $summary = '', string $content = '', $tooltip = null)
    {
 
        $this->renderSettings($name, $label, $description, $checked, true, $additionalClasses, $dataId, $summary, $content, $tooltip);
    }




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
            ],
        ];

        $this->renderSettingsFields($fields);
    }




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
            ],
        ];

        $this->renderSettingsFields($fields);
    }







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
                'label'    => esc_html__('Enable SSL: ', 'wp-staging'),
                'name'     => 'wpstg-db-ssl',
                'type'     => 'checkbox',
                'value'    => 'true',
                'checked'  => false,
                'disabled' => true,
            ],
        ];

        $this->renderSettingsFields($fields);
    }




    public function renderEnableWooSchedulerSettings()
    {
 
    }
}
