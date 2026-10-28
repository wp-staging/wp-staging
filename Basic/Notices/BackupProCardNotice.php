<?php

namespace WPStaging\Basic\Notices;




class BackupProCardNotice extends AbstractProUpgradeCardNotice
{
 
    const NOTICE_ID = 'backup_pro_card';

 
    const META_KEY = 'wpstg_user_backup_pro_card_snoozed_until';




    protected function getSnoozeMetaKey(): string
    {
        return self::META_KEY;
    }




    protected function getCardName(): string
    {
        return 'backup';
    }
}
