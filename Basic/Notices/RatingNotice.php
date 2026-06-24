<?php

namespace WPStaging\Basic\Notices;

use DateTime;

class RatingNotice
{
    /** @var string */
    const OPTION_NAME = 'wpstg_rating';

    /**
     * Shared eligibility gate for the success-based review prompt, used by both
     * the staging and backup completion modals (views/notices/review-prompt-modal.php).
     *
     * It only answers "is the user currently allowed to be asked?", i.e. the
     * review prompt has not been permanently dismissed and is not inside a snooze
     * window. The "value delivered" trigger (a staging site or backup was just
     * created) is the caller's responsibility — the prompt is rendered into the
     * success modal, never as loose dashboard text.
     *
     * Because every surface reads and writes the same wpstg_rating state, a
     * "Maybe Later" or "Don't Ask Again" in one place silences the prompt
     * everywhere, so the user is never asked twice across workflows.
     *
     * @return bool
     */
    public function isReviewPromptEligible(): bool
    {
        return $this->canShow(self::OPTION_NAME);
    }

    /**
     * Check whether the prompt is currently dismissed or snoozed.
     *
     * @param string $option
     * @return bool
     */
    private function canShow($option)
    {
        if (empty($option)) {
            return false;
        }

        $dbOption = get_option($option);

        // Permanently dismissed via "Don't Ask Again" / "Leave a Review".
        if ($dbOption === "no") {
            return false;
        }

        // Snoozed via "Maybe Later": a valid date means "do not show before then".
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
