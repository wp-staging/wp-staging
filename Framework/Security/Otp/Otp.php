<?php

namespace WPStaging\Framework\Security\Otp;

use WP_User;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\DataEncryption;

/**
 * Class Otp
 *
 * WP STAGING OTP implementation,
 * with the main purpose to make sure that the user
 * has the right to perform certain actions.
 * OTPs are maintained per user and per session.
 *
 * @package WPStaging\Framework\Security\Otp
 */
class Otp
{
    /**
     * The option_name that is stored in the database for storing otps.
     * @var string
     */
    const OPTION_NAME = 'wpstg_otps';

    /**
     * The expiration time for the otp in seconds.
     * @var int
     */
    const OTP_EXPIRATION = 60 * 15; // 15 minutes in seconds

    /**
     * The expiration time for the otp before it can be re-verified on failure.
     * @var int
     */
    const TRANSIENT_OTP_VERIFICATION_EXPIRY = 5; // 5 seconds

    /**
     * The length of the OTP.
     * @var int
     */
    const OTP_LENGTH = 6;

    /**
     * The maximum allowed consecutive OTP failures before locking the verification.
     * @var int
     */
    const MAX_ALLOWED_CONSECUTIVE_OTP_FAILURES = 5;

    /**
     * The expiration time for locking otp for consecutive failures.
     * @var string
     */
    const TRANSIENT_CONSECTUTIVE_OTP_FAILURES = 'wpstg_otp_consecutive_failures';

    /**
     * The expiration time for locking otp for consecutive failures.
     * this transient should expire automatically after 15 minutes.
     * @var string
     */
    const TRANSIENT_CONSECTUTIVE_OTP_FAILURES_EXPIRY = 60 * 15; // 15 minutes

    /**
     * The expiration time for locking otp for consecutive failures.
     * @var int
     */
    const TRANSIENT_OTP_LOCKED_EXPIRY = 60; // 1 minute

    /**
     * The transient name when otp is locked.
     * @var string
     */
    const TRANSIENT_OTP_LOCKED = 'wpstg_otp_locked';

    /**
     * @var Capabilities
     */
    private $capabilities;

    /**
     * @var DataEncryption
     */
    private $dataEncryption;

    public function __construct(Capabilities $capabilities, DataEncryption $dataEncryption)
    {
        $this->capabilities   = $capabilities;
        $this->dataEncryption = $dataEncryption;
    }

    public function isOtpFeatureEnabled(): bool
    {
        return defined('WPSTG_OTP_ENABLED') && constant('WPSTG_OTP_ENABLED');
    }

    /**
     * @return void
     */
    public function cleanupExpiredOtps()
    {
        $otps = get_option(static::OPTION_NAME, []);
        $currentTime = time();

        foreach ($otps as $userID => $otpData) {
            foreach ($otpData as $sessionId => $otp) {
                if ($otp['expiry_at'] < $currentTime) {
                    unset($otps[$userID][$sessionId]);
                }
            }
        }

        update_option(static::OPTION_NAME, $otps);
    }

    /**
     * @param string $sessionId A sessionId for the request.
     * @return string
     */
    public function generateNewOtp(string $sessionId): string
    {
        // Early bail: Not enough privilege to generate OTP.
        if (!current_user_can($this->capabilities->manageWPSTG())) {
            throw new OtpException('Current user has no privilege to generate an OTP');
        }

        $otp = $this->generateOtp();
        $this->saveOtp($otp, $sessionId);

        return $otp;
    }

    /**
     * @param string $otpToValidate Compare an OTP with the one stored in the database identified by the session ID.
     * @param string $sessionId A sessionId for the request.
     */
    public function validateOtp(string $otpToValidate, string $sessionId)
    {
        // Early bail: Not enough privilege to generate a otp.
        if (!current_user_can($this->capabilities->manageWPSTG())) {
            throw new OtpException('User has no privilege to validate a otp');
        }

        // Early bail: A otp is always a 6-character random string.
        if (empty($otpToValidate) || strlen($otpToValidate) !== self::OTP_LENGTH) {
            throw new OtpException('Invalid OTP');
        }

        $savedOtp = $this->getSessionOtp($sessionId);
        if ($otpToValidate !== $savedOtp) {
            throw new FailedOtpException();
        }

        // Once the OTP is validated, it should be removed.
        $this->invalidateOtp($sessionId);
    }

    /**
     * For validation the request that contains otp
     * @throws OtpException
     * @return void
     */
    public function validateOtpRequest()
    {
        // Early bail if OTP feature is not enabled
        if (!$this->isOtpFeatureEnabled()) {
            throw new OtpDisabledException();
        }

        $waitBeforeVerify = $this->checkOtpVerificationLocked();
        if ($waitBeforeVerify > 0) {
            throw new OtpException(sprintf(esc_html__('OTP verification is locked! Try again in after %s...', 'wp-staging'), esc_html($waitBeforeVerify) . 's'), 403);
        }

        if (empty($_REQUEST['sessionId'])) {
            throw new OtpException(esc_html__('Invalid Request! Session ID is missing', 'wp-staging'), 400);
        }

        if (empty($_REQUEST['otp'])) {
            throw new OtpException(esc_html__('Invalid Request! OTP is missing', 'wp-staging'), 400);
        }

        $sessionId = sanitize_text_field($_REQUEST['sessionId']);
        $otp       = sanitize_text_field($_REQUEST['otp']);
        try {
            $this->validateOtp($otp, $sessionId);
        } catch (ExpiredOtpException $ex) {
            $this->lockOtpVerification();
            throw new OtpException(esc_html__('OTP Expired', 'wp-staging'), 403);
        } catch (FailedOtpException $ex) {
            $this->lockOtpVerification();
            throw new OtpException(esc_html__('OTP Not Verified', 'wp-staging'), 403);
        } catch (\Throwable $ex) {
            $this->lockOtpVerification();
            throw new OtpException(esc_html__('OTP Error: ', 'wp-staging') . $ex->getMessage(), 403);
        }
    }

    public function getOtpCreatedTimeBySessionId(string $sessionId): int
    {
        $userID = $this->getCurrentUserID();

        $otps = get_option(static::OPTION_NAME, []);
        // Early bail: if there is no otp for the user
        if (!isset($otps[$userID])) {
            throw new OtpException('Current user is not an instance of WP_User');
        }

        if (empty($otps[$userID][$sessionId])) {
            throw new OtpException('No OTP for the session');
        }

        $otpData = $otps[$userID][$sessionId];

        return $otpData['created_at'];
    }

    /**
     * @return void
     */
    public function lockOtpVerification()
    {
        $expiryTime = self::TRANSIENT_OTP_VERIFICATION_EXPIRY;
        // If the transient is not set assume 0 consecutive failures.
        $consecutiveFailures = get_transient(static::TRANSIENT_CONSECTUTIVE_OTP_FAILURES);
        if ($consecutiveFailures === false) {
            $consecutiveFailures = 0;
        }

        $consecutiveFailures++;
        set_transient(static::TRANSIENT_CONSECTUTIVE_OTP_FAILURES, $consecutiveFailures);
        if ($consecutiveFailures >= self::MAX_ALLOWED_CONSECUTIVE_OTP_FAILURES) {
            $expiryTime = self::TRANSIENT_OTP_LOCKED_EXPIRY;
            // lets delete this transient as we are locking the verification.
            delete_transient(static::TRANSIENT_CONSECTUTIVE_OTP_FAILURES);
        }

        $expireAt = time() + $expiryTime;
        set_transient(static::TRANSIENT_OTP_LOCKED, $expireAt, $expiryTime);
    }

    public function checkOtpVerificationLocked(): int
    {
        $expiryAt = get_transient(static::TRANSIENT_OTP_LOCKED);
        if ($expiryAt === false) {
            return 0;
        }

        if ($expiryAt < time()) {
            delete_transient(static::TRANSIENT_OTP_LOCKED);
            return 0;
        }

        return $expiryAt - time();
    }

    /**
     * @param string $sessionId
     * @return string
     */
    protected function getSessionOtp(string $sessionId): string
    {
        $userID = $this->getCurrentUserID();

        $otps = get_option(static::OPTION_NAME, []);
        // Early bail: if there is no otp for the user
        if (!isset($otps[$userID])) {
            throw new OtpException('Current user is not an instance of WP_User');
        }

        if (empty($otps[$userID][$sessionId])) {
            throw new OtpException('No OTP for the session');
        }

        $otpData = $otps[$userID][$sessionId];
        // Early bail: if the otp is expired
        if ($otpData['expiry_at'] < time()) {
            throw new ExpiredOtpException('OTP has expired');
        }

        return $this->dataEncryption->decrypt($otpData['otp']);
    }

    /**
     * @param string $otp
     * @param string $sessionId
     * @return void
     */
    protected function saveOtp(string $otp, string $sessionId)
    {
        $userID = $this->getCurrentUserID();

        $otps = get_option(static::OPTION_NAME, []);
        $otps[$userID][$sessionId] = [
            'otp' => $this->dataEncryption->encrypt($otp),
            'created_at' => time(),
            'expiry_at' => time() + static::OTP_EXPIRATION,
        ];

        update_option(static::OPTION_NAME, $otps);
    }

    /**
     * @param string $sessionId
     * @return void
     */
    protected function invalidateOtp(string $sessionId)
    {
        $userID = $this->getCurrentUserID();

        $otps = get_option(static::OPTION_NAME, []);
        unset($otps[$userID][$sessionId]);

        update_option(static::OPTION_NAME, $otps);
    }

    /**
     * @return string false if user has no cap to generate a new otp,
     */
    protected function generateOtp(): string
    {
        $min    = 10 ** (self::OTP_LENGTH - 1);
        $max    = 10 ** self::OTP_LENGTH;
        $newOtp = rand($min, $max - 1);
        // Early bail: A otp is always a 6-characters random string.
        if (strlen($newOtp) !== self::OTP_LENGTH) {
            throw new OtpException('Invalid OTP generated');
        }

        return (string)$newOtp;
    }

    protected function getCurrentUserID(): int
    {
        $user = wp_get_current_user();
        // Early bail: if current user is not instance of WP_User
        if (!$user instanceof WP_User) {
            throw new OtpException('Current user is not an instance of WP_User');
        }

        return (int)$user->ID;
    }
}
