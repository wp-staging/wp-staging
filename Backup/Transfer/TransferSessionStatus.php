<?php

namespace WPStaging\Backup\Transfer;




class TransferSessionStatus
{
 
    const PREPARING = 'preparing';

 
    const READY = 'ready';

 
    const EXPIRED = 'expired';

 
    const REVOKED = 'revoked';

 
    const COMPLETED = 'completed';

 
    const FAILED = 'failed';

    public static function isTerminal(string $status): bool
    {
        return in_array($status, [self::EXPIRED, self::REVOKED, self::COMPLETED, self::FAILED], true);
    }
}
