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

@\trigger_error('The ' . __NAMESPACE__ . '\\AbstractFindAdapter class is deprecated since Symfony 2.8 and will be removed in 3.0. Use directly the Finder class instead.', \E_USER_DEPRECATED);
use WPStaging\Vendor\Symfony\Component\Finder\Comparator\DateComparator;
use WPStaging\Vendor\Symfony\Component\Finder\Comparator\NumberComparator;
use WPStaging\Vendor\Symfony\Component\Finder\Exception\AccessDeniedException;
use WPStaging\Vendor\Symfony\Component\Finder\Expression\Expression;
use WPStaging\Vendor\Symfony\Component\Finder\Iterator;
use WPStaging\Vendor\Symfony\Component\Finder\Shell\Command;
use WPStaging\Vendor\Symfony\Component\Finder\Shell\Shell;
/**
 * Shell engine implementation using GNU find command.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 *
 * @deprecated since 2.8, to be removed in 3.0. Use Finder instead.
 */
abstract class AbstractFindAdapter extends \WPStaging\Vendor\Symfony\Component\Finder\Adapter\AbstractAdapter
{
    protected $shell;
    public function __construct()
    {
        $this->shell = new \WPStaging\Vendor\Symfony\Component\Finder\Shell\Shell();
    }
    /**
     * {@inheritdoc}
     */
    public function searchInDirectory($dir)
    {
        // having "/../" in path make find fail
        $dir = \realpath($dir);
        // searching directories containing or not containing strings leads to no result
        if (\WPStaging\Vendor\Symfony\Component\Finder\Iterator\FileTypeFilterIterator::ONLY_DIRECTORIES === $this->mode && ($this->contains || $this->notContains)) {
            return new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\FilePathsIterator(array(), $dir);
        }
        $command = \WPStaging\Vendor\Symfony\Component\Finder\Shell\Command::create();
        $find = $this->buildFindCommand($command, $dir);
        if ($this->followLinks) {
            $find->add('-follow');
        }
        $find->add('-mindepth')->add($this->minDepth + 1);
        if (\PHP_INT_MAX !== $this->maxDepth) {
            $find->add('-maxdepth')->add($this->maxDepth + 1);
        }
        if (\WPStaging\Vendor\Symfony\Component\Finder\Iterator\FileTypeFilterIterator::ONLY_DIRECTORIES === $this->mode) {
            $find->add('-type d');
        } elseif (\WPStaging\Vendor\Symfony\Component\Finder\Iterator\FileTypeFilterIterator::ONLY_FILES === $this->mode) {
            $find->add('-type f');
        }
        $this->buildNamesFiltering($find, $this->names);
        $this->buildNamesFiltering($find, $this->notNames, \true);
        $this->buildPathsFiltering($find, $dir, $this->paths);
        $this->buildPathsFiltering($find, $dir, $this->notPaths, \true);
        $this->buildSizesFiltering($find, $this->sizes);
        $this->buildDatesFiltering($find, $this->dates);
        $useGrep = $this->shell->testCommand('grep') && $this->shell->testCommand('xargs');
        $useSort = \is_int($this->sort) && $this->shell->testCommand('sort') && $this->shell->testCommand('cut');
        if ($useGrep && ($this->contains || $this->notContains)) {
            $grep = $command->ins('grep');
            $this->buildContentFiltering($grep, $this->contains);
            $this->buildContentFiltering($grep, $this->notContains, \true);
        }
        if ($useSort) {
            $this->buildSorting($command, $this->sort);
        }
        $command->setErrorHandler($this->ignoreUnreadableDirs ? function ($stderr) {
        } : function ($stderr) {
            throw new \WPStaging\Vendor\Symfony\Component\Finder\Exception\AccessDeniedException($stderr);
        });
        $paths = $this->shell->testCommand('uniq') ? $command->add('| uniq')->execute() : \array_unique($command->execute());
        $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\FilePathsIterator($paths, $dir);
        if ($this->exclude) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\ExcludeDirectoryFilterIterator($iterator, $this->exclude);
        }
        if (!$useGrep && ($this->contains || $this->notContains)) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\FilecontentFilterIterator($iterator, $this->contains, $this->notContains);
        }
        if ($this->filters) {
            $iterator = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\CustomFilterIterator($iterator, $this->filters);
        }
        if (!$useSort && $this->sort) {
            $iteratorAggregate = new \WPStaging\Vendor\Symfony\Component\Finder\Iterator\SortableIterator($iterator, $this->sort);
            $iterator = $iteratorAggregate->getIterator();
        }
        return $iterator;
    }
    /**
     * {@inheritdoc}
     */
    protected function canBeUsed()
    {
        return $this->shell->testCommand('find');
    }
    /**
     * @param Command $command
     * @param string  $dir
     *
     * @return Command
     */
    protected function buildFindCommand(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, $dir)
    {
        return $command->ins('find')->add('find ')->arg($dir)->add('-noleaf');
        // the -noleaf option is required for filesystems that don't follow the '.' and '..' conventions
    }
    /**
     * @param Command  $command
     * @param string[] $names
     * @param bool     $not
     */
    private function buildNamesFiltering(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, array $names, $not = \false)
    {
        if (0 === \count($names)) {
            return;
        }
        $command->add($not ? '-not' : null)->cmd('(');
        foreach ($names as $i => $name) {
            $expr = \WPStaging\Vendor\Symfony\Component\Finder\Expression\Expression::create($name);
            // Find does not support expandable globs ("*.{a,b}" syntax).
            if ($expr->isGlob() && $expr->getGlob()->isExpandable()) {
                $expr = \WPStaging\Vendor\Symfony\Component\Finder\Expression\Expression::create($expr->getGlob()->toRegex(\false));
            }
            // Fixes 'not search' and 'full path matching' regex problems.
            // - Jokers '.' are replaced by [^/].
            // - We add '[^/]*' before and after regex (if no ^|$ flags are present).
            if ($expr->isRegex()) {
                $regex = $expr->getRegex();
                $regex->prepend($regex->hasStartFlag() ? '/' : '/[^/]*')->setStartFlag(\false)->setStartJoker(\true)->replaceJokers('[^/]');
                if (!$regex->hasEndFlag() || $regex->hasEndJoker()) {
                    $regex->setEndJoker(\false)->append('[^/]*');
                }
            }
            $command->add($i > 0 ? '-or' : null)->add($expr->isRegex() ? $expr->isCaseSensitive() ? '-regex' : '-iregex' : ($expr->isCaseSensitive() ? '-name' : '-iname'))->arg($expr->renderPattern());
        }
        $command->cmd(')');
    }
    /**
     * @param Command  $command
     * @param string   $dir
     * @param string[] $paths
     * @param bool     $not
     */
    private function buildPathsFiltering(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, $dir, array $paths, $not = \false)
    {
        if (0 === \count($paths)) {
            return;
        }
        $command->add($not ? '-not' : null)->cmd('(');
        foreach ($paths as $i => $path) {
            $expr = \WPStaging\Vendor\Symfony\Component\Finder\Expression\Expression::create($path);
            // Find does not support expandable globs ("*.{a,b}" syntax).
            if ($expr->isGlob() && $expr->getGlob()->isExpandable()) {
                $expr = \WPStaging\Vendor\Symfony\Component\Finder\Expression\Expression::create($expr->getGlob()->toRegex(\false));
            }
            // Fixes 'not search' regex problems.
            if ($expr->isRegex()) {
                $regex = $expr->getRegex();
                $regex->prepend($regex->hasStartFlag() ? \preg_quote($dir) . \DIRECTORY_SEPARATOR : '.*')->setEndJoker(!$regex->hasEndFlag());
            } else {
                $expr->prepend('*')->append('*');
            }
            $command->add($i > 0 ? '-or' : null)->add($expr->isRegex() ? $expr->isCaseSensitive() ? '-regex' : '-iregex' : ($expr->isCaseSensitive() ? '-path' : '-ipath'))->arg($expr->renderPattern());
        }
        $command->cmd(')');
    }
    /**
     * @param Command            $command
     * @param NumberComparator[] $sizes
     */
    private function buildSizesFiltering(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, array $sizes)
    {
        foreach ($sizes as $i => $size) {
            $command->add($i > 0 ? '-and' : null);
            switch ($size->getOperator()) {
                case '<=':
                    $command->add('-size -' . ($size->getTarget() + 1) . 'c');
                    break;
                case '>=':
                    $command->add('-size +' . ($size->getTarget() - 1) . 'c');
                    break;
                case '>':
                    $command->add('-size +' . $size->getTarget() . 'c');
                    break;
                case '!=':
                    $command->add('-size -' . $size->getTarget() . 'c');
                    $command->add('-size +' . $size->getTarget() . 'c');
                    break;
                case '<':
                default:
                    $command->add('-size -' . $size->getTarget() . 'c');
            }
        }
    }
    /**
     * @param Command          $command
     * @param DateComparator[] $dates
     */
    private function buildDatesFiltering(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, array $dates)
    {
        foreach ($dates as $i => $date) {
            $command->add($i > 0 ? '-and' : null);
            $mins = (int) \round((\time() - $date->getTarget()) / 60);
            if (0 > $mins) {
                // mtime is in the future
                $command->add(' -mmin -0');
                // we will have no result so we don't need to continue
                return;
            }
            switch ($date->getOperator()) {
                case '<=':
                    $command->add('-mmin +' . ($mins - 1));
                    break;
                case '>=':
                    $command->add('-mmin -' . ($mins + 1));
                    break;
                case '>':
                    $command->add('-mmin -' . $mins);
                    break;
                case '!=':
                    $command->add('-mmin +' . $mins . ' -or -mmin -' . $mins);
                    break;
                case '<':
                default:
                    $command->add('-mmin +' . $mins);
            }
        }
    }
    /**
     * @param Command $command
     * @param string  $sort
     *
     * @throws \InvalidArgumentException
     */
    private function buildSorting(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, $sort)
    {
        $this->buildFormatSorting($command, $sort);
    }
    /**
     * @param Command $command
     * @param string  $sort
     */
    protected abstract function buildFormatSorting(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, $sort);
    /**
     * @param Command $command
     * @param array   $contains
     * @param bool    $not
     */
    protected abstract function buildContentFiltering(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, array $contains, $not = \false);
}
