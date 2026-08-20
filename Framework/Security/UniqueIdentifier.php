<?php

namespace WPStaging\Framework\Security;








class UniqueIdentifier
{
 
    const IDENTIFIER_OPTION_NAME = 'wpstg_unique_identifier';

 
    private $identifier;

 
    public function getIdentifier()
    {
 
        if (!empty($this->identifier)) {
            return $this->identifier;
        }

 
        $this->identifier = get_option(self::IDENTIFIER_OPTION_NAME);
        if (empty($this->identifier)) {
            $this->identifier = $this->generateIdentifier();
            update_option(self::IDENTIFIER_OPTION_NAME, $this->identifier);
        }

        return $this->identifier;
    }

 
    public function generateIdentifier($length = 16)
    {
        $allowedChars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $uniqueId     = '';

        for ($i = 0; $i < $length; $i++) {
            $index     = rand(0, strlen($allowedChars) - 1);
            $uniqueId .= $allowedChars[$index];
        }

        return $uniqueId;
    }
}
