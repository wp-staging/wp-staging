<?php

namespace WPStaging\Basic\Notices;

use DateTime;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Analytics\Actions\AnalyticsGenericEvent;






abstract class AbstractProUpgradeCardNotice
{
 
    const SNOOZE_DAYS = 30;

 
    const ANALYTICS_GROUP = 'upgrade';

 
    const EVENT_DISMISSED = 'pro_card_dismissed';




    abstract protected function getSnoozeMetaKey(): string;




    abstract protected function getCardName(): string;






    public function isShownToCurrentUser(): bool
    {
        if (WPStaging::isPro()) {
            return false;
        }

        if (!current_user_can('manage_options')) {
            return false;
        }

        return !$this->isSnoozed();
    }






    public function snooze(): bool
    {
        $userId = get_current_user_id();
        if ($userId === 0) {
            return false;
        }

        if ($this->isSnoozed()) {
            return true;
        }

        $dismissal = $this->hasUserDismissedCardBefore($userId) ? 'repeat' : 'first';

        $until = date('Y-m-d', strtotime('+' . static::SNOOZE_DAYS . ' days'));
        update_user_meta($userId, $this->getSnoozeMetaKey(), $until);

        AnalyticsGenericEvent::logEvent(self::EVENT_DISMISSED, self::ANALYTICS_GROUP, [
            'card'      => $this->getCardName(),
            'dismissal' => $dismissal,
        ]);

        return true;
    }






    public function isSnoozed(): bool
    {
        $userId = get_current_user_id();
        if ($userId === 0) {
            return false;
        }

        $until = get_user_meta($userId, $this->getSnoozeMetaKey(), true);
        if (!wpstg_is_valid_date($until)) {
            return false;
        }

        return new DateTime('now') < new DateTime($until);
    }





    private function hasUserDismissedCardBefore(int $userId): bool
    {
        return metadata_exists('user', $userId, $this->getSnoozeMetaKey());
    }
}
