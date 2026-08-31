<?php

namespace WPStaging\Backup\Service;

use WPStaging\Core\DTO\Settings;
use WPStaging\Core\WPStaging;







class UpdateProtectionSettings
{
 
    const INTRO_SURFACES = ['modal', 'notice'];




    public function isEnabled(): bool
    {
        return WPStaging::make(Settings::class)->isBackupBeforeUpdateEnabled();
    }




    public function getMode(): string
    {
        $mode = (string)get_option(Settings::OPTION_BACKUP_BEFORE_UPDATE_MODE, '');

        return $mode === '' ? 'ask' : $mode;
    }







    public function forgetMode()
    {
        delete_option(Settings::OPTION_BACKUP_BEFORE_UPDATE_MODE);
    }








    public function setEnabled(bool $isEnabled)
    {
        $settings = get_option('wpstg_settings', []);
        if (is_object($settings)) {
            $settings = (array)$settings;
        }

        if (!is_array($settings)) {
            $settings = [];
        }

        $settings['enableBackupBeforeUpdate'] = $isEnabled ? '1' : '0';
        update_option('wpstg_settings', $settings);

        if (!$isEnabled) {
            $this->forgetMode();
        }
    }




    public function getIntrosSeen(): array
    {
        return array_values(array_filter(explode(',', (string)get_option(Settings::OPTION_BACKUP_BEFORE_UPDATE_INTRO_SEEN, ''))));
    }





    public function hasSeenIntro(string $surface): bool
    {
        return in_array($surface, $this->getIntrosSeen(), true);
    }





    public function markIntroSeen(string $surface)
    {
        $seen = $this->getIntrosSeen();
        if (in_array($surface, $seen, true)) {
            return;
        }

        $seen[] = $surface;
        update_option(Settings::OPTION_BACKUP_BEFORE_UPDATE_INTRO_SEEN, implode(',', $seen));
    }
}
