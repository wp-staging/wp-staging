<?php

namespace WPStaging\Framework\Network;









class SsrfProtection
{




    const BLOCKED_RANGES = [
        '127.0.0.0/8'    => 'loopback',
        '169.254.0.0/16' => 'link-local',
        '0.0.0.0/8'      => 'current-network',
    ];

















    public function isBlockedUrl($url)
    {
        if (defined('WPSTG_DISABLE_SSRF_PROTECTION') && WPSTG_DISABLE_SSRF_PROTECTION) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return true;
        }

 
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isBlockedIp($host);
        }

        return $this->isBlockedHostname($host);
    }











    private function isBlockedHostname($host)
    {
 
        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A);
            if (is_array($records) && !empty($records)) {
                foreach ($records as $record) {
                    if (!empty($record['ip']) && $this->isBlockedIp($record['ip'])) {
                        return true;
                    }
                }

                return false;
            }
        }

 
        $ip = gethostbyname($host);

 
        if ($ip === $host) {
            return true;
        }

        return $this->isBlockedIp($ip);
    }















    private function isBlockedIp($ip)
    {
        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return false;
        }

        foreach (self::BLOCKED_RANGES as $cidr => $label) {
            if ($this->ipInCidr($ipLong, $cidr)) {
                return true;
            }
        }

        return false;
    }








    private function ipInCidr($ipLong, $cidr)
    {
        list($subnet, $bits) = explode('/', $cidr);
        $subnetLong          = ip2long($subnet);
        $mask                = -1 << (32 - (int)$bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
