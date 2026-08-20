<?php

namespace WPStaging\Framework\Job\Dto\Traits;

trait DateCreatedTrait
{
 
    private $dateCreated;

 
    private $dateCreatedTimezone;




    public function getDateCreated()
    {
        return (string)$this->dateCreated;
    }




    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
    }




    public function getDateCreatedTimezone()
    {
        return (string)$this->dateCreatedTimezone;
    }




    public function setDateCreatedTimezone($dateCreatedTimezone)
    {
        $this->dateCreatedTimezone = $dateCreatedTimezone;
    }
}
