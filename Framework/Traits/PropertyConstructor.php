<?php








namespace WPStaging\Framework\Traits;






trait PropertyConstructor
{






    public function __construct(array $props = [])
    {
        foreach ($props as $prop => $value) {
            if (property_exists($this, $prop)) {
                $this->{$prop} = $value;
            }
        }
    }
}
