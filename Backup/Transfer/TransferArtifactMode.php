<?php

namespace WPStaging\Backup\Transfer;




class TransferArtifactMode
{
 
    const HARDLINK = 'hardlink';

 
    const COPY = 'copy';

 
    const FAILED = 'failed';

 
    public static function getLabel(string $mode): string
    {
        if ($mode === self::HARDLINK) {
            return __('Fast mode: hardlink', 'wp-staging');
        }

        if ($mode === self::COPY) {
            return __('Compatibility mode: temporary copy', 'wp-staging');
        }

        return __('Unavailable', 'wp-staging');
    }
}
