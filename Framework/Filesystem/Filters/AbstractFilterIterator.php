<?php

namespace WPStaging\Framework\Filesystem\Filters;

use FilterIterator;
use WPStaging\Framework\Traits\SafeFileInfoTrait;

/**
 * Base class for non-recursive filesystem filters, providing open_basedir-safe
 * SplFileInfo stat calls so subclasses don't each import SafeFileInfoTrait.
 */
abstract class AbstractFilterIterator extends FilterIterator
{
    use SafeFileInfoTrait;
}
