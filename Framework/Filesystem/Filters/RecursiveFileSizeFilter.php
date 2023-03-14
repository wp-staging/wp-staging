<?php

namespace WPStaging\Framework\Filesystem\Filters;

use RecursiveFilterIterator;
use RecursiveDirectoryIterator;

class RecursiveFileSizeFilter extends RecursiveFilterIterator
{
    private $sizeFilters = [];
    public function __construct(RecursiveDirectoryIterator $iterator, $sizeFilters = [])
    {
        parent::__construct($iterator);
        $this->sizeFilters = $sizeFilters;
    }

    #[\ReturnTypeWillChange]
    public function accept()
    {
        $current = $this->current();

        if (!$current->isFile()) {
            return true;
        }

        $fileSize = $current->getSize();
        foreach ($this->sizeFilters as $sizeFilter) {
            $sizeComparator = explode(' ', $sizeFilter);
            if (count($sizeComparator) !== 2) {
                continue;
            }

            if ($this->compareBytes($fileSize, $sizeComparator[1], $sizeComparator[0])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compare Bytes
     *
     * @param string|int $compare
     * @param string|int $compareWith
     * @param string $comparator
     * @return bool
     */
    public function compareBytes($compare, $compareWith, $comparator = '=')
    {
        $compare = wp_convert_hr_to_bytes($compare);
        $compareWith = wp_convert_hr_to_bytes($compareWith);

        // comparison for equal to
        if (($comparator === '=' || $comparator === ExcludeFilter::SIZE_EQUAL_TO) && ($compare == $compareWith)) {
            return true;
        }

        // comparison for greater than
        if (($comparator === '>' || $comparator === ExcludeFilter::SIZE_GREATER_THAN) && ($compare > $compareWith)) {
            return true;
        }

        // comparison for less than
        if (($comparator === '<' || $comparator === ExcludeFilter::SIZE_LESS_THAN) && ($compare < $compareWith)) {
            return true;
        }

        return false;
    }

    #[\ReturnTypeWillChange]
    public function getChildren()
    {
        return new self($this->getInnerIterator()->getChildren(), $this->sizeFilters);
    }
}
