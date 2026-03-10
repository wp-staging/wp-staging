<?php

namespace WPStaging\Backend\Activation;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Framework\Traits\NoticesTrait;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Notices\Notices;

class Welcome
{
    use NoticesTrait;

    public function __construct()
    {
        add_action('admin_init', [$this, 'welcome']);
        add_action('wp_ajax_wpstg_activate_pro', [$this, 'ajaxActivatePro']); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Authorization checked in ajaxActivatePro()

        if (wpstgGetProVersionNumberIfInstalled() && $this->isWPStagingAdminPage()) {
            add_action(Notices::ACTION_ADMIN_NOTICES, [$this, 'wpstgproActivationNotice']);
        }
    }

    /**
     * @return void
     * @todo make this message permanently hideable when dismissed.
     */
    public function wpstgproActivationNotice()
    {
        $nonce = wp_create_nonce('wpstg_activate_pro');
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <?php
                printf(
                    Escape::escapeHtml(__('WP Staging Pro is installed but not active. %1$sActivate now%2$s to unlock all Pro features.', 'wp-staging')),
                    '<a href="#" id="wpstg-activate-pro" data-nonce="' . esc_attr($nonce) . '">',
                    '</a>'
                );
                ?>
            </p>
        </div>
        <script>
            (function() {
                var link = document.getElementById('wpstg-activate-pro');
                if (!link) return;
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (link.dataset.busy) return;
                    link.dataset.busy = '1';
                    var originalText = link.textContent;
                    link.textContent = '<?php echo esc_js(__('Activating...', 'wp-staging')); ?>';
                    link.style.pointerEvents = 'none';
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', ajaxurl);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        try { var res = JSON.parse(xhr.responseText); } catch(err) { res = {}; }
                        if (res.success) {
                            window.location.reload();
                        } else {
                            link.textContent = res.data || 'Activation failed.';
                            link.style.pointerEvents = '';
                            delete link.dataset.busy;
                        }
                    };
                    xhr.onerror = function() {
                        link.textContent = originalText;
                        link.style.pointerEvents = '';
                        delete link.dataset.busy;
                    };
                    xhr.send('action=wpstg_activate_pro&nonce=' + link.dataset.nonce);
                });
            })();
        </script>
        <?php
    }

    /**
     * @return void
     */
    public function ajaxActivatePro()
    {
        if (!check_ajax_referer('wpstg_activate_pro', 'nonce', false)) {
            wp_send_json_error('Invalid security token.');
        }

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error('Insufficient permissions.');
        }

        $slug = wpstgGetPluginSlug(WPSTG_PRO_VERSION_PLUGIN_FILE);
        if (!$slug) {
            wp_send_json_error('Pro plugin not found.');
        }

        $result = activate_plugin($slug);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success();
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
