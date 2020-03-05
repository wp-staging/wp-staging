<?php

// TODO PHP7.x; declare(strict_types=1);
// TODO PHP7.x; type-hints & return types

namespace WPStaging\Service\Interfaces;

interface ArrayableInterface
{
    /**
     * @return array
     */
    public function toArray();
}
