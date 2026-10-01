<?php

namespace WPStaging\Staging\Interfaces;




interface SubsiteUrlMappingDtoInterface
{
    public function setSubsiteUrlMappings(array $subsiteUrlMappings);

    public function getSubsiteUrlMappings(): array;
}
