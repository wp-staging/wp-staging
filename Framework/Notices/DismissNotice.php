<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Basic\Notices\GeneralProCardNotice;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\ThirdParty\WordFence;
use WPStaging\Pro\Notices\DismissNotice as DismissProNotice;

/**
 * Dismiss notice depending upon post request
 */
class DismissNotice
{
    /**
     * @var DisabledItemsNotice
     */
    private $disabledItemsNotice;

    /**
     * @var WarningsNotice
     */
    private $warningsNotice;

    /**
     * @var WordFence
     */
    private $wordFence;

    /**
     * @var ObjectCacheNotice
     */
    private $objectCacheNotice;

    public function __construct(DisabledItemsNotice $disabledItemsNotice, WarningsNotice $warningsNotice, WordFence $wordFence, ObjectCacheNotice $objectCacheNotice)
    {
        $this->disabledItemsNotice = $disabledItemsNotice;
        $this->warningsNotice      = $warningsNotice;
        $this->wordFence           = $wordFence;
        $this->objectCacheNotice   = $objectCacheNotice;
    }

    public function dismiss($noticeToDismiss)
    {
        // Compact general "Upgrade to Pro" card on the Staging dashboard.
        // Snoozes only this card for 90 days (per admin); contextual Pro prompts,
        // Pro badges and the Upgrade navigation keep working.
        if ($noticeToDismiss === 'general_pro_card') {
            // Report the actual outcome: snooze() returns false when there is no
            // current user, so the client can avoid hiding a card that was not stored.
            wp_send_json(WPStaging::make(GeneralProCardNotice::class)->snooze());
            return;
        }

        if ($noticeToDismiss === 'disabled_items' && $this->disabledItemsNotice->disable() !== false) {
            wp_send_json(true);
            return;
        }

        if ($noticeToDismiss === 'warnings_notice' && $this->warningsNotice->disable() !== false) {
            wp_send_json(true);
            return;
        }

        // Dismiss wordfence user.ini renamed notice
        if ($noticeToDismiss === WordFence::NOTICE_NAME && $this->wordFence->disable() !== false) {
            wp_send_json(true);
            return;
        }

        if ($noticeToDismiss === ObjectCacheNotice::ACTION_NOTICE_DISMISS && $this->objectCacheNotice->disable() !== false) {
            wp_send_json(true);
            return;
        }

        if (!WPStaging::isPro()) {
            wp_send_json(null);
            return;
        }

        /** @var DismissProNotice $dismissProNotice */
        $dismissProNotice = WPStaging::make(DismissProNotice::class);
        $dismissProNotice->dismiss($noticeToDismiss);
    }
}
