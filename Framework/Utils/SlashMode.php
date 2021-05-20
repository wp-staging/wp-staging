<?php

// TODO PHP7.x; declare(strict_types=1);
namespace WPStaging\Framework\Utils;

// TODO PHP7.1; constant visibility
/**
 * This class is mainly used as a enum to provide SLASH MODE
 */
class SlashMode
{
    /**
     * Make path have no leading and trailing slashes
     * @var int
     */
    const NO_SLASH = 0;

    /**
     * Make path have only leading slash but no trailing slash
     * @var int
     */
    const LEADING_SLASH = -1;

    /**
     * Make path have only trailing slash but no leading slash
     * @var int
     */
    const TRAILING_SLASH = 1;

    /**
     * Make path have both slashes i.e. trailing slash and leading slash
     * @var int
     */
    const BOTH_SLASHES = 2;
}
