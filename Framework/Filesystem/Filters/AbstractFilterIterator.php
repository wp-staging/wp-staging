<?php

namespace WPStaging\Framework\Filesystem\Filters;

use FilterIterator;
use WPStaging\Framework\Traits\SafeFileInfoTrait;





abstract class AbstractFilterIterator extends FilterIterator
{
    use SafeFileInfoTrait;
}
