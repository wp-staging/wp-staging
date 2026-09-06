<?php

namespace WPStaging\Framework\Security;










class AccessToken
{



    const REQUEST_KEY = 'accessToken';




    const OPTION_NAME = 'wpstg_access_token';




    private $isCheckCapabilities = true;





    public function setIsCheckCapabilities(bool $isCheckCapabilities = true)
    {
        $this->isCheckCapabilities = $isCheckCapabilities;
    }




    public function requestHasValidToken()
    {
        return isset($_REQUEST[self::REQUEST_KEY]) && $this->isValidToken(sanitize_text_field($_REQUEST[self::REQUEST_KEY]));
    }






    public function generateNewToken()
    {
 
        if ($this->isCheckCapabilities && !$this->currentUserCanManageWPSTG()) {
            return false;
        }

        $newToken = wp_generate_password(64, false);

 
        if (strlen($newToken) !== 64) {
            return false;
        }




        $sanitizedToken = str_ireplace('0x', 'ax', $newToken);

        update_option(static::OPTION_NAME, $sanitizedToken);

        return $sanitizedToken;
    }






    public function setToken($newToken)
    {
 
        if ($this->isCheckCapabilities && !$this->currentUserCanManageWPSTG()) {
            return false;
        }

 
        if (strlen($newToken) !== 64) {
            return false;
        }

        update_option(static::OPTION_NAME, $newToken);

        return $newToken;
    }







    public function getToken()
    {
 
        if ($this->isCheckCapabilities && !$this->currentUserCanManageWPSTG()) {
            return false;
        }

        return (string)get_option(static::OPTION_NAME, null);
    }








    public function isValidToken($tokenToValidate)
    {
        $tokenToValidate = (string)$tokenToValidate;

 
        if (empty($tokenToValidate) || strlen($tokenToValidate) !== 64) {
            return false;
        }

        $savedToken = (string)get_option(static::OPTION_NAME, null);

 
        if (empty($savedToken)) {
            return false;
        }

        return hash_equals($savedToken, $tokenToValidate);
    }




    private function currentUserCanManageWPSTG()
    {
        return current_user_can((new Capabilities())->manageWPSTG());
    }
}
