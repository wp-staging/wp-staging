<?php

namespace WPStaging\Framework\Filesystem\Filters;

use RecursiveFilterIterator;
use WPStaging\Framework\Traits\SafeFileInfoTrait;





abstract class AbstractRecursiveFilterIterator extends RecursiveFilterIterator
{
    use SafeFileInfoTrait;
}
