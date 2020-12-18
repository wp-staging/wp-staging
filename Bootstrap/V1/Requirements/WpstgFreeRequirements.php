<?php

namespace WPStaging\Bootstrap\V1;

if (!class_exists(WpstgFreeRequirements::class)) {
    require_once 'WpstgRequirements.php';

    class WpstgFreeRequirements extends WpstgRequirements
    {
        /** @var string */
        private $proPlugin;

        /** @var string */
        private $freePlugin;

        public function checkRequirements()
        {
            $this->proVersionMustNotBeEnabled();
            $this->anotherInstanceOfWpstagingMustNotBeEnabled();
        }

        /**
         * Prevent conflicts when WP STAGING Pro is active.
         */
        private function proVersionMustNotBeEnabled()
        {
            // Early bail: Pro is not loaded, therefore there's no need for further checks.
            if (!defined('WPSTG_PRO_LOADED')) {
                return;
            }

            $this->proPlugin = plugin_basename(WPSTG_PRO_LOADED);
            $this->freePlugin = plugin_basename($this->pluginFile);

            require_once ABSPATH . 'wp-admin/includes/plugin.php';

            if (is_multisite()) {
                if (is_network_admin()) {
                    $this->deactivateFreePluginOnMultisiteNetworkLevel();
                } else {
                    $this->deactivateFreeOrProPluginOnMultisite();
                }
            } else {
                $this->deactivateFreePluginIfProIsActiveOnSingleSite();
            }
        }

        /**
         * Catch-all to prevent conflicts when another instance of WP STAGING is active.
         */
        private function anotherInstanceOfWpstagingMustNotBeEnabled()
        {
            $oldVersionsLoaded = defined('WPSTG_PLUGIN_DIR') || defined('WPSTG_PLUGIN_FILE');
            $proVersionLoaded = defined('WPSTG_PRO_LOADED');
            $anotherFreeVersionLoaded = defined('WPSTG_FREE_LOADED') && $this->pluginFile !== WPSTG_FREE_LOADED;

            if ($oldVersionsLoaded || $proVersionLoaded || $anotherFreeVersionLoaded) {
                $this->notificationMessage = __('Another instance of WP STAGING is activated, therefore other instances of WP STAGING were automatically prevented from running to avoid errors. Please leave only one instance of the WP STAGING plugin active.', 'wp-staging');

                if (is_network_admin() && current_user_can('manage_network_plugins')) {
                    add_action('network_admin_notices', [$this, '_displayWarning']);
                } elseif (!is_network_admin() && current_user_can('activate_plugins')) {
                    add_action('admin_notices', [$this, '_displayWarning']);
                } elseif (!is_network_admin() && !current_user_can('activate_plugins')) {
                    $this->notificationMessage = __('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Please ask the site administrator to leave only one instance of WP STAGING active.', 'wp-staging');
                    add_action('admin_notices', [$this, '_displayWarning']);
                }
                if (defined('WPSTG_DEBUG') && WPSTG_DEBUG === true) {
                    throw new \RuntimeException(sprintf("Another instance of WP STAGING is activated, therefore other instances of WP STAGING were automatically prevented from running to avoid errors. Please leave only one instance of the WP STAGING plugin active. Plugin that was prevented from loading: %s", $this->pluginFile));
                }
            }
        }

        /**
         * Pro active
         * Free active
         *
         * Result: Deactivate Free
         */
        private function deactivateFreePluginIfProIsActiveOnSingleSite()
        {
            if (is_plugin_active($this->proPlugin)) {
                if (current_user_can('deactivate_plugin', $this->freePlugin)) {
                    unset($_GET['activate']);
                    deactivate_plugins($this->freePlugin);

                    $this->notificationMessage = __('WP STAGING Pro is activated, therefore WP STAGING Basic was automatically disabled.', 'wp-staging');
                    add_action('admin_notices', [$this, '_displayWarning']);

                    throw new \RuntimeException(sprintf("WP STAGING Pro is activated, therefore WP STAGING Basic was automatically disabled. Pro version that was active: %s, Basic version that was disabled: %s", $this->proPlugin, $this->freePlugin));
                }

                $this->notificationMessage = __('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Please ask the site administrator to leave only one instance of WP STAGING active.', 'wp-staging');
                add_action('admin_notices', [$this, '_displayWarning']);

                throw new \RuntimeException(sprintf('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Plugin that was loaded: %s, Plugin that was prevented from loading: %s', $this->freePlugin, $this->proPlugin));
            }
        }

        private function deactivateFreeOrProPluginOnMultisite()
        {
            /*
             * Pro active site-level
             * Free active site-level
             * Free deactivated network-level
             *
             * Result: Deactivate Free site-level
             */
            if (
                is_plugin_active($this->proPlugin) &&
                is_plugin_active($this->freePlugin) &&
                !is_plugin_active_for_network($this->freePlugin)
            ) {
                if (current_user_can('deactivate_plugin', $this->freePlugin)) {
                    unset($_GET['activate']);
                    deactivate_plugins($this->freePlugin);

                    $this->notificationMessage = __('WP STAGING Pro is activated, therefore WP STAGING Basic was automatically disabled.', 'wp-staging');
                    add_action('admin_notices', [$this, '_displayWarning']);

                    throw new \RuntimeException(sprintf("WP STAGING Pro is activated, therefore WP STAGING Basic was automatically disabled. Pro version that was active: %s, Basic version that was disabled: %s", $this->proPlugin, $this->freePlugin));
                }

                $this->notificationMessage = __('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Please ask the site administrator to leave only one instance of WP STAGING active.', 'wp-staging');
                add_action('admin_notices', [$this, '_displayWarning']);

                throw new \RuntimeException(sprintf('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Plugin that was loaded: %s, Plugin that was prevented from loading: %s', $this->freePlugin, $this->proPlugin));
            }

            /*
             * Pro active site-level
             * Free active network-level
             *
             * Result: Deactivate Pro site-level
             */
            if (
                is_plugin_active($this->proPlugin) &&
                is_plugin_active_for_network($this->freePlugin)
            ) {
                if (current_user_can('deactivate_plugin', $this->proPlugin)) {
                    unset($_GET['activate']);
                    deactivate_plugins($this->proPlugin);

                    $this->notificationMessage = __('WP STAGING Basic is activated network-wide, therefore WP STAGING Pro was automatically disabled. Please disable WP STAGING Basic network-wide to enable WP STAGING Pro.', 'wp-staging');
                    add_action('admin_notices', [$this, '_displayWarning']);

                    throw new \RuntimeException(sprintf("WP STAGING Basic is activated networkwide, therefore WP STAGING Pro was automatically disabled. Basic version that was activate networkwide: %s, Pro version that was disabled: %s", $this->freePlugin, $this->proPlugin));
                }

                $this->notificationMessage = __('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Please ask the site administrator to leave only one instance of WP STAGING active.', 'wp-staging');
                add_action('admin_notices', [$this, '_displayWarning']);

                throw new \RuntimeException(sprintf('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Plugin that was loaded: %s, Plugin that was prevented from loading: %s', $this->freePlugin, $this->proPlugin));
            }
        }

        /*
         * Pro active network-level
         * Free active network-level
         *
         * Result: Deactivate Free network-level
         */
        private function deactivateFreePluginOnMultisiteNetworkLevel()
        {
            if (is_plugin_active_for_network($this->proPlugin)) {
                if (current_user_can('manage_network_plugins')) {
                    unset($_GET['activate']);
                    deactivate_plugins($this->freePlugin, null, true);

                    $this->notificationMessage = __('WP STAGING Pro is activated network-wide, therefore WP STAGING Basic was disabled network-wide.', 'wp-staging');
                    add_action('network_admin_notices', [$this, '_displayWarning']);

                    throw new \RuntimeException(sprintf("WP STAGING Pro is activated networkwide, therefore WP STAGING Basic networkwide was automatically disabled. Pro version that was activate networkwide: %s, Basic version that was disabled networkwide: %s", $this->proPlugin, $this->freePlugin));
                }

                $this->notificationMessage = __('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Please ask the site administrator to leave only one instance of WP STAGING active.', 'wp-staging');
                add_action('admin_notices', [$this, '_displayWarning']);

                throw new \RuntimeException(sprintf('Another instance of WP STAGING was activated, therefore other instances of the WP STAGING plugin were prevented from loading. Plugin that was loaded: %s, Plugin that was prevented from loading: %s', $this->freePlugin, $this->proPlugin));
            }
        }
    }
}
