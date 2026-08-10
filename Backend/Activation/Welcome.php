<?php

namespace WPStaging\Backend\Activation;

// No Direct Access
if (!defined("WPINC")) {
    die;
}

use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;
use WPStaging\Framework\Experiments\ExperimentsRegistry;
use WPStaging\Framework\Onboarding\FreeOnboarding;
use WPStaging\Framework\Traits\NoticesTrait;
use WPStaging\Framework\Facades\Escape;
use WPStaging\Framework\Notices\Notices;

class Welcome
{
    use NoticesTrait;

    /**
     * Marks the welcome page as the one the activation redirect sent the user to.
     *
     * The sidebar's "Get WP Staging Pro" opens this same page, so without it the
     * page cannot tell an upgrade the user went looking for from one the plugin
     * put in front of them, and both would be attributed as the same click.
     */
    const QUERY_FROM_ACTIVATION = 'wpstg-activation';

    /** utm_content for the buy button on a welcome page reached by activation. */
    const UTM_CONTENT_ACTIVATION = 'welcome_page_activation';

    /** utm_content for the buy button on a welcome page the user asked for. */
    const UTM_CONTENT_SIDEBAR = 'sidebar_upgrade';

    /** @see dev/docs/analytics/generic-events.md */
    const EVENT_ACTIVATION_REDIRECT = 'activation_redirect';

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

    /**
     * The neutral first-run shell asks for exactly one decision, and its notice
     * dispatcher cannot be silenced wholesale because the consent modal renders
     * through it. So this notice stands down on its own instead.
     *
     * @return bool
     */
    private function isFirstRunAwaitingConsent(): bool
    {
        $onboarding = FreeOnboarding::resolve();

        return $onboarding !== null && $onboarding->isPreConsentScreen();
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

        wp_safe_redirect($this->getFirstScreenUrl());
        exit;
    }

    /**
     * Where a newly activated installation lands.
     *
     * The variant is drawn here rather than on the screen that follows, because
     * which screen follows is what it decides. `control` keeps the welcome page
     * the funnel is built on; `task_selector` goes straight to WP STAGING, where
     * the consent question and then the first-run selector take over.
     *
     * An installation that is not in the experiment at all — Pro installed, an
     * upgrade rather than a first install, a user without the capability — draws
     * no variant and keeps the existing behaviour.
     */
    public function getFirstScreenUrl(): string
    {
        $onboarding = FreeOnboarding::resolve();

        return $this->getScreenUrlForVariant($onboarding === null ? '' : $onboarding->getVariant());
    }

    /**
     * @param string $variant The drawn variant, or empty for an installation
     *                        that is not in the experiment.
     */
    public function getScreenUrlForVariant(string $variant): string
    {
        $isFirstRun = $variant === ExperimentsRegistry::VARIANT_TASK_SELECTOR;

        // The experiment and variant are stamped on every event by the pipeline,
        // so the payload only has to say where this installation was sent — which
        // is the thing the variant was drawn to decide.
        AnalyticsGenericEvent::logEvent(self::EVENT_ACTIVATION_REDIRECT, FreeOnboarding::ANALYTICS_GROUP, [
            'destination' => $isFirstRun ? 'task_selector' : 'welcome_page',
        ]);

        if ($isFirstRun) {
            return admin_url('admin.php?page=wpstg_clone');
        }

        return add_query_arg(self::QUERY_FROM_ACTIVATION, '1', admin_url('admin.php?page=wpstg-welcome'));
    }

    /**
     * @return string The utm_content the buy button should carry, which depends
     *                on how the user arrived rather than on where they are.
     */
    public static function getUpgradeContext(): string
    {
        // Read-only routing detail on an admin page the user is already on: it
        // selects an attribution label and nothing else. phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_GET[self::QUERY_FROM_ACTIVATION]) ? self::UTM_CONTENT_ACTIVATION : self::UTM_CONTENT_SIDEBAR;
    }
}
