<?php

namespace WPStaging\Framework\Support\ThirdParty;

use WPStaging\Backend\Notices\BooleanNotice;
use WPStaging\Backend\Notices\Notices;

/**
 * Class WordFence
 *
 * Rename .user.ini to .user.ini.bak and show notice regarding this on staging site
 *
 * @package WPStaging\Framework\Support\ThirdParty
 */
class WordFence extends BooleanNotice
{
    /**
     * The option_name that is stored in the database to check if the freemius notice to be shown
     * on the staging site
     */
    const NOTICE_OPTION = 'wpstg_wordfence_notice';

    /**
     * Notice parameter on which wordfence user.ini renamed notice will be dismissed
     */
    const NOTICE_NAME = 'wordfence_userini_renamed';

    /**
     * Check if .user.ini file exists. If it exists rename it to .user.ini.bak and enable the notice
     */
    public function renameUserIni()
    {
        $absolutePathToUserIni = ABSPATH . '/.user.ini';
        // Early bail if not exist
        if (!file_exists($absolutePathToUserIni)) {
            return;
        }

        // Rename by appending .bak to its name
        rename($absolutePathToUserIni, $absolutePathToUserIni . '.bak');

        // Enable the notice
        $this->enable();
    }

    /**
     * Show the notice if the notice flag is enabled
     *
     * @param string $viewsNoticesPath
     */
    public function showNotice($viewsNoticesPath)
    {
        if (Notices::SHOW_ALL_NOTICES || $this->isEnabled()) {
            require "{$viewsNoticesPath}wordfence-userini-renamed.php";
        }
    }

    /**
     * The name of option on which the visibility of this notice is stored in db
     *
     * @return string
     */
    public function getOptionName()
    {
        return self::NOTICE_OPTION;
    }
}
