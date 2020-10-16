<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x type hints & return types
// TODO PHP7.1; constant visibility

namespace WPStaging\Repository;

use WPStaging\Entity\Settings;

class SettingsRepository
{
    const OPTION_NAME = 'wpstg_settings';

    /**
     * @return Settings
     */
    public function find()
    {
        $records = get_option(self::OPTION_NAME, []);
        if (!$records || !is_array($records)) {
            return new Settings;
        }

        /** @var Settings|null $settings */
        $settings = (new Settings)->hydrate($records);
        return $settings;
    }

    /**
     * @param Settings $settings
     * @return bool
     */
    public function save(Settings $settings)
    {
        $data = $settings->toArray();
        $existing = $this->find();
        if ($existing && $data === $existing->toArray()) {
            return true;
        }
        return update_option(self::OPTION_NAME, $data, false);
    }
}