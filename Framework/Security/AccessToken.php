<?php

namespace WPStaging\Framework\Security;

/**
 * Class AccessToken
 *
 * WPSTAGING own implementation of a WordPress nonce,
 * with the difference that it's generated and invalidated
 * under our own criteria.
 *
 * @package WPStaging\Framework\Security
 */
class AccessToken
{
    /**
     * The key in $_REQUEST that AccessToken expects to find a token.
     */
    const REQUEST_KEY = 'accessToken';

    /**
     * The option_name that is stored in the database.
     */
    const OPTION_NAME = 'wpstg_access_token';

    /**
     * @return bool Whether the current $_REQUEST has a valid token.
     */
    public function requestHasValidToken()
    {
        return isset($_REQUEST[self::REQUEST_KEY]) && $this->isValidToken(sanitize_text_field($_REQUEST[self::REQUEST_KEY]));
    }

    /**
     * @return string The new access token
     * @return bool False if user has no cap to generate a new token,
     *              or the generated token does not comply with the standards.
     */
    public function generateNewToken()
    {
        // Early bail: Not enough privilege to generate a token. Todo: Remove "new" once we have DI
        if (! current_user_can((new Capabilities())->manageWPSTG())) {
            return false;
        }

        $newToken = wp_generate_password(64, false);

        // Early bail: A token is always a 64-character random string.
        if (strlen($newToken) !== 64) {
            return false;
        }

        /* literals that start with 0x are hexadecimal integers (base 16) and can lead to blocked requests by mod_security
         * Issue: https://github.com/wp-staging/wp-staging-pro/issues/1650
         */
        $sanitizedToken = str_ireplace('0x', 'ax', $newToken);

        update_option(static::OPTION_NAME, $sanitizedToken);

        return $sanitizedToken;
    }

    /**
     * @param string $newToken The new token, a 64-char string.
     *
     * @return false|mixed
     */
    public function setToken($newToken)
    {
        // Early bail: Not enough privilege to generate a token.
        if (! current_user_can((new Capabilities())->manageWPSTG())) {
            return false;
        }

        // Early bail: A token is always a 64-character random string.
        if (strlen($newToken) !== 64) {
            return false;
        }

        update_option(static::OPTION_NAME, $newToken);

        return $newToken;
    }

    /**
     * Gets the token. Requires user to be logged-in.
     *
     * @return string The access token or an empty string if no token exists.
     * @return bool False if user has no cap to read the token.
     */
    public function getToken()
    {
        // Early bail: Not enough privilege to get a token. Todo: Remove "new" once we have DI
        if (! current_user_can((new Capabilities())->manageWPSTG())) {
            return false;
        }

        return (string)get_option(static::OPTION_NAME, null);
    }

    /**
     * Check if given token is valid. Does not require user to be logged-in.
     *
     * @param string $tokenToValidate A token to compare with the saved token.
     *
     * @return bool Whether given token is valid.
     */
    public function isValidToken($tokenToValidate)
    {
        // Early bail: A token is always a 64-character random string.
        if (strlen($tokenToValidate) !== 64) {
            return false;
        }

        $savedToken      = (string)get_option(static::OPTION_NAME, null);
        $tokenToValidate = (string)$tokenToValidate;

        // Early bail: We can't validate a token because at least one of the parts are empty.
        if (empty($savedToken) || empty($tokenToValidate)) {
            return false;
        }

        return $tokenToValidate === $savedToken;
    }
}
