<?php

/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace WPStaging\Vendor\AWS\CRT\HTTP;

use WPStaging\Vendor\AWS\CRT\Internal\Encoding;
final class Headers
{
    private $headers;
    public function __construct($headers = [])
    {
        $this->headers = $headers;
    }
    public static function marshall($headers)
    {
        $buf = "";
        foreach ($headers->headers as $header => $value) {
            $buf .= \WPStaging\Vendor\AWS\CRT\Internal\Encoding::encodeString($header);
            $buf .= \WPStaging\Vendor\AWS\CRT\Internal\Encoding::encodeString($value);
        }
        return $buf;
    }
    public static function unmarshall($buf)
    {
        $strings = \WPStaging\Vendor\AWS\CRT\Internal\Encoding::readStrings($buf);
        $headers = [];
        for ($idx = 0; $idx < \count($strings);) {
            $headers[$strings[$idx++]] = $strings[$idx++];
        }
        return new \WPStaging\Vendor\AWS\CRT\HTTP\Headers($headers);
    }
    public function count()
    {
        return \count($this->headers);
    }
    public function get($header)
    {
        return isset($this->headers[$header]) ? $this->headers[$header] : null;
    }
    public function set($header, $value)
    {
        $this->headers[$header] = $value;
    }
    public function toArray()
    {
        return $this->headers;
    }
}
