<?php

namespace WPStaging\Framework\ThirdParty;








class FreemiusScript
{




    const NOTICE_OPTION = 'wpstg_freemius_notice';







    public function getFreemiusOptions()
    {
        return [
            'fs_accounts',
            'fs_dbg_accounts',
            'fs_active_plugins',
            'fs_api_cache',
            'fs_dbg_api_cache',
            'fs_debug_mode',
        ];
    }







    public function hasFreemiusOptions()
    {
        return get_option('fs_accounts') !== false;
    }







    public function isNoticeEnabled()
    {
        return get_option(self::NOTICE_OPTION, false);
    }







    public function disableNotice()
    {
        return delete_option(self::NOTICE_OPTION);
    }
}
