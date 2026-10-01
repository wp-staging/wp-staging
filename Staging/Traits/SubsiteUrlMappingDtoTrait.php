<?php

namespace WPStaging\Staging\Traits;




trait SubsiteUrlMappingDtoTrait
{
 
    private $subsiteUrlMappings = [];





    public function setSubsiteUrlMappings(array $subsiteUrlMappings)
    {
        $this->subsiteUrlMappings = $subsiteUrlMappings;
    }




    public function getSubsiteUrlMappings(): array
    {
        return $this->subsiteUrlMappings;
    }
}
