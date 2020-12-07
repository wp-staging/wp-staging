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

@\trigger_error('The ' . __NAMESPACE__ . '\\GnuFindAdapter class is deprecated since Symfony 2.8 and will be removed in 3.0. Use directly the Finder class instead.', \E_USER_DEPRECATED);
use WPStaging\Vendor\Symfony\Component\Finder\Expression\Expression;
use WPStaging\Vendor\Symfony\Component\Finder\Iterator\SortableIterator;
use WPStaging\Vendor\Symfony\Component\Finder\Shell\Command;
use WPStaging\Vendor\Symfony\Component\Finder\Shell\Shell;
/**
 * Shell engine implementation using GNU find command.
 *
 * @author Jean-François Simon <contact@jfsimon.fr>
 *
 * @deprecated since 2.8, to be removed in 3.0. Use Finder instead.
 */
class GnuFindAdapter extends \WPStaging\Vendor\Symfony\Component\Finder\Adapter\AbstractFindAdapter
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'gnu_find';
    }
    /**
     * {@inheritdoc}
     */
    protected function buildFormatSorting(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, $sort)
    {
        switch ($sort) {
            case \WPStaging\Vendor\Symfony\Component\Finder\Iterator\SortableIterator::SORT_BY_NAME:
                $command->ins('sort')->add('| sort');
                return;
            case \WPStaging\Vendor\Symfony\Component\Finder\Iterator\SortableIterator::SORT_BY_TYPE:
                $format = '%y';
                break;
            case \WPStaging\Vendor\Symfony\Component\Finder\Iterator\SortableIterator::SORT_BY_ACCESSED_TIME:
                $format = '%A@';
                break;
            case \WPStaging\Vendor\Symfony\Component\Finder\Iterator\SortableIterator::SORT_BY_CHANGED_TIME:
                $format = '%C@';
                break;
            case \WPStaging\Vendor\Symfony\Component\Finder\Iterator\SortableIterator::SORT_BY_MODIFIED_TIME:
                $format = '%T@';
                break;
            default:
                throw new \InvalidArgumentException(\sprintf('Unknown sort options: %s.', $sort));
        }
        $command->get('find')->add('-printf')->arg($format . ' %h/%f\\n')->add('| sort | cut')->arg('-d ')->arg('-f2-');
    }
    /**
     * {@inheritdoc}
     */
    protected function canBeUsed()
    {
        return \WPStaging\Vendor\Symfony\Component\Finder\Shell\Shell::TYPE_UNIX === $this->shell->getType() && parent::canBeUsed();
    }
    /**
     * {@inheritdoc}
     */
    protected function buildFindCommand(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, $dir)
    {
        return parent::buildFindCommand($command, $dir)->add('-regextype posix-extended');
    }
    /**
     * {@inheritdoc}
     */
    protected function buildContentFiltering(\WPStaging\Vendor\Symfony\Component\Finder\Shell\Command $command, array $contains, $not = \false)
    {
        foreach ($contains as $contain) {
            $expr = \WPStaging\Vendor\Symfony\Component\Finder\Expression\Expression::create($contain);
            // todo: avoid forking process for each $pattern by using multiple -e options
            $command->add('| xargs -I{} -r grep -I')->add($expr->isCaseSensitive() ? null : '-i')->add($not ? '-L' : '-l')->add('-Ee')->arg($expr->renderPattern())->add('{}');
        }
    }
}
