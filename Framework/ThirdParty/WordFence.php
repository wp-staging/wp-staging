<?php

namespace WPStaging\Framework\ThirdParty;

use WPStaging\Framework\Notices\BooleanNotice;
use WPStaging\Framework\Notices\Notices;








class WordFence extends BooleanNotice
{




    const NOTICE_OPTION = 'wpstg_wordfence_notice';




    const NOTICE_NAME = 'wordfence_userini_renamed';

    const FILTER_ACTIVE_PLUGINS = 'active_plugins';




    public function renameUserIni()
    {
        $absolutePathToUserIni = ABSPATH . '/.user.ini';
 
        if (!file_exists($absolutePathToUserIni)) {
            return;
        }

        $activePlugins = apply_filters(self::FILTER_ACTIVE_PLUGINS, (array)get_option('active_plugins'));
        if (!in_array('wordfence/wordfence.php', $activePlugins)) {
            return;
        }

 
        rename($absolutePathToUserIni, $absolutePathToUserIni . '.bak');

 
        $this->enable();
    }




    public function isEnabled(): bool
    {
 
        if (!file_exists(ABSPATH . '/.user.ini.bak')) {
            return false;
        }

        return parent::isEnabled();
    }






    public function showNotice(string $viewsNoticesPath)
    {
        if (Notices::SHOW_ALL_NOTICES || $this->isEnabled()) {
            require "{$viewsNoticesPath}wordfence-userini-renamed.php";
        }
    }






    public function getOptionName(): string
    {
        return self::NOTICE_OPTION;
    }
}
