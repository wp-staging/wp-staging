<?php

namespace WPStaging\Framework\Network;

/**
 * Validates URLs against SSRF (Server-Side Request Forgery) attacks
 * by blocking requests to dangerous internal IP ranges.
 *
 * Only loopback, link-local (cloud metadata), and zero-network are blocked.
 * Private LAN ranges (10.x, 172.16.x, 192.168.x) are allowed for legitimate
 * use cases like local network backups and Docker cross-site communication.
 */
class SsrfProtection
{
    /**
     * IP ranges that are always blocked.
     * @var array<string, string>
     */
    const BLOCKED_RANGES = [
        '127.0.0.0/8'    => 'loopback',
        '169.254.0.0/16' => 'link-local',
        '0.0.0.0/8'      => 'current-network',
    ];

    /**
     * Check if a URL resolves to a blocked IP address.
     *
     * All resolved A records are validated, not just the first one.
     * This prevents bypasses where a hostname returns a mix of safe
     * and unsafe IPs.
     *
     * Note: There is an inherent TOCTOU (time-of-check-time-of-use) gap
     * between our DNS validation and the actual HTTP request made by
     * wp_remote_request(). WordPress's HTTP API does not expose
     * CURLOPT_RESOLVE, so we cannot pin the resolved IP to the connection.
     * This is a known limitation of plugin-level SSRF protection.
     *
     * @param string $url
     * @return bool True if the URL is blocked, false if safe.
     */
    public function isBlockedUrl($url)
    {
        if (defined('WPSTG_DISABLE_SSRF_PROTECTION') && WPSTG_DISABLE_SSRF_PROTECTION) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return true;
        }

        // If the host is already an IP, use it directly
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isBlockedIp($host);
        }

        return $this->isBlockedHostname($host);
    }

    /**
     * Resolve all A records for a hostname and block if any points to a dangerous range.
     *
     * Uses dns_get_record() to fetch all A records so that multi-record hostnames
     * cannot bypass validation. Falls back to gethostbyname() if dns_get_record()
     * is unavailable or returns no results.
     *
     * @param string $host
     * @return bool True if blocked, false if safe.
     */
    private function isBlockedHostname($host)
    {
        // Attempt to validate all A records
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

        // Fallback: dns_get_record() unavailable or returned no results
        $ip = gethostbyname($host);

        // gethostbyname returns the hostname on failure
        if ($ip === $host) {
            return true;
        }

        return $this->isBlockedIp($ip);
    }

    /**
     * Check if an IP address falls within any blocked range.
     *
     * Note: IPv6 addresses pass through unblocked because ip2long() only
     * handles IPv4. Since dns_get_record(DNS_A) and gethostbyname() never
     * return IPv6 addresses, the only way an IPv6 IP reaches this method
     * is when the user explicitly provides one in the URL. We allow it
     * rather than silently blocking legitimate IPv6-only servers.
     *
     * @param string $ip
     * @return bool
     */
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

    /**
     * Check if an IP (as long) falls within a CIDR range.
     *
     * @param int $ipLong
     * @param string $cidr e.g. '127.0.0.0/8'
     * @return bool
     */
    private function ipInCidr($ipLong, $cidr)
    {
        list($subnet, $bits) = explode('/', $cidr);
        $subnetLong          = ip2long($subnet);
        $mask                = -1 << (32 - (int)$bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
