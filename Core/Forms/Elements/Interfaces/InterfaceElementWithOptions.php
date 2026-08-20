<?php

namespace WPStaging\Core\Forms\Elements\Interfaces;





interface InterfaceElementWithOptions
{






    public function addOption($id, $name);





    public function removeOption($id);





    public function addOptions($options);




    public function getOptions();
}
