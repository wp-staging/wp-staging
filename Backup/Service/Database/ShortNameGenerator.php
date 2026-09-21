<?php

namespace WPStaging\Backup\Service\Database;




class ShortNameGenerator
{
 
    const MAX_IDENTIFIER_LENGTH = 64;

 
    const MAX_HASH_LENGTH = 19;






    public static function generate(string $name, string $prefix): string
    {
        $availableHashLength = self::MAX_IDENTIFIER_LENGTH - strlen($prefix);
        if ($availableHashLength < self::MAX_HASH_LENGTH) {
            throw new \InvalidArgumentException(sprintf('The database identifier prefix must not exceed %d characters.', self::MAX_IDENTIFIER_LENGTH - self::MAX_HASH_LENGTH));
        }

        return $prefix . substr(md5($name), 0, self::MAX_HASH_LENGTH);
    }
}
