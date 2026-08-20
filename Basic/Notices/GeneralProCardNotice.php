<?php

namespace WPStaging\Basic\Notices;

use DateTime;













class GeneralProCardNotice
{
 
    const META_KEY = 'wpstg_user_general_pro_card_snoozed_until';

 
    const SNOOZE_DAYS = 90;






    public function snooze(): bool
    {
        $userId = get_current_user_id();
        if ($userId === 0) {
            return false;
        }

        $until = date('Y-m-d', strtotime('+' . self::SNOOZE_DAYS . ' days'));
        update_user_meta($userId, self::META_KEY, $until);

        return true;
    }






    public function isSnoozed(): bool
    {
        $userId = get_current_user_id();
        if ($userId === 0) {
            return false;
        }

        $until = get_user_meta($userId, self::META_KEY, true);
        if (!wpstg_is_valid_date($until)) {
            return false;
        }

        return new DateTime('now') < new DateTime($until);
    }
}
