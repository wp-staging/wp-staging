<?php

namespace WPStaging\Basic\Notices;




class GeneralProCardNotice extends AbstractProUpgradeCardNotice
{
 
    const NOTICE_ID = 'general_pro_card';

 
    const META_KEY = 'wpstg_user_general_pro_card_snoozed_until';




    protected function getSnoozeMetaKey(): string
    {
        return self::META_KEY;
    }




    protected function getCardName(): string
    {
        return 'staging';
    }
}
