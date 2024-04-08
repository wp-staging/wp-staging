<?php

namespace WPStaging\Framework\Notices;

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

    /**
     * @var FreeBackupUpdateNotice
     */
    private $freeBackupUpdateNotice;

    public function __construct(DisabledItemsNotice $disabledItemsNotice, WarningsNotice $warningsNotice, WordFence $wordFence, ObjectCacheNotice $objectCacheNotice, FreeBackupUpdateNotice $freeBackupUpdateNotice)
    {
        $this->disabledItemsNotice = $disabledItemsNotice;
        $this->warningsNotice      = $warningsNotice;
        $this->wordFence           = $wordFence;
        $this->objectCacheNotice   = $objectCacheNotice;
        $this->freeBackupUpdateNotice   = $freeBackupUpdateNotice;
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

        if ($noticeToDismiss === ObjectCacheNotice::NOTICE_DISMISS_ACTION && $this->objectCacheNotice->disable() !== false) {
            wp_send_json(true);
            return;
        }

        if ($noticeToDismiss === FreeBackupUpdateNotice::OPTION_NAME_FREE_BACKUP_NOTICE_DISMISSED && $this->freeBackupUpdateNotice->disable() !== false) {
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
