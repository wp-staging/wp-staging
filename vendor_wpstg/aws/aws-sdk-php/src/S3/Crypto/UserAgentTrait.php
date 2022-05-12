<?php

namespace WPStaging\Vendor\Aws\S3\Crypto;

use WPStaging\Vendor\Aws\AwsClientInterface;
use WPStaging\Vendor\Aws\Middleware;
use WPStaging\Vendor\Psr\Http\Message\RequestInterface;
trait UserAgentTrait
{
    private function appendUserAgent(\WPStaging\Vendor\Aws\AwsClientInterface $client, $agentString)
    {
        $list = $client->getHandlerList();
        $list->appendBuild(\WPStaging\Vendor\Aws\Middleware::mapRequest(function (\WPStaging\Vendor\Psr\Http\Message\RequestInterface $req) use($agentString) {
            if (!empty($req->getHeader('User-Agent')) && !empty($req->getHeader('User-Agent')[0])) {
                $userAgent = $req->getHeader('User-Agent')[0];
                if (\strpos($userAgent, $agentString) === \false) {
                    $userAgent .= " {$agentString}";
                }
            } else {
                $userAgent = $agentString;
            }
            $req = $req->withHeader('User-Agent', $userAgent);
            return $req;
        }));
    }
}
