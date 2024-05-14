<?php

namespace WPStaging\Backend\Activation;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Framework\Traits\NoticesTrait;
use WPStaging\Framework\Facades\Escape;

class Welcome
{
    use NoticesTrait;

    public function __construct()
    {
        add_action('admin_init', [$this, 'welcome']);

        if (wpstgGetProVersionNumberIfInstalled() && $this->isWPStagingAdminPage()) {
            add_action('wpstg.admin_notices', [$this, 'wpstgproActivationNotice']);
        }
    }

    /**
     * @return void
     * @todo make this message permanently hideable when dismissed.
     */
    public function wpstgproActivationNotice()
    {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php
                printf(
                    Escape::escapeHtml(__('WP Staging Pro is installed but not activated. Some features may not work until you activate the Pro version. Go to <a href=%s>Installed Plugins</a> and activate WP Staging Pro there.', 'wp-staging')),
                    esc_url(admin_url('plugins.php'))
                );
                ?>
            </p>
        </div>
        <?php
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
        // Bail if pro is installed
        if (wpstgGetProVersionNumberIfInstalled()) {
            return;
        }

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
