<?php

namespace WPStaging\Framework\Filesystem\Filters;

use RecursiveFilterIterator;
use WPStaging\Framework\Traits\SafeFileInfoTrait;

/**
 * Base class for recursive filesystem filters, providing open_basedir-safe
 * SplFileInfo stat calls so subclasses don't each import SafeFileInfoTrait.
 */
abstract class AbstractRecursiveFilterIterator extends RecursiveFilterIterator
{
    use SafeFileInfoTrait;
}
