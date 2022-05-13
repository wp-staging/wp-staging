<?php

namespace WPStaging\Vendor\Aws\Handler\GuzzleV5;

use WPStaging\Vendor\GuzzleHttp\Stream\StreamDecoratorTrait;
use WPStaging\Vendor\GuzzleHttp\Stream\StreamInterface as GuzzleStreamInterface;
use WPStaging\Vendor\Psr\Http\Message\StreamInterface as Psr7StreamInterface;
/**
 * Adapts a Guzzle 5 Stream to a PSR-7 Stream.
 *
 * @codeCoverageIgnore
 */
class PsrStream implements \WPStaging\Vendor\Psr\Http\Message\StreamInterface
{
    use StreamDecoratorTrait;
    /** @var GuzzleStreamInterface */
    private $stream;
    public function __construct(\WPStaging\Vendor\GuzzleHttp\Stream\StreamInterface $stream)
    {
        $this->stream = $stream;
    }
    public function rewind()
    {
        $this->stream->seek(0);
    }
    public function getContents()
    {
        return $this->stream->getContents();
    }
}
