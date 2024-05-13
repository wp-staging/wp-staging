<?php

namespace WPStaging\Framework\Notices;

/**
 * Class FreeBackupUpdateNotice
 *
 * This class is used to show a notice in free version that we added backup feature in free version
 *
 * @see Notices
 * @todo move this to Basic/Notices
 */
class FreeBackupUpdateNotice
{
    /**
     * @var string
     * The option name to store the visibility of disabled backup notice
     */
    const OPTION_NAME_FREE_BACKUP_NOTICE_DISMISSED = 'wpstg_free_backup_notice_dismissed';

    public function getOptionName(): string
    {
        return self::OPTION_NAME_FREE_BACKUP_NOTICE_DISMISSED;
    }

    /**
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        if (get_option($this->getOptionName()) === false) {
            return true;
        }

        return false;
    }

    /**
     * Add the option in database to dismiss the backup notice
     *
     * @return bool
     */
    public function disable(): bool
    {
        return add_option($this->getOptionName(), true);
    }

    /**
     * Enables the notice
     *
     * @return bool
     */
    public function enable(): bool
    {
        return delete_option($this->getOptionName());
    }
}
