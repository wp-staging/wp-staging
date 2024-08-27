<?php

namespace WPStaging\Framework\Job\Dto\Traits;

trait DateCreatedTrait
{
    /** @var string */
    private $dateCreated;

    /** @var string */
    private $dateCreatedTimezone;

    /**
     * @return string
     */
    public function getDateCreated()
    {
        return (string)$this->dateCreated;
    }

    /**
     * @param string $dateCreated
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;
    }

    /**
     * @return string
     */
    public function getDateCreatedTimezone()
    {
        return (string)$this->dateCreatedTimezone;
    }

    /**
     * @param string $dateCreatedTimezone
     */
    public function setDateCreatedTimezone($dateCreatedTimezone)
    {
        $this->dateCreatedTimezone = $dateCreatedTimezone;
    }
}
