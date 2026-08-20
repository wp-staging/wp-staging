<?php

namespace WPStaging\Basic\Notices;

use DateTime;

class RatingNotice
{
 
    const OPTION_NAME = 'wpstg_rating';

















    public function isReviewPromptEligible(): bool
    {
        return $this->canShow(self::OPTION_NAME);
    }







    private function canShow($option)
    {
        if (empty($option)) {
            return false;
        }

        $dbOption = get_option($option);

 
        if ($dbOption === "no") {
            return false;
        }

 
        if (wpstg_is_valid_date($dbOption)) {
            $now  = new DateTime("now");
            $show = new DateTime($dbOption);
            if ($now < $show) {
                return false;
            }
        }

        return true;
    }
}
