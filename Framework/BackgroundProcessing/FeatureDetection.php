<?php

/**
 * Provides feature detection for the Queue system.
 *
 * @package WPStaging\Framework\BackgroundProcessing
 */

namespace WPStaging\Framework\BackgroundProcessing;

use WP_Error;
use WPStaging\Framework\Adapter\WpAdapter;

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
        if ($this->isAjaxAvailableCache === null) {
            // Run this check only on Admin UI and on PHP initial state.
            // TODO: inject WpAdapter using DI
            $notRightContext = wp_installing() || (defined('REST_REQUEST') && REST_REQUEST) || (new WpAdapter())->doingAjax()
                || wp_doing_cron() || !is_admin();

            if ($notRightContext) {
                // Default to say that it's supported if we cannot exclude it.
                return true;
            }

            $availableOptionValue = get_option(self::AJAX_OPTION_NAME, null);

            if (!in_array($availableOptionValue, ['y', 'n'], true)) {
                $available = $this->runAjaxFeatureTest();
                $availableOptionValue = $available ? 'y' : 'n';
                update_option(self::AJAX_OPTION_NAME, $availableOptionValue);
            }

            $this->isAjaxAvailableCache = $availableOptionValue === 'y';
        }

        if (!$this->isAjaxAvailableCache && $showAdminNotice) {
            add_action('admin_notices', [$this, 'ajaxSupportMissingAdminNotice']);
        }

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
        $ajaxUrl = add_query_arg([
            'action' => self::AJAX_TEST_ACTION,
            '_ajax_nonce' => wp_create_nonce(self::AJAX_TEST_ACTION)
        ], admin_url('admin-ajax.php'));

        $hash = md5(uniqid(__CLASS__, true));

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

        if ($response instanceof WP_Error) {
            return false;
        }

        $test = static function () use ($hash) {
            // Run a direct query to force the re-fetch and not hit the cache.
            global $wpdb;
            $fetched = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT option_value from {$wpdb->options} WHERE option_name = %s AND option_value = 'y'",
                    self::AJAX_OPTION_NAME
                )
            );

            return $fetched === 'y';
        };

        $waited = 0;
        $waitStep = .5;
        $timeout = 10;

        do {
            $waited += $waitStep;
            sleep($waitStep);

            if ($test()) {
                // Look no further, it worked.
                return true;
            }
        } while ($waited <= $timeout);

        // We waited enough: either the AJAX system is not available or is not reliable.
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
        update_option(self::AJAX_OPTION_NAME, 'y');
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
