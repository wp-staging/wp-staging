<?php

namespace WPStaging\Framework\Notices;

/**
 * Class BooleanNotice
 *
 * This class is used to reduce the boilerplate code for dismissible boolean notices
 *
 * @package WPStaging\Framework\Notices;
 */
abstract class BooleanNotice
{
    /**
     * The name of option on which the visibility of this notice is stored in db
     *
     * @return string
     */
    abstract public function getOptionName(): string;

    /**
     * Enable the option in database to show this notice
     */
    public function enable(): bool
    {
        return add_option($this->getOptionName(), true);
    }

    /**
     * Check whether to show this notice or not
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return get_option($this->getOptionName(), false);
    }

    /**
     * Delete the option in database to disable the notice
     *
     * @return bool
     */
    public function disable(): bool
    {
        return delete_option($this->getOptionName());
    }
}
