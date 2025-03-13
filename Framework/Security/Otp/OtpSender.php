<?php

namespace WPStaging\Framework\Security\Otp;

use WPStaging\Framework\Security\Auth;
use WPStaging\Notifications\Notifications;

use function WPStaging\functions\debug_log;

/**
 * Class OtpSender
 *
 * @package WPStaging\Framework\Security\Otp
 */
class OtpSender
{
    /**
     * The transient key for storing the OTP sent status.
     * @var string
     */
    const TRANSIENT_OTP_SENT = 'wpstg_otp_sent';

    /**
     * The expiration time for the OTP sent transient in seconds.
     * @var int
     */
    const TRANSIENT_OTP_SENT_EXPIRATION = 30; // 30 seconds

    /**
     * @var Otp
     */
    private $otpService;

    /**
     * @var Notifications
     */
    private $notifications;

    /**
     * @var Auth
     */
    private $auth;

    public function __construct(Otp $otpService, Auth $auth, Notifications $notifications)
    {
        $this->otpService    = $otpService;
        $this->notifications = $notifications;
        $this->auth          = $auth;
    }

    /**
     * Send OTP to the user
     * @return void
     */
    public function ajaxSendOtp()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
            wp_send_json_error(esc_html__('Invalid Request! User is not authenticated', 'wp-staging'), 403);
        }

        if (!$this->otpService->isOtpFeatureEnabled()) {
            wp_send_json_success([
                'message' => esc_html__('OTP feature is not enabled.', 'wp-staging'),
            ], 202);
        }

        if (empty($_REQUEST['sessionId'])) {
            wp_send_json_error(esc_html__('Invalid Request! Session ID is missing', 'wp-staging'), 403);
        }

        $userEmail = $this->getCurrentUserEmail();
        if (empty($userEmail)) {
            wp_send_json_error(esc_html__('Invalid Request! No user found', 'wp-staging'), 403);
        }

        $sessionId     = sanitize_text_field($_REQUEST['sessionId']);
        $resendRequest = !empty($_REQUEST['resend']) && sanitize_text_field($_REQUEST['resend']) === 'true';

        if (!$this->canSendOtp()) {
            if ($resendRequest) {
                wp_send_json_error([
                    'message'   => esc_html__('Please wait %s seconds before requesting a new confirmation code...', 'wp-staging'),
                    'reRequestAt' => get_transient(self::TRANSIENT_OTP_SENT),
                ]);
            } else {
                $remainingTime = $this->calculateRemainingTime();
                wp_send_json_error(sprintf(esc_html__('OTP already sent. Please wait %s seconds before sending another verification code.', 'wp-staging'), esc_html($remainingTime)), 423);
            }
        }

        $otp = '';
        try {
            $otp = $this->otpService->generateNewOtp($sessionId);
        } catch (OtpException $ex) {
            wp_send_json_error($ex->getMessage(), 401);
        }

        $subject = esc_html__('Your WP Staging Verification Code', 'wp-staging');

        $message = esc_html__('Your Verification Code', 'wp-staging');
        $message .= "\n\n" . esc_html__('To upload your backup, enter this code on the WP Staging OTP form:', 'wp-staging');
        $message .= "\n\n" . esc_html($otp);
        $message .= "\n\n" . sprintf(esc_html__('This E-Mail has been sent from %s while uploading a backup file to the website with the WP Staging plugin.', 'wp-staging'), get_site_url());
        $message .= "\n\n" . esc_html__("Please do not forward this email.  If you didn`t request this code, you can ignore this message.", "wp-staging");
        $message .= "\n\n" . esc_html__('The verification code above is unique and will expire in 5 minutes.', 'wp-staging');

        $sent = false;
        if (get_option(Notifications::OPTION_SEND_EMAIL_AS_HTML, false) === 'true') {
            $sent = $this->notifications->sendEmailAsHTML($userEmail, $subject, $message);
        } else {
            $sent = $this->notifications->sendEmail($userEmail, $subject, $message, '', [], Notifications::DISABLE_FOOTER_MESSAGE);
        }

        if (!$sent) {
            debug_log('Failed to send OTP to user email: ' . $userEmail);
            wp_send_json_error(esc_html__('Failed to send OTP', 'wp-staging'), 401);
        }

        debug_log('Succeeded to send OTP to user email: ' . $userEmail);
        if ($resendRequest) {
            wp_send_json_success([
                'message'   => esc_html__('OTP sent successfully! Please wait %s seconds before requesting a new verification code...', 'wp-staging'),
                'reRequestAt' => get_transient(self::TRANSIENT_OTP_SENT),
            ], 201);
        } else {
            wp_send_json_success(esc_html__('OTP sent successfully', 'wp-staging'), 201);
        }
    }

    protected function getCurrentUserEmail(): string
    {
        $user = wp_get_current_user();
        if (!$user) {
            return '';
        }

        return $user->user_email;
    }

    protected function canSendOtp(): bool
    {
        $otpSent = get_transient(self::TRANSIENT_OTP_SENT);
        if ($otpSent !== false) {
            return false;
        }

        set_transient(self::TRANSIENT_OTP_SENT, time() + self::TRANSIENT_OTP_SENT_EXPIRATION, self::TRANSIENT_OTP_SENT_EXPIRATION);

        return true;
    }

    protected function calculateRemainingTime(): string
    {
        $currentTime = time();
        $expireAt = get_transient(self::TRANSIENT_OTP_SENT);
        $timeToWaitMore = $expireAt - $currentTime;

        return $timeToWaitMore . 's';
    }
}
