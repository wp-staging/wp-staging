<?php

namespace WPStaging\Framework\Permalinks;

use WPStaging\Framework\Traits\EventLoggerTrait;

class PermalinksPurge
{
    use EventLoggerTrait;

    const PLUGINS_LOADED_PRIORITY = 99999;
    const TRANSIENT = "wpstg_permalinks_do_purge";

    public function executeAfterPushing()
    {
        set_transient(self::TRANSIENT, "true");
        $this->pushProcessCompleted();
    }

    public function purgePermalinks()
    {
        if (get_transient(self::TRANSIENT)) {
            delete_transient(self::TRANSIENT);
            flush_rewrite_rules(false);
        }
    }
}
