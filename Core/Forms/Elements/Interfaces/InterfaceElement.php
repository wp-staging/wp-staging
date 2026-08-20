<?php

namespace WPStaging\Core\Forms\Elements\Interfaces;





interface InterfaceElement
{





    public function setName($name);




    public function getName();






    public function setAttribute($name, $value);





    public function setAttributes($attributes);




    public function getAttributes();




    public function prepareAttributes();




    public function setId(string $id);




    public function getId();





    public function setLabel($label);




    public function getLabel();




    public function prepareLabel();





    public function setFilters($filters);




    public function getFilters();





    public function setDefault($value);




    public function getDefault();





    public function addValidation($validation);




    public function getValidations();





    public function setRenderFile($file);




    public function getRenderFile();




    public function __toString();




    public function render();
}
