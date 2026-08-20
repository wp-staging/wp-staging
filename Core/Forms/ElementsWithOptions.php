<?php

namespace WPStaging\Core\Forms;

use WPStaging\Core\Forms\Elements\Interfaces\InterfaceElementWithOptions;





abstract class ElementsWithOptions extends Elements implements InterfaceElementWithOptions
{




    protected $options = [];







    public function __construct($name, $options = [], $attributes = [])
    {
        parent::__construct($name, $attributes);
        $this->addOptions($options);
    }






    public function addOption($id, $name)
    {
        $this->options[$id] = $name;

        return $this;
    }





    public function removeOption($id)
    {
        if (isset($this->options[$id])) {
            unset($this->options[$id]);
        }

        return $this;
    }





    public function addOptions($options)
    {
        foreach ($options as $id => $name) {
            $this->addOption($id, $name);
        }

        return $this;
    }




    public function getOptions()
    {
        return $this->options;
    }
}
