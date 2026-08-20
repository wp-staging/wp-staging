<?php

namespace WPStaging\Framework\Utils;

use WPStaging\Core\WPStaging;








class Sanitize
{
    protected $config = [];
















    public function sanitizeString($value)
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return trim(htmlspecialchars($value));
    }





    public function sanitizePassword($password)
    {
        if (!is_string($password)) {
            return '';
        }

        return trim(stripslashes($password));
    }









    public function sanitizeCredentialContent($value)
    {
        if (!is_string($value) || $value === '') {
            return '';
        }

 
        $value = str_replace("\0", '', $value);

 
        $value = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

 
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($value);
    }








    public function sanitizeRemotePath($path)
    {
        if (!is_string($path)) {
            return '';
        }

 
        $path = str_replace('\\', '/', $path);

        $isAbsolute = strpos($path, '/') === 0;

        $parts    = explode('/', $path);
        $resolved = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                if (!empty($resolved)) {
                    array_pop($resolved);
                }

                continue;
            }

            $resolved[] = $part;
        }

        $sanitized = implode('/', $resolved);
        return $isAbsolute ? '/' . $sanitized : $sanitized;
    }








    public function sanitizeInt($value, $useAbsValue = false)
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($useAbsValue) {
            return absint($integer);
        }

        return $integer;
    }





    public function sanitizeBool($value)
    {
 
 
        return filter_var($value, defined('FILTER_VALIDATE_BOOL') ? FILTER_VALIDATE_BOOL : FILTER_VALIDATE_BOOLEAN);
    }





    public function sanitizeEmail($value): string
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }







    public function sanitizePath($value)
    {
        if (is_array($value) || is_object($value)) {
            return false;
        }

        $value = $this->sanitizeString($value);

 
        $path = rtrim($value, '/\\');

 
        if (WPStaging::isWindowsOs()) {
            return $path;
        }

 
        if (!WPStaging::isMacOs()) {
            $path = preg_replace('/\s+/', '', $path);
        }

 
        $replacements = [
            '//' => '/',
        ];

        return strtr($path, $replacements);
    }







    public function htmlDecodeAndSanitize($text)
    {
        return sanitize_text_field(html_entity_decode($text));
    }





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





    public function sanitizeArrayInt($items)
    {
 
        if (!is_array($items) || empty($items)) {
            return [];
        }

        $sanitized = [];
        foreach ($items as $item) {
            $sanitized[] = $this->sanitizeInt($item);
        }

        return $sanitized;
    }














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






    public function sanitizeArray($items, $config = [])
    {
 
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





    public function decodeBase64AndSanitize($text)
    {
        return $this->sanitizeString(base64_decode($text));
    }






    protected function sanitizeCall($value, $method)
    {
        $methodName = 'sanitize' . ucfirst($method);
        if (!method_exists($this, $methodName)) {
            return $this->sanitizeString($value);
        }

        return $this->{$methodName}($value);
    }








    public function sanitizeUrl($url, $protocols = null)
    {
        return esc_url($url, $protocols, 'db');
    }








    public function sanitizeDomainForCli($domain)
    {
        if (!is_string($domain)) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9.\-]/', '', $domain);
    }








    public function sanitizeTablePrefixForCli($prefix)
    {
        if (!is_string($prefix)) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9_]/', '', $prefix);
    }








    public function sanitizeLicenseKeyForCli($licenseKey)
    {
        if (!is_string($licenseKey)) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9\-]/', '', $licenseKey);
    }
}
