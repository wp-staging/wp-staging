<?php

namespace WPStaging\Vendor\Aws\Handler\GuzzleV5;

use WPStaging\Vendor\GuzzleHttp\Stream\StreamDecoratorTrait;
use WPStaging\Vendor\GuzzleHttp\Stream\StreamInterface as GuzzleStreamInterface;
use WPStaging\Vendor\Psr\Http\Message\StreamInterface as Psr7StreamInterface;
/**
 * Adapts a PSR-7 Stream to a Guzzle 5 Stream.
 *
 * @codeCoverageIgnore
 */
class GuzzleStream implements \WPStaging\Vendor\GuzzleHttp\Stream\StreamInterface
{
    use StreamDecoratorTrait;
    /** @var Psr7StreamInterface */
    private $stream;
    public function __construct(\WPStaging\Vendor\Psr\Http\Message\StreamInterface $stream)
    {
        $this->stream = $stream;
    }
}
