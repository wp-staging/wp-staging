<?php

namespace WPStaging\Framework\Facades;

use WPStaging\Framework\Security\DataEncryption as SecurityDataEncryption;

/**
 * @method static string encrypt(string|int $value)
 * @method static string decrypt(string $value)
 * @method static bool isEncrypted(string $value)
 */
class DataEncryption extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SecurityDataEncryption::class;
    }
}
