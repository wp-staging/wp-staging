<?php

// TODO PHP7.x declare(strict_types=1);
// TODO PHP7.x type-hints & return types

namespace WPStaging\Component\Job\Dto;

use JsonSerializable;
use Serializable;
use WPStaging\Framework\Traits\ArrayableTrait;
use WPStaging\Framework\Traits\HydrateTrait;

class ResponseDto implements JsonSerializable, Serializable
{
    use ArrayableTrait;
    use HydrateTrait;

    /** @var string */
    private $status;

    /** @var int */
    private $percentage;

    /** @var int */
    private $total;

    /** @var int */
    private $step;

    /** @var string */
    private $job;

    /** @var string */
    private $message;

    /** @var float */
    private $time;

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $this->hydrate(unserialize($serialized));
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }


}
