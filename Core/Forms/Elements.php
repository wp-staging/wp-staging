<?php

namespace WPStaging\Core\Forms;

use WPStaging\Core\Forms\Elements\Interfaces\InterfaceElement;





abstract class Elements implements InterfaceElement
{




    protected $name;




    protected $id;




    protected $attributes = [];




    protected $label;




    protected $default;




    protected $filters = [];




    protected $validations = [];




    protected $renderFile;






    public function __construct($name, $attributes)
    {
        $this->setName($name);
        $this->setAttributes($attributes);
    }





    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }




    public function getName()
    {
        return $this->name;
    }






    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }





    public function setAttributes($attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }

        return $this;
    }




    public function getAttributes()
    {
        return $this->attributes;
    }




    public function prepareAttributes()
    {
        $attributes = '';
        foreach ($this->attributes as $name => $value) {
            $attributes .= "{$name}='{$value}' ";
        }

        if ($this->id) {
            $attributes .= "id='{$this->id}' ";
        }

        return rtrim($attributes, ' ');
    }




    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }




    public function getId()
    {
        return $this->id ?? '';
    }





    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }




    public function getLabel()
    {
        return $this->label;
    }




    public function prepareLabel()
    {
        return "<label for='{$this->getId()}'>{$this->label}</label>";
    }





    public function setFilters($filters)
    {
        if (is_string($filters)) {
            $this->filters[] = $filters;
        } else {
            array_merge($this->filters, $filters);
        }

        return $this;
    }




    public function getFilters()
    {
        return $this->filters;
    }





    public function setDefault($value)
    {
        $this->default = $value;

        return $this;
    }




    public function getDefault()
    {
        return $this->default;
    }





    public function addValidation($validation)
    {
        $this->validations[] = $validation;

        return $this;
    }




    public function getValidations()
    {
        return $this->validations;
    }





    public function setRenderFile($file)
    {
        if (file_exists($file) && is_readable($file)) {
            $this->renderFile = $file;
        }

        return $this;
    }




    public function getRenderFile()
    {
        return $this->renderFile;
    }




    public function __toString()
    {
        return $this->render();
    }




    abstract protected function prepareOutput();




    abstract public function render();
}
