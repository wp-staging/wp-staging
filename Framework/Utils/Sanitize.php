<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Core\WPStaging;

/**
 * Sanitizes user input for safe use across the application
 *
 * Provides various sanitization methods for strings, integers, booleans,
 * emails, paths, arrays, and CLI-specific inputs like domains, table prefixes,
 * and license keys.
 */
class Sanitize
{
    protected $config = [];

    /**
     * Sanitize a string value.
     *
     * Applies URL decoding (optional), HTML special characters encoding, and trimming.
     * For arrays or objects, returns empty string to prevent security issues.
     *
     * Examples:
     *   sanitizeString('<script>')   // Returns: '&lt;script&gt;'
     *   sanitizeString(123)          // Returns: '123'
     *   sanitizeString(['text'])     // Returns: '' (empty string)
     *   sanitizeString($obj)         // Returns: '' (empty string)
     *
     * @param mixed $value The value to sanitize (should be string or scalar)
     * @param bool $shouldUrlDecode Whether to apply URL decoding first
     * @return string The sanitized string, or empty string for non-scalar values
     */
    public function sanitizeString($value, $shouldUrlDecode = true)
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        if ($shouldUrlDecode) {
            $value = wpstg_urldecode($value);
        }

        return trim(htmlspecialchars($value));
    }

    /**
     * @param string $password
     * @return string
     */
    public function sanitizePassword($password)
    {
        if (!is_string($password)) {
            return '';
        }

        return trim(stripslashes($password));
    }

    /**
     * Sanitize integer. Optionally use abs flag.
     *
     * @param int|string $value
     * @param bool $useAbsValue
     * @return int
     */
    public function sanitizeInt($value, $useAbsValue = false)
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($useAbsValue) {
            return absint($integer);
        }

        return $integer;
    }

    /**
     * @param int|bool|string $value
     * @return bool
     */
    public function sanitizeBool($value)
    {
        // FILTER_VALIDATE_BOOL is alias of FILTER_VALIDATE_BOOLEAN and was introduced in PHP 8.0 but php.net say that we use the BOOL variant,
        // See if we should use like this or just use the BOOLEAN variant?
        return filter_var($value, defined('FILTER_VALIDATE_BOOL') ? FILTER_VALIDATE_BOOL : FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param string $value
     * @return string
     */
    public function sanitizeEmail($value): string
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }

    /**
     * Sanitize the path, remove spaces and trailing slashes.
     *
     * @param string $value
     * @return string|false returns false if $value is an array or object
     */
    public function sanitizePath($value)
    {
        if (is_array($value) || is_object($value)) {
            return false;
        }

        $value = $this->sanitizeString($value);

        // Remove trailing slashes.
        $path = rtrim($value, '/\\');

        // To support network path on windows.
        if (WPStaging::isWindowsOs()) {
            return $path;
        }

        // Remove whitespace and spaces but not for macos
        if (!WPStaging::isMacOs()) {
            $path = preg_replace('/\s+/', '', $path);
        }

        // Convert all invalid slashes to one single forward slash
        $replacements = [
            '//' => '/',
        ];

        return strtr($path, $replacements);
    }

    /**
     * Html decode and then sanitize.
     *
     * @param string $text
     * @return string
     */
    public function htmlDecodeAndSanitize($text)
    {
        return sanitize_text_field(html_entity_decode($text));
    }

    /**
     * @param array $file
     * @param array
     */
    public function sanitizeFileUpload($file)
    {
        if (!is_array($file)) {
            return;
        }

        if (!isset($file['tmp_name'])) {
            return;
        }

        return $file;
    }

    /**
     * @param array|string $htmlPost
     * @return array
     */
    public function sanitizeExcludeRules($htmlPost)
    {
        if (is_object($htmlPost)) {
            return [];
        }

        $decoded = wpstg_urldecode($htmlPost);

        if (!is_array($decoded)) {
            $items = explode(',', $decoded);
        } else {
            $items = $decoded;
        }

        $sanitized = [];
        foreach ($items as $item) {
            $sanitized[] = $this->sanitizeString($item);
        }

        return $sanitized;
    }

    /**
     * @param array $items
     * @return array
     */
    public function sanitizeArrayInt($items)
    {
        // Early bail if not array
        if (!is_array($items) || empty($items)) {
            return [];
        }

        $sanitized = [];
        foreach ($items as $item) {
            $sanitized[] = $this->sanitizeInt($item);
        }

        return $sanitized;
    }

    /**
     * Sanitize an array of strings, with support for multidimensional arrays.
     *
     * Applies sanitizeString to each element of the array. For nested arrays,
     * recursively sanitizes while preserving the array structure.
     *
     * Examples:
     *   sanitizeArrayString(['text', '<script>'])  // Returns: ['text', '&lt;script&gt;']
     *   sanitizeArrayString([['a', 'b'], ['c']])   // Returns: [['a', 'b'], ['c']]
     *
     * @param array<mixed> $items
     * @return array<int|string, mixed>
     */
    public function sanitizeArrayString($items)
    {
        if (!is_array($items) || empty($items)) {
            return [];
        }

        $sanitized = [];
        foreach ($items as $key => $item) {
            if (is_array($item)) {
                $sanitized[$key] = $this->sanitizeArrayString($item);
            } else {
                $sanitized[$key] = $this->sanitizeString($item);
            }
        }

        return $sanitized;
    }

    /**
     * @param array $items
     * @param array $config An array that defines the expected type of a key,e.g. ['thisIsAbooleanValue' => true, 'thisShouldBeAnInteger' => int]
     * @return array
     */
    public function sanitizeArray($items, $config = [])
    {
        // Early bail if not array
        if (!is_array($items) || empty($items)) {
            return [];
        }

        $sanitized = [];
        if (!is_array($config) || empty($config)) {
            $config = $this->config;
        } else {
            $this->config = $config;
        }

        foreach ($items as $key => $value) {
            $sanitized[$key] = isset($config[$key]) ?
                $this->sanitizeCall($value, $config[$key]) :
                (is_array($value) ? $this->sanitizeArrayString($value) : $this->sanitizeString($value));
        }

        return $sanitized;
    }

    /**
     * @param string $text
     * @return string
     */
    public function decodeBase64AndSanitize($text)
    {
        return $this->sanitizeString(base64_decode($text));
    }

    /**
     * @param int|bool|string|array $value
     * @param string $method
     * @return int|bool|string|array
     */
    protected function sanitizeCall($value, $method)
    {
        $methodName = 'sanitize' . ucfirst($method);
        if (!method_exists($this, $methodName)) {
            return $this->sanitizeString($value);
        }

        return $this->{$methodName}($value);
    }

    /**
     * Wrapper for sanitize_url and esc_url_raw.
     *
     * @param string $url
     * @param string[] $protocols Optional. An array of acceptable protocols.
     * @return string
     */
    public function sanitizeUrl($url, $protocols = null)
    {
        return esc_url($url, $protocols, 'db');
    }

    /**
     * Sanitize a domain name for safe use in CLI commands.
     * Only allows alphanumeric characters, dots, and hyphens.
     *
     * @param string $domain
     * @return string
     */
    public function sanitizeDomainForCli($domain)
    {
        if (!is_string($domain)) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9.\-]/', '', $domain);
    }

    /**
     * Sanitize a database table prefix for safe use in CLI commands.
     * Only allows alphanumeric characters and underscores.
     *
     * @param string $prefix
     * @return string
     */
    public function sanitizeTablePrefixForCli($prefix)
    {
        if (!is_string($prefix)) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);
    }

    /**
     * Sanitize a license key for safe use in CLI commands.
     * Only allows alphanumeric characters and hyphens.
     *
     * @param string $licenseKey
     * @return string
     */
    public function sanitizeLicenseKeyForCli($licenseKey)
    {
        if (!is_string($licenseKey)) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9\-]/', '', $licenseKey);
    }
}
