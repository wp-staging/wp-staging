<?php

namespace WPStaging\Framework\Notices;








abstract class BooleanNotice
{





    abstract public function getOptionName(): string;




    public function enable(): bool
    {
        return add_option($this->getOptionName(), true);
    }






    public function isEnabled(): bool
    {
        return get_option($this->getOptionName(), false);
    }






    public function disable(): bool
    {
        return delete_option($this->getOptionName());
    }
}
