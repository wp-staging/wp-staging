<?php

namespace WPStaging\Framework\Security;

/**
 * Class UniqueIdentifier
 *
 * Use to create a unique id that is currently used in cloning related logs file names
 *
 * @package WPStaging\Framework\Security
 */
class UniqueIdentifier
{
    /** @var string */
    const IDENTIFIER_OPTION_NAME = 'wpstg_unique_identifier';

    /** @var string */
    private $identifier;

    /** @return string */
    public function getIdentifier()
    {
        // Early bail: if unique identifier is already set
        if (!empty($this->identifier)) {
            return $this->identifier;
        }

        // Cache the result
        $this->identifier = get_option(self::IDENTIFIER_OPTION_NAME);
        if (empty($this->identifier)) {
            $this->identifier = $this->generateIdentifier();
            update_option(self::IDENTIFIER_OPTION_NAME, $this->identifier);
        }

        return $this->identifier;
    }

    /** @return string */
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
