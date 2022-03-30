<?php

namespace WPStaging\Vendor\GuzzleHttp\Psr7;

use WPStaging\Vendor\Psr\Http\Message\StreamInterface;
/**
 * Stream decorator that prevents a stream from being seeked.
 *
 * @final
 */
class NoSeekStream implements \WPStaging\Vendor\Psr\Http\Message\StreamInterface
{
    use StreamDecoratorTrait;
    public function seek($offset, $whence = \SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a NoSeekStream');
    }
    public function isSeekable()
    {
        return \false;
    }
}
