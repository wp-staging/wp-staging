<?php

// TODO PHP7.x; declare(strict_types=1);

namespace WPStaging\Service\Entity;

interface IdentifyableEntityInterface
{
    /**
     * @return string
     */
    public function getId();
}
