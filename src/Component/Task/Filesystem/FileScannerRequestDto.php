<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Task\Filesystem;

use WPStaging\Component\Dto\AbstractRequestDto;

class FileScannerRequestDto extends AbstractRequestDto
{

    /** @var array */
    private $included;

    /** @var array */
    private $excluded;

    /**
     * @return array
     */
    public function getIncluded()
    {
        return $this->included?: [];
    }

    public function setIncluded(array $included = null)
    {
        $this->included = $included;
    }

    /**
     * @return array
     */
    public function getExcluded()
    {
        return $this->excluded;
    }

    public function setExcluded(array $excluded = null)
    {
        $this->excluded = $excluded;
    }
}
