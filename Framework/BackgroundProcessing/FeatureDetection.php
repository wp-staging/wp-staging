<?php

/**
 * Provides feature detection for the Queue system.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

use WP_Error;
use WPStaging\Framework\Adapter\WpAdapter;

use function WPStaging\functions\debug_log;

/**
 * Class FeatureDetection
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */
class FeatureDetection
{
    const AJAX_TEST_ACTION = 'wpstg_ajax_test_action';
    const AJAX_OPTION_NAME = 'wpstg_q_feature_detection_ajax_available';
    const AJAX_REQUEST_QUERY_VAR = 'wpstg_q_ajax_check';

    /**
     * A cache property to store the result of the AJAX support check.
     *
     * @var bool
     */
    protected $isAjaxAvailableCache;

    /**
     * Returns whether the AJAX based queue processing system is available or not.
     *
     * @param bool $showAdminNotice Whether to show an admin notice on missing support or not.
     *
     * @return bool Whether the AJAX based queue processing system is available or not.
     */
    public function isAjaxAvailable($showAdminNotice = true)
    {
        //debug_log('isAjaxAvailable start');
        if ($this->isAjaxAvailableCache === null) {
            // Run this check only on Admin UI and on PHP initial state.
            // TODO: inject WpAdapter using DI
            $notRightContext = wp_installing() || (defined('REST_REQUEST') && REST_REQUEST) || (new WpAdapter())->doingAjax()
                || wp_doing_cron() || !is_admin();

            if ($notRightContext) {
                // Default to say that it's supported if we cannot exclude it.
                debug_log(sprintf(
                    "isAjaxAvailable not right context: Is WP Installing? %s - Is Rest? %s - Is Ajax? %s - Is Cron? %s - Is admin? %s",
                    wp_installing() ? 'true' : 'false',
                    (defined('REST_REQUEST') && REST_REQUEST) ? 'true' : 'false',
                    (new WpAdapter())->doingAjax() ? 'true' : 'false',
                    wp_doing_cron() ? 'true' : 'false',
                    is_admin() ? 'true' : 'false'
                ));
                return true;
            }

            $availableOptionValue = get_option(self::AJAX_OPTION_NAME, null);

            if (!in_array($availableOptionValue, ['y', 'n'], true)) {
                $available = $this->runAjaxFeatureTest();
                $availableOptionValue = $available ? 'y' : 'n';
                update_option(self::AJAX_OPTION_NAME, $availableOptionValue, false);
            }

            $this->isAjaxAvailableCache = $availableOptionValue === 'y';
        }

        if (!$this->isAjaxAvailableCache && $showAdminNotice) {
            add_action('admin_notices', [$this, 'ajaxSupportMissingAdminNotice']);
        }

        //debug_log('isAjaxAvailable end. Result: ' . $this->isAjaxAvailableCache);

        return $this->isAjaxAvailableCache;
    }

    /**
     * Runs the AJAX support feature detection test.
     *
     * This method will fire a non-blocking POST request
     * to the `admin-ajax` endpoint.
     * In response, the `updateAjaxTestOption` will udpate
     * the flag option value and set it to `y` or not set it at all.
     * This method will wait for some time for its counter-part, the
     * `updateAjaxTestOption` running in the other request, to update
     * the option. If the time runs out and the option is not there,
     * the we know AJAX is either not working or not reliable enough.
     * The method uses an option, and not a transient, as flag value to
     * be able to force re-fetch it from the database.
     *
     * @return bool Whether the AJAX-based system is supported or not.
     *
     * @see FeatureDetection::updateAjaxTestOption()
     */
    public function runAjaxFeatureTest()
    {
        // Start from a clean state.
        debug_log('Starting from a clean state...');
        delete_option(self::AJAX_OPTION_NAME);

        $ajaxUrl = add_query_arg([
            'action' => self::AJAX_TEST_ACTION,
            '_ajax_nonce' => wp_create_nonce(self::AJAX_TEST_ACTION)
        ], admin_url('admin-ajax.php'));

        $hash = md5(uniqid(__CLASS__, true));

        debug_log('Sending request to: ' . $ajaxUrl);

        $response = wp_remote_post(esc_url_raw($ajaxUrl), [
            'headers' => [
                'X-WPSTG-Request' => self::AJAX_TEST_ACTION
            ],
            'blocking' => false,
            'timeout' => 0.01,
            'cookies' => isset($_COOKIE) ? $_COOKIE : [],
            'sslverify' => apply_filters('https_local_ssl_verify', false),
            'body' => [self::AJAX_OPTION_NAME => $hash],
        ]);

        debug_log(wp_json_encode($response));

        if ($response instanceof WP_Error) {
            return false;
        }

        $test = static function () use ($hash) {
            // Run a direct query to force the re-fetch and not hit the cache.
            global $wpdb;
            $fetched = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT `option_value` from `{$wpdb->options}` WHERE `option_name` = '%s' AND `option_value` = 'y'",
                    self::AJAX_OPTION_NAME
                )
            );

            debug_log('Fetched is equal to: ' . $fetched);
            debug_log('Fetched is equal to (get_option): ' . get_option(self::AJAX_OPTION_NAME));

            return $fetched === 'y';
        };

        $waited = 0;
        $waitStep = .5 * 1e6; // 0.5 second
        $timeout = 10 * 1e6; // 10 seconds

        do {
            debug_log('runAjaxFeatureTest waited ' . number_format($waited / 1e6, 1) . ' seconds...');
            $waited += $waitStep;
            usleep($waitStep);

            if ($test()) {
                // Look no further, it worked.
                debug_log('runAjaxFeatureTest worked');
                return true;
            }
        } while ($waited <= $timeout);

        // We waited enough: either the AJAX system is not available or is not reliable.
        debug_log('runAjaxFeatureTest did not work');
        return false;
    }

    /**
     * Writes `y` to the feature detection option.
     *
     * This method will be called in response to the AJAX request
     * fired by the `runAjaxFeatureTest` method.
     * That method will wait, in its PHP process, for this method to
     * udpate the option value and deem the AJAX support as "working".
     *
     * @return void The method does not return any value and will have the
     *              side effect of updating the option.
     *
     * @see FeatureDetection::runAjaxFeatureTest()
     */
    public function updateAjaxTestOption()
    {
        debug_log('Running updateAjaxTestOption');
        check_ajax_referer(self::AJAX_TEST_ACTION);

        if (!update_option(self::AJAX_OPTION_NAME, 'y', false)) {
            debug_log('updateAjaxTestOption update_option returned false');
        }

        debug_log('Complete updateAjaxTestOption. New value: ' . get_option(self::AJAX_OPTION_NAME));
    }

    /**
     * Displays a dismissible admin notice to let the user know the BG Processing system will not
     * perform at its best due to lack of AJAX support.
     *
     * @return void The method will have the side effect of echoing HTML to the page.
     */
    public function ajaxSupportMissingAdminNotice()
    {
        $message = __(
            'WP STAGING Background Processing system cannot use AJAX: this will prevent it from performing at its best.',
            'wp-staging'
        );
        $checkLink = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('/?' . self::AJAX_REQUEST_QUERY_VAR . '=1')),
            __('Click here to check again now.', 'wp-staging')
        );
        ?>
        <div class="notice notice-warning is-dismissible wpstg__notice wpstg__notice--warning">
            <p><?php echo wp_kses_post($message); ?></p>
            <p><?php echo wp_kses_post($checkLink); ?></p>
        </div>
        <?php
    }
}
