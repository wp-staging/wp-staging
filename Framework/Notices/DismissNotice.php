<?php

namespace WPStaging\Framework\Notices;

use WPStaging\Framework\Notices\DisabledItemsNotice;
use WPStaging\Framework\Notices\WarningsNotice;
use WPStaging\Core\WPStaging;
use WPStaging\Framework\Support\ThirdParty\WordFence;
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

    public function __construct(DisabledItemsNotice $disabledItemsNotice, WarningsNotice $warningsNotice, WordFence $wordFence)
    {
        $this->disabledItemsNotice = $disabledItemsNotice;
        $this->warningsNotice      = $warningsNotice;
        $this->wordFence           = $wordFence;
    }

    public function dismiss($noticeToDismiss)
    {
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

        if (!WPStaging::isPro()) {
            wp_send_json(null);
            return;
        }

        /** @var DismissProNotice */
        $dismissProNotice = WPStaging::make(DismissProNotice::class);
        $dismissProNotice->dismiss($noticeToDismiss);
    }
}
