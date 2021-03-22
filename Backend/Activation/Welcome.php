<?php

namespace WPStaging\Backend\Activation;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

class Welcome
{

    public function __construct()
    {
        add_action('admin_init', [$this, 'welcome']);
    }


    /**
     * Sends user to the welcome page on first activation of WPSTG as well as each
     * time WPSTG is upgraded to a new version
     *
     * @access public
     * @return void
     * @since 1.0.1
     */
    public function welcome()
    {
        // Bail if no activation redirect
        if (get_transient('wpstg_activation_redirect') === false) {
            return;
        }

        // Delete the redirect transient
        delete_transient('wpstg_activation_redirect');

        // Bail if activating from network, or bulk
        if (is_network_admin() || isset($_GET['activate-multi'])) {
            return;
        }

        wp_safe_redirect(admin_url('admin.php?page=wpstg-welcome'));
        exit;
    }
}
