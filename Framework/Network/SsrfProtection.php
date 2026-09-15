<?php

namespace WPStaging\Framework\Network;

use function WPStaging\functions\debug_log;







class SsrfProtection
{



    const BLOCKED_RANGES = [
        '127.0.0.0/8'    => 'loopback',
        '169.254.0.0/16' => 'link-local',
        '0.0.0.0/8'      => 'current-network',
    ];




    const BLOCKED_RANGES_IPV6 = [
        '::1/128'        => 'loopback',
        '::/128'         => 'unspecified',
        'fe80::/10'      => 'link-local',
        'fd00:ec2::/32'  => 'cloud-metadata-aws',
        'fd20:ce::/32'   => 'cloud-metadata-google',
        '64:ff9b:1::/48' => 'nat64-local-use',
        '2001::/32'      => 'teredo',
    ];




    const IPV4_EMBEDDING_PREFIXES = [
        '::/96'           => 12,
        '::ffff:0:0/96'   => 12,
        '::ffff:0:0:0/96' => 12,
        '64:ff9b::/96'    => 12,
        '2002::/16'       => 2,
    ];




    private $resolvedIpsByHost = [];





    public function isBlockedUrl($url)
    {
        if ($this->isProtectionDisabled()) {
            return false;
        }

        $host = $this->getUrlHost((string)$url);
        if ($host === '') {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isBlockedIp($host);
        }

        return $this->isBlockedHostname($host);
    }






    public function runRequestWithPinnedIp(string $url, callable $request)
    {
        if ($this->isProtectionDisabled()) {
            return $request();
        }

        $host = $this->getUrlHost($url);

        $pinValidatedIp = function ($handle, $requestArgs, $requestUrl) use ($host, $url) {
            if ($host === '' || $this->getUrlHost((string)$requestUrl) !== $host) {
                return;
            }

            $resolveEntry = $this->getCurlResolveEntry($url);
            if ($resolveEntry !== '') {
                curl_setopt($handle, CURLOPT_RESOLVE, [$resolveEntry]);
            }
        };

        add_action('http_api_curl', $pinValidatedIp, 10, 3);

        try {
            return $request();
        } finally {
            remove_action('http_api_curl', $pinValidatedIp, 10);
        }
    }





    protected function getCurlResolveEntry(string $url): string
    {
        $host = $this->getUrlHost($url);
        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP)) {
            return '';
        }

        foreach ($this->resolveHostToIps($host) as $ip) {
            if (!$this->isBlockedIp($ip)) {
                return $host . ':' . $this->getUrlPort($url) . ':' . $ip;
            }
        }

        debug_log(sprintf('SSRF protection: %s resolves to no address that may be requested, leaving the connection unpinned.', $host));

        return '';
    }





    private function getUrlHost(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return '';
        }

        if (strpos($host, '[') === 0 && substr($host, -1) === ']') {
            return substr($host, 1, -1);
        }

        return $host;
    }





    private function getUrlPort(string $url): int
    {
        $port = parse_url($url, PHP_URL_PORT);
        if (!empty($port)) {
            return (int)$port;
        }

        return strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https' ? 443 : 80;
    }





    private function isBlockedHostname(string $host): bool
    {
        $ips = $this->resolveHostToIps($host);
        if (empty($ips)) {
            return true;
        }

        foreach ($ips as $ip) {
            if ($this->isBlockedIp($ip)) {
                return true;
            }
        }

        return false;
    }





    private function resolveHostToIps(string $host): array
    {
        if (isset($this->resolvedIpsByHost[$host])) {
            return $this->resolvedIpsByHost[$host];
        }

        $ips = $this->lookupDnsRecords($host);
        if (empty($ips)) {
            $ips = $this->lookupHostAddress($host);
        }

        $this->resolvedIpsByHost[$host] = $ips;

        return $ips;
    }





    protected function lookupDnsRecords($host): array
    {
        if (!function_exists('dns_get_record')) {
            return [];
        }

        $ips = [];
        foreach ([DNS_A => 'ip', DNS_AAAA => 'ipv6'] as $recordType => $addressField) {
            foreach ($this->queryDnsRecords($host, $recordType) as $record) {
                if (!empty($record[$addressField])) {
                    $ips[] = $record[$addressField];
                }
            }
        }

        return $ips;
    }






    private function queryDnsRecords(string $host, int $recordType): array
    {
        set_error_handler(function () {
            return true;
        });

        try {
            $records = dns_get_record($host, $recordType);
        } finally {
            restore_error_handler();
        }

        return is_array($records) ? $records : [];
    }





    protected function lookupHostAddress($host): array
    {
        $ip = gethostbyname($host);

        return $ip === $host ? [] : [$ip];
    }





    private function isBlockedIp($ip): bool
    {
        $packedIp = inet_pton($ip);
        if ($packedIp === false) {
            return true;
        }

        if (strlen($packedIp) === 16) {
            if ($this->isIpInAnyRange($packedIp, self::BLOCKED_RANGES_IPV6)) {
                return true;
            }

            $packedIp = $this->unwrapEmbeddedIpv4($packedIp);
        }

        return strlen($packedIp) === 4 && $this->isIpInAnyRange($packedIp, self::BLOCKED_RANGES);
    }






    private function isIpInAnyRange(string $packedIp, array $ranges): bool
    {
        foreach (array_keys($ranges) as $cidr) {
            if ($this->isIpInCidr($packedIp, $cidr)) {
                return true;
            }
        }

        return false;
    }






    private function isIpInCidr(string $packedIp, string $cidr): bool
    {
        list($subnet, $prefixLength) = explode('/', $cidr);

        $packedSubnet = inet_pton($subnet);
        if ($packedSubnet === false || strlen($packedSubnet) !== strlen($packedIp)) {
            return false;
        }

        $prefixLength  = (int)$prefixLength;
        $wholeBytes    = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($wholeBytes > 0 && strncmp($packedIp, $packedSubnet, $wholeBytes) !== 0) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($packedIp[$wholeBytes]) & $mask) === (ord($packedSubnet[$wholeBytes]) & $mask);
    }





    private function unwrapEmbeddedIpv4(string $packedIp): string
    {
        foreach (self::IPV4_EMBEDDING_PREFIXES as $cidr => $addressOffset) {
            if ($this->isIpInCidr($packedIp, $cidr)) {
                return substr($packedIp, $addressOffset, 4);
            }
        }

        return $packedIp;
    }




    private function isProtectionDisabled(): bool
    {
        return defined('WPSTG_DISABLE_SSRF_PROTECTION') && WPSTG_DISABLE_SSRF_PROTECTION;
    }
}
