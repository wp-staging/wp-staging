<?php

namespace WPStaging\Backup\Transfer;

use Exception;





class TransferTokenGenerator
{
 
    const TOKEN_BYTES = 32;

 
    const TOKEN_PREFIX_LENGTH = 8;

 
    const TOKEN_PATTERN = '/^[A-Za-z0-9_-]{43}$/';

 
    const TOKEN_IN_TEXT_PATTERN = '/(?<![A-Za-z0-9_-])[A-Za-z0-9_-]{43}(?![A-Za-z0-9_-])/';





    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::TOKEN_BYTES)), '+/', '-_'), '=');
    }

 
    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

 
    public function getPrefix(string $token): string
    {
        return substr($token, 0, self::TOKEN_PREFIX_LENGTH);
    }

 
    public function redactTokens(string $text): string
    {
        return (string)preg_replace_callback(self::TOKEN_IN_TEXT_PATTERN, function (array $matches) {
            return $this->getPrefix($matches[0]) . '...';
        }, $text);
    }

 
    public function isValidFormat(string $token): bool
    {
        return preg_match(self::TOKEN_PATTERN, $token) === 1;
    }
}
