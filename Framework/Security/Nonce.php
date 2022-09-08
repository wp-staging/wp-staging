<?php

namespace WPStaging\Framework\Security;

/**
 * Class Nonce
 *
 * Hold the nonces used in the application.
 *
 * @package WPStaging\Framework\Security
 */
class Nonce
{
    /**
     * The main nonce.
     *
     * @todo Add a different nonce for each action.
     */
    const WPSTG_NONCE = 'wpstg_nonce';

    /**
     * Helper method to verify given nonce, if it exists.
     *
     * @param string $action The nonce name to check.
     *
     * @return bool True if request has valid given nonce action. False otherwise.
     */
    public function requestHasValidNonce($action)
    {
        return isset($_REQUEST['nonce']) && wp_verify_nonce(sanitize_text_field($_REQUEST['nonce']), $action) !== false;
    }
}
