<?php

namespace WPStaging\Basic\Notices;

use DateTime;
use Exception;

class RatingNotice
{
    /** @var string */
    const OPTION_NAME = 'wpstg_rating';

    /** @var int */
    const DAYS_TO_SHOW_RATING_NOTICE_AFTER = 7;

    public function shouldShowRatingNotice()
    {
        return $this->canShow(self::OPTION_NAME, self::DAYS_TO_SHOW_RATING_NOTICE_AFTER) && $this->getCurrentPage() !== 'page' && $this->getCurrentPage() !== 'post';
    }

    /**
     * Check if notice should be shown after certain days of installation or if it is dismissed
     * @param int $days default 10
     * @return bool
     */
    private function canShow($option, $days = 10)
    {
        // Do not show notice
        if (empty($option)) {
            return false;
        }

        $dbOption = get_option($option);

        // Do not show notice
        if ($dbOption === "no") {
            return false;
        }

        $now = new DateTime("now");

        // Check if user clicked on "rate later" button and if there is a valid 'later' date
        if (wpstg_is_valid_date($dbOption)) {
            // Do not show before this date
            $show = new DateTime($dbOption);
            if ($now < $show) {
                return false;
            }
        }

        // Show X days after installation
        $installDate = new DateTime(get_option("wpstg_installDate"));

        // get number of days between installation date and today
        $difference = $now->diff($installDate)->days;

        return $days <= $difference;
    }

    /**
     * Get current page.
     * Note: This can not be moved to wpAdapter class as it is only available very late
     * at add admin_init and not available most of the time.
     *
     * @return string post, page
     */
    private function getCurrentPage()
    {
        if (function_exists('\get_current_screen')) {
            return \get_current_screen()->post_type;
        }

        throw new Exception('Function get_current_screen does not exist. WP < 3.0.1.');
    }
}
