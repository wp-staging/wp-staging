<?php

namespace WPStaging\Bootstrap\V1;

if (!class_exists(WpstgProRequirements::class)) {
    require_once 'WpstgRequirements.php';

    class WpstgProRequirements extends WpstgRequirements
    {
        public function checkRequirements()
        {
            $this->anotherInstanceOfWpstagingMustNotBeEnabled();
        }

        /**
         * Catch-all to prevent conflicts when another instance of WPSTAGING is active.
         */
        private function anotherInstanceOfWpstagingMustNotBeEnabled()
        {
            $oldVersionsLoaded       = defined('WPSTG_PLUGIN_DIR') || defined('WPSTG_PLUGIN_FILE');
            $anotherProVersionLoaded = defined('WPSTG_PRO_LOADED') && $this->pluginFile !== WPSTG_PRO_LOADED;

            if ($oldVersionsLoaded || $anotherProVersionLoaded) {
                $this->notificationMessage = __('Another instance of WP STAGING is activated, therefore other instances of WP STAGING were automatically prevented from running to avoid errors. Please leave only one instance of the WP STAGING plugin active.', 'wp-staging');

                if (is_network_admin() && current_user_can('manage_network_plugins')) {
                    add_action('network_admin_notices', [$this, '_displayWarning']);
                } elseif (!is_network_admin() && current_user_can('activate_plugins')) {
                    add_action('admin_notices', [$this, '_displayWarning']);
                } elseif (!is_network_admin() && !current_user_can('activate_plugins')) {
                    $this->notificationMessage = __('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Please ask the site administrator to leave only one instance of WP STAGING active.', 'wp-staging');
                    add_action('admin_notices', [$this, '_displayWarning']);
                }
                if (defined('WPSTG_DEBUG') && WPSTG_DEBUG === true){
                    throw new \RuntimeException(sprintf("Another instance of WP STAGING is activated, therefore other instances of WP STAGING were automatically prevented from running to avoid errors. Please leave only one instance of the WP STAGING plugin active. Plugin that was prevented from loading: %s", $this->pluginFile));
                }
            }
        }
    }
}
