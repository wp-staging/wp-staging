<?php

namespace WPStaging\Basic\Notices;

use DateTime;

/**
 * Snooze state for the compact general "Upgrade to Pro" card on the Staging
 * dashboard (views/ads/pro-upgrade-card.php).
 *
 * Dismissal is a 90-day snooze, not a permanent opt-out: clicking the card's X
 * hides only this one card for the current admin and lets it reappear once the
 * window passes. Contextual Pro prompts, Pro badges, the Upgrade navigation and
 * the permanent "Compare Free vs Pro" footer card are unaffected.
 *
 * State is stored per admin user (user meta), so one admin dismissing the card
 * does not hide it for every other admin on the site.
 */
class GeneralProCardNotice
{
    /** @var string */
    const META_KEY = 'wpstg_user_general_pro_card_snoozed_until';

    /** @var int */
    const SNOOZE_DAYS = 90;

    /**
     * Snooze the card for the current admin for SNOOZE_DAYS days.
     *
     * @return bool True when a current user exists and the snooze was stored.
     */
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

    /**
     * Whether the card is currently snoozed for the current admin.
     *
     * @return bool
     */
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
