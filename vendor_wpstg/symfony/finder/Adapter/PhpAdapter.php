<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WPStaging\Vendor\Symfony\Component\Finder\Adapter;

@\trigger_error('The ' . __NAMESPACE__ . '\\PhpAdapter class is deprecated since Symfony 2.8 and will be removed in 3.0. Use directly the Finder class instead.', \E_USER_DEPRECATED);
use WPStaging\Vendor\Symfony\Component\Finder\Iterator;
/**
 * PHP finder engine implementation.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 *
 * @deprecated since 2.8, to be removed in 3.0. Use Finder instead.
 */
class PhpAdapter extends \WPStaging\Vendor\Symfony\Component\Finder\Adapter\AbstractAdapter
{
    /**
     * {@inheritdoc}
     */
    public function searchInDirectory($dir)
    {
        $flags = \RecursiveDirectoryIterator::SKIP_DOTS;
        if ($this->followLinks) {
            $flags |= \RecursiveDirectoryIterator::FOLLOW_SYMLINKS;
        }
        $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator($dir, $flags, $this->ignoreUnreadableDirs);
        if ($this->exclude) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\ExcludeDirectoryFilterIterator($iterator, $this->exclude);
        }
        $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
        if ($this->minDepth > 0 || $this->maxDepth < \PHP_INT_MAX) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\DepthRangeFilterIterator($iterator, $this->minDepth, $this->maxDepth);
        }
        if ($this->mode) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\FileTypeFilterIterator($iterator, $this->mode);
        }
        if ($this->names || $this->notNames) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\FilenameFilterIterator($iterator, $this->names, $this->notNames);
        }
        if ($this->contains || $this->notContains) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\FilecontentFilterIterator($iterator, $this->contains, $this->notContains);
        }
        if ($this->sizes) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\SizeRangeFilterIterator($iterator, $this->sizes);
        }
        if ($this->dates) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\DateRangeFilterIterator($iterator, $this->dates);
        }
        if ($this->filters) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\CustomFilterIterator($iterator, $this->filters);
        }
        if ($this->paths || $this->notPaths) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\PathFilterIterator($iterator, $this->paths, $this->notPaths);
        }
        if ($this->sort) {
            $iteratorAggregate = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\SortableIterator($iterator, $this->sort);
            $iterator = $iteratorAggregate->getIterator();
        }
        return $iterator;
    }
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'php';
    }
    /**
     * {@inheritdoc}
     */
    protected function canBeUsed()
    {
        return \true;
    }
}
