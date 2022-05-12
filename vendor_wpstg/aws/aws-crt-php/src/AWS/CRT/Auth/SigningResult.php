<?php

/**
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0.
 */
namespace WPStaging\Vendor\AWS\CRT\Auth;

use WPStaging\Vendor\AWS\CRT\NativeResource;
use WPStaging\Vendor\AWS\CRT\HTTP\Request;
class SigningResult extends \WPStaging\Vendor\AWS\CRT\NativeResource
{
    protected function __construct($native)
    {
        parent::__construct();
        $this->acquire($native);
    }
    function __destruct()
    {
        // No destruction necessary, SigningResults are transient, just release
        $this->release();
        parent::__destruct();
    }
    public static function fromNative($ptr)
    {
        return new \WPStaging\Vendor\AWS\CRT\Auth\SigningResult($ptr);
    }
    public function applyToHttpRequest(&$http_request)
    {
        self::$crt->signing_result_apply_to_http_request($this->native, $http_request->native);
        // Update http_request from native
        $http_request = \WPStaging\Vendor\AWS\CRT\HTTP\Request::unmarshall($http_request->toBlob());
    }
}
