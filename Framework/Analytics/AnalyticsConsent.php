<?php

namespace WPStaging\Framework\Analytics;

use WPStaging\Framework\Notices\Notices;
use WPStaging\Core\WPStaging;

use function WPStaging\functions\debug_log;

class AnalyticsConsent
{
    use WithAnalyticsAPI;

    const OPTION_NAME_ANALYTICS_HAS_CONSENT = 'wpstg_analytics_has_consent';
    const OPTION_NAME_ANALYTICS_NOTICE_DISMISSED = 'wpstg_analytics_notice_dismissed';
    const OPTION_NAME_ANALYTICS_MODAL_DISMISSED = 'wpstg_analytics_modal_dismissed';
    const OPTION_NAME_ANALYTICS_REMIND_ME = 'wpstg_analytics_consent_remind_me';





    public function maybeShowConsentFailureNotice()
    {
 
        if (!WPStaging::make(Notices::class)->isWPStagingAdminPage()) {
            return;
        }

 
        if (!isset($_GET['wpstgConsentFailed'])) {
            return;
        }

        $notice = WPSTG_VIEWS_DIR . 'notices/analytics-consent-failed.php';

        if (!file_exists($notice)) {
            return;
        }

        include_once $notice;
    }





    public function listenForConsent()
    {
 
        if (!isset($_GET['wpstgConsent'])) {
            return;
        }

 
        if (!current_user_can('manage_options')) {
            return;
        }

 
        check_ajax_referer('wpstg_consent_nonce', 'wpstgConsentNonce');

        if ($_GET['wpstgConsent'] == 'later') {
            update_option(self::OPTION_NAME_ANALYTICS_MODAL_DISMISSED, '1', false);
            update_option(self::OPTION_NAME_ANALYTICS_REMIND_ME, strtotime('+7 days'), false);

            return;
        }

 
        if ($_GET['wpstgConsent'] == 'no') {
            update_option(self::OPTION_NAME_ANALYTICS_NOTICE_DISMISSED, '1', false);
            update_option(self::OPTION_NAME_ANALYTICS_MODAL_DISMISSED, '1', false);
            update_option(self::OPTION_NAME_ANALYTICS_HAS_CONSENT, '0', false);
            delete_option(self::OPTION_NAME_ANALYTICS_REMIND_ME);

            add_action(Notices::ACTION_ADMIN_NOTICES, [$this, 'showNoticeConsentRefused']);

            return;
        }

        if ($_GET['wpstgConsent'] == 'yes') {
            try {
                $this->giveConsent();
            } catch (\Exception $e) {
 
                wp_redirect(add_query_arg([
                    'wpstgConsentFailed' => true,
                ], $this->getReturnUrl()));
                exit;
            }

            update_option(self::OPTION_NAME_ANALYTICS_NOTICE_DISMISSED, '1', false);
            update_option(self::OPTION_NAME_ANALYTICS_MODAL_DISMISSED, '1', false);
            update_option(self::OPTION_NAME_ANALYTICS_HAS_CONSENT, '1', false);
            delete_option(self::OPTION_NAME_ANALYTICS_REMIND_ME);
        }
    }

    public function showNoticeConsentRefused()
    {
        $notice = WPSTG_VIEWS_DIR . 'notices/analytics-consent-refused.php';

        if (!file_exists($notice)) {
            return;
        }

        include_once $notice;
    }






    public function giveConsent()
    {
        $url = $this->getApiUrl('consent');

        $response = wp_remote_post($url, [
            'method'      => 'POST',
            'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'        => json_encode([
                'site_hash' => $this->getSiteHash(),
                'site_url'  => get_home_url(),
            ]),
            'data_format' => 'body',
            'timeout'     => 10,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'sslverify'   => false,
        ]);

 
        if (is_wp_error($response) || !in_array(wp_remote_retrieve_response_code($response), [201, 409])) {
            $errorMessage = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
            debug_log('WP STAGING Analytics Send Error: ' . $errorMessage, 'debug');

 
            update_option(self::OPTION_NAME_ANALYTICS_NOTICE_DISMISSED, '1', false);

 
            update_option(self::OPTION_NAME_ANALYTICS_MODAL_DISMISSED, '1', false);

 
            update_option(self::OPTION_NAME_ANALYTICS_HAS_CONSENT, '1', false);

            throw new \Exception();
        }
    }




    public function hasUserConsent()
    {
        return get_option(self::OPTION_NAME_ANALYTICS_HAS_CONSENT, null);
    }





    public function invalidateConsent()
    {
        delete_option(self::OPTION_NAME_ANALYTICS_NOTICE_DISMISSED);
        delete_option(self::OPTION_NAME_ANALYTICS_HAS_CONSENT);
    }

    protected function getReturnUrl(): string
    {
        global $pagenow, $plugin_page;

        return add_query_arg('page', $plugin_page, admin_url($pagenow));
    }






    public function getConsentLink(bool $agreeOrDecline): string
    {
        return add_query_arg([
            'wpstgConsent'      => $agreeOrDecline ? 'yes' : 'no',
            'wpstgConsentNonce' => wp_create_nonce('wpstg_consent_nonce'),
        ], $this->getReturnUrl());
    }

    public function getRemindMeLaterConsentLink(): string
    {
        return add_query_arg([
            'wpstgConsent'      => 'later',
            'wpstgConsentNonce' => wp_create_nonce('wpstg_consent_nonce'),
        ], $this->getReturnUrl());
    }
}
