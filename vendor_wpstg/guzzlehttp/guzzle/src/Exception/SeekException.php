<?php

namespace WPStaging\Vendor\GuzzleHttp\Exception;

use WPStaging\Vendor\Psr\Http\Message\StreamInterface;
/**
 * Exception thrown when a seek fails on a stream.
 */
class SeekException extends \RuntimeException implements \WPStaging\Vendor\GuzzleHttp\Exception\GuzzleException
{
    private $stream;
    public function __construct(\WPStaging\Vendor\Psr\Http\Message\StreamInterface $stream, $pos = 0, $msg = '')
    {
        $this->stream = $stream;
        $msg = $msg ?: 'Could not seek the stream to position ' . $pos;
        parent::__construct($msg);
    }
    /**
     * @return StreamInterface
     */
    public function getStream()
    {
        return $this->stream;
    }
}
