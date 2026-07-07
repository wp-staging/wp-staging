<?php

namespace WPStaging\Framework\Notices;

/**
 * Global warning shown when the user was reverted from the Next-Gen engine and
 * may have staging sites that were created with it. Those sites can be corrupted
 * (see issue #5346) and must be deleted and recreated with the Classic engine.
 *
 * Enabled by the upgrade routine, dismissed via the shared wpstg_dismiss_notice flow.
 *
 * @see \WPStaging\Framework\Notices\Notices
 * @see \WPStaging\Framework\Notices\DismissNotice
 */
class NextGenEngineNotice extends BooleanNotice
{
    /**
     * The option name storing the visibility of the Next-Gen engine warning.
     */
    const OPTION_NAME = 'wpstg_next_gen_engine_notice';

    public function getOptionName(): string
    {
        return self::OPTION_NAME;
    }
}
