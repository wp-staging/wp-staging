<?php

namespace WPStaging\Framework\Permalinks;

class PermalinksPurge
{

    const PLUGINS_LOADED_PRIORITY = 99999;
    const TRANSIENT = "wpstg_permalinks_do_purge";

    public function executeAfterPushing()
    {
        set_transient(self::TRANSIENT, "true");
    }

    public function purgePermalinks()
    {
        if (get_transient(self::TRANSIENT)) {
            delete_transient(self::TRANSIENT);
            flush_rewrite_rules(false);
        }
    }
}
