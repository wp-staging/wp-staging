<?php

namespace WPStaging\Framework\Security\Otp;

use WP_User;
use WPStaging\Framework\Security\Capabilities;
use WPStaging\Framework\Security\DataEncryption;











class Otp
{




    const OPTION_NAME = 'wpstg_otps';





    const OTP_EXPIRATION = 60 * 15; 





    const TRANSIENT_OTP_VERIFICATION_EXPIRY = 5; 





    const OTP_LENGTH = 6;





    const MAX_ALLOWED_CONSECUTIVE_OTP_FAILURES = 5;





    const TRANSIENT_CONSECTUTIVE_OTP_FAILURES = 'wpstg_otp_consecutive_failures';






    const TRANSIENT_CONSECTUTIVE_OTP_FAILURES_EXPIRY = 60 * 15; 





    const TRANSIENT_OTP_LOCKED_EXPIRY = 60; 





    const TRANSIENT_OTP_LOCKED = 'wpstg_otp_locked';




    private $capabilities;




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





    public function generateNewOtp(string $sessionId): string
    {
 
        if (!current_user_can($this->capabilities->manageWPSTG())) {
            throw new OtpException('Current user has no privilege to generate an OTP');
        }

        $otp = $this->generateOtp();
        $this->saveOtp($otp, $sessionId);

        return $otp;
    }





    public function validateOtp(string $otpToValidate, string $sessionId)
    {
 
        if (!current_user_can($this->capabilities->manageWPSTG())) {
            throw new OtpException('User has no privilege to validate a otp');
        }

 
        if (empty($otpToValidate) || strlen($otpToValidate) !== self::OTP_LENGTH) {
            throw new OtpException('Invalid OTP');
        }

        $savedOtp = $this->getSessionOtp($sessionId);
        if ($otpToValidate !== $savedOtp) {
            throw new FailedOtpException();
        }

 
        $this->invalidateOtp($sessionId);
    }






    public function validateOtpRequest()
    {
 
        if (!$this->isOtpFeatureEnabled()) {
            throw new OtpDisabledException();
        }

        $waitBeforeVerify = $this->checkOtpVerificationLocked();
        if ($waitBeforeVerify > 0) {
            throw new OtpException(sprintf(esc_html__('OTP verification is locked! Try again in after %s...', 'wp-staging'), esc_html((string)$waitBeforeVerify) . 's'), 403);
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
 
        if (!isset($otps[$userID])) {
            throw new OtpException('Current user is not an instance of WP_User');
        }

        if (empty($otps[$userID][$sessionId])) {
            throw new OtpException('No OTP for the session');
        }

        $otpData = $otps[$userID][$sessionId];

        return $otpData['created_at'];
    }




    public function lockOtpVerification()
    {
        $expiryTime = self::TRANSIENT_OTP_VERIFICATION_EXPIRY;
 
        $consecutiveFailures = get_transient(static::TRANSIENT_CONSECTUTIVE_OTP_FAILURES);
        if ($consecutiveFailures === false) {
            $consecutiveFailures = 0;
        }

        $consecutiveFailures++;
        set_transient(static::TRANSIENT_CONSECTUTIVE_OTP_FAILURES, $consecutiveFailures);
        if ($consecutiveFailures >= self::MAX_ALLOWED_CONSECUTIVE_OTP_FAILURES) {
            $expiryTime = self::TRANSIENT_OTP_LOCKED_EXPIRY;
 
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





    protected function getSessionOtp(string $sessionId): string
    {
        $userID = $this->getCurrentUserID();

        $otps = get_option(static::OPTION_NAME, []);
 
        if (!isset($otps[$userID])) {
            throw new OtpException('Current user is not an instance of WP_User');
        }

        if (empty($otps[$userID][$sessionId])) {
            throw new OtpException('No OTP for the session');
        }

        $otpData = $otps[$userID][$sessionId];
 
        if ($otpData['expiry_at'] < time()) {
            throw new ExpiredOtpException('OTP has expired');
        }

        return $this->dataEncryption->decrypt($otpData['otp']);
    }






    protected function saveOtp(string $otp, string $sessionId)
    {
        $userID = $this->getCurrentUserID();

        $otps = get_option(static::OPTION_NAME, []);
        $otps[$userID][$sessionId] = [
            'otp'        => $this->dataEncryption->encrypt($otp),
            'created_at' => time(),
            'expiry_at'  => time() + static::OTP_EXPIRATION,
        ];

        update_option(static::OPTION_NAME, $otps);
    }





    protected function invalidateOtp(string $sessionId)
    {
        $userID = $this->getCurrentUserID();

        $otps = get_option(static::OPTION_NAME, []);
        unset($otps[$userID][$sessionId]);

        update_option(static::OPTION_NAME, $otps);
    }




    protected function generateOtp(): string
    {
        $min    = 10 ** (self::OTP_LENGTH - 1);
        $max    = 10 ** self::OTP_LENGTH;
        $newOtp = rand($min, $max - 1);
 
        if (strlen((string)$newOtp) !== self::OTP_LENGTH) {
            throw new OtpException('Invalid OTP generated');
        }

        return (string)$newOtp;
    }

    protected function getCurrentUserID(): int
    {
        $user = wp_get_current_user();
 
        if (!$user instanceof WP_User) {
            throw new OtpException('Current user is not an instance of WP_User');
        }

        return (int)$user->ID;
    }
}
