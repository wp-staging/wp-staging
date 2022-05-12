<?php

/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace WPStaging\Vendor\AWS\CRT\HTTP;

use WPStaging\Vendor\AWS\CRT\IO\InputStream;
class Request extends \WPStaging\Vendor\AWS\CRT\HTTP\Message
{
    private $body_stream = null;
    public function __construct($method, $path, $query = [], $headers = [], $body_stream = null)
    {
        parent::__construct($method, $path, $query, $headers);
        if (!\is_null($body_stream) && !$body_stream instanceof \WPStaging\Vendor\AWS\CRT\IO\InputStream) {
            throw InvalidArgumentException('body_stream must be an instance of ' . \WPStaging\Vendor\AWS\CRT\IO\InputStream::class);
        }
        $this->body_stream = $body_stream;
    }
    public static function marshall($request)
    {
        return parent::marshall($request);
    }
    public static function unmarshall($buf)
    {
        return parent::_unmarshall($buf, \WPStaging\Vendor\AWS\CRT\HTTP\Request::class);
    }
    public function body_stream()
    {
        return $this->body_stream;
    }
}
