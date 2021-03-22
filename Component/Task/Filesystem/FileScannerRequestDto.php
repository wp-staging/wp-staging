<?php

// TODO PHP7.x; declare(strict_type=1);
// TODO PHP7.x; type hints & return types

namespace WPStaging\Component\Task\Filesystem;

use WPStaging\Component\Dto\AbstractRequestDto;

class FileScannerRequestDto extends AbstractRequestDto
{
    /** @var array */
    private $included = [];

    /** @var array */
    private $excluded = [];

    /** @var bool */
    private $includeOtherFilesInWpContent;

    /**
     * @return array
     */
    public function getIncluded()
    {
        return (array)$this->included;
    }

    public function setIncluded(array $included = [])
    {
        $this->included = $included;
    }

    /**
     * @return array
     */
    public function getExcluded()
    {
        return (array)$this->excluded;
    }

    public function setExcluded(array $excluded = [])
    {
        $this->excluded = $excluded;
    }

    /**
     * @return bool
     */
    public function getIncludeOtherFilesInWpContent()
    {
        return (bool)$this->includeOtherFilesInWpContent;
    }

    public function setIncludeOtherFilesInWpContent($includeOtherFilesInWpContent)
    {
        $this->includeOtherFilesInWpContent = $includeOtherFilesInWpContent;
    }
}
