<?php

namespace WPStaging\Backend\Activation;

 
if (!defined("WPINC")) {
    die;
}

use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Onboarding\FreeOnboarding;
use WPStaging\Framework\Traits\NoticesTrait;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Notices\Notices;

class Welcome
{
    use NoticesTrait;








    const QUERY_FROM_ACTIVATION = 'wpstg-activation';

 
    const UTM_CONTENT_ACTIVATION = 'welcome_page_activation';

 
    const UTM_CONTENT_SIDEBAR = 'sidebar_upgrade';

 
    const EVENT_ACTIVATION_REDIRECT = 'activation_redirect';

    public function __construct()
    {
        add_action('admin_init', [$this, 'welcome']);
        add_action('wp_ajax_wpstg_activate_pro', [$this, 'ajaxActivatePro']); // phpcs:ignore WPStaging.Security.AuthorizationChecked -- Authorization checked in ajaxActivatePro()

        if (wpstgGetProVersionNumberIfInstalled() && $this->isWPStagingAdminPage()) {
            add_action(Notices::ACTION_ADMIN_NOTICES, [$this, 'wpstgproActivationNotice']);
        }
    }





    public function wpstgproActivationNotice()
    {
        if ($this->isFirstRunAwaitingConsent()) {
            return;
        }

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








    private function isFirstRunAwaitingConsent(): bool
    {
        $onboarding = FreeOnboarding::resolve();

        return $onboarding !== null && $onboarding->isPreConsentScreen();
    }




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









    public function welcome()
    {
 
        if (wpstgGetProVersionNumberIfInstalled()) {
            return;
        }

 
        if (get_transient('wpstg_activation_redirect') === false) {
            return;
        }

 
        delete_transient('wpstg_activation_redirect');

 
        if (is_network_admin() || isset($_GET['activate-multi'])) {
            return;
        }

        wp_safe_redirect($this->getFirstScreenUrl());
        exit;
    }









    public function getFirstScreenUrl(): string
    {
        $onboarding = FreeOnboarding::resolve();

        return $this->getScreenUrl($onboarding !== null && $onboarding->isEligible());
    }







    public function getScreenUrl(bool $isFirstRun): string
    {
        AnalyticsGenericEvent::logEvent(self::EVENT_ACTIVATION_REDIRECT, FreeOnboarding::ANALYTICS_GROUP, [
            'destination' => $isFirstRun ? 'task_selector' : 'welcome_page',
        ]);

        if ($isFirstRun) {
            return admin_url('admin.php?page=wpstg_clone');
        }

        return add_query_arg(self::QUERY_FROM_ACTIVATION, '1', admin_url('admin.php?page=wpstg-welcome'));
    }





    public static function getUpgradeContext(): string
    {
 
        // selects an attribution label and nothing else. phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_GET[self::QUERY_FROM_ACTIVATION]) ? self::UTM_CONTENT_ACTIVATION : self::UTM_CONTENT_SIDEBAR;
    }
}
