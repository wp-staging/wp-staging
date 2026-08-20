<?php

namespace WPStaging\Framework\Traits;

trait IpResolverTrait
{





    public function getClientIP(): string
    {
        $ip = '';

 
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (empty($_SERVER[$header])) {
                continue;
            }

            $ips = explode(',', sanitize_text_field($_SERVER[$header]));
            $ips = array_map('trim', $ips);
            foreach ($ips as $potentialIp) {
 
                if (filter_var($potentialIp, FILTER_VALIDATE_IP) !== false) {
                    $ip = $potentialIp;
                    break 2;
                }
            }
        }

        return $ip;
    }
}
