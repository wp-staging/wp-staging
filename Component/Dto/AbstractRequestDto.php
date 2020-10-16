<?php

// TODO PHP7.x declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Component\Dto;

abstract class AbstractRequestDto extends AbstractDto
{

    /** @var StepsDto */
    protected $steps;

    /**
     * @return StepsDto
     */
    public function getSteps()
    {
        if (!$this->steps) {
            $this->steps = (new StepsDto)->hydrate([
                'current' => 0,
                'total' => 0,
            ]);
        }
        return $this->steps;
    }

    /** @noinspection PhpUnused */
    public function setSteps(StepsDto $steps)
    {
        $this->steps = $steps;
    }
}
