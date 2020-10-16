<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Framework\Entity;

interface IdentifyableEntityInterface
{
    /**
     * @return string
     */
    public function getId();
}
