<?php

namespace WPStaging\Framework\Security;

/**
 * Class Data Encryption
 *
 * Class responsible for encrypting and decrypting data
 *
 * @package WPStaging\Framework\Security
 */
class DataEncryption
{
    /** @var bool */
    private $hasSsl;

    /** @var string */
    private $prefix;

    /** @var string */
    private $key;

    /** @var string */
    private $salt;

    public function __construct()
    {
        if (!apply_filters('wpstg.framework.security.dataEncryption.useSsl', true)) {
            $this->hasSsl = false;
        } else {
            $this->hasSsl = extension_loaded('openssl') && function_exists('openssl_encrypt') && function_exists('openssl_decrypt') && (bool)in_array('aes-256-ctr', openssl_get_cipher_methods());
        }

        $this->prefix = '!wpstg!';
        $this->key    = $this->getDefaultKey();
        $this->salt   = $this->getDefaultSalt();
    }

    /**
     * @param string|int $value
     * @return string
     */
    public function encrypt($value)
    {
        if ($this->hasSsl) {
            return $this->sslEncrypt($value);
        }

        return $this->base64Encrypt($value);
    }

    /**
     * @param string $value
     * @return string
     */
    public function decrypt($value)
    {
        if ($this->verifyPrefix($value, 'ssl')) {
            return $this->sslDecrypt($value);
        }

        return $this->base64Decrypt($value);
    }

    /**
     * @param string|int $value
     * @return string
     */
    protected function base64Encrypt($value)
    {
        if (!$this->isValidKeySalt() || $value === '' || $this->isEncrypted($value)) {
            return $value;
        }

        $mykey  = $this->key . $this->salt;
        $encpad = substr($mykey, 0, 12);
        $value  = $encpad . $value;

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $pad       = base64_decode($mykey);
        $valueLen  = strlen($value);
        $encrypted = '';
        $x         = 0;
        for ($i = 0; $i < $valueLen; $i++, $x++) {
            if (!isset($pad[$x])) {
                $x = 0;
            }

            $padi = $pad[$x];
            $encrypted .= chr(ord($value[$i]) ^ ord($padi));
        }

        return $this->setPrefixType('b64') . $this->normalizeBase64Encode($encrypted);
    }

    /**
     * @param string $inputValue
     * @return string
     */
    protected function base64Decrypt($inputValue)
    {
        if (!$this->isValidKeySalt() || !is_string($inputValue) || $inputValue === '' || !$this->isEncrypted($inputValue) || !$this->verifyPrefix($inputValue, 'b64')) {
            return $inputValue;
        }

        $value = $this->stripPrefix($inputValue, 'b64');
        $mykey = $this->key . $this->salt;

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        $pad          = base64_decode($mykey);
        $encrypted    = $this->normalizeBase64Decode($value);
        $encryptedLen = strlen($encrypted);
        $decrypted    = '';
        $x            = 0;
        for ($i = 0; $i < $encryptedLen; $i++, $x++) {
            if (!isset($pad[$x])) {
                $x = 0;
            }

            $padi = $pad[$x];
            $decrypted .= chr(ord($encrypted[$i]) ^ ord($padi));
        }

        $encpad = substr($mykey, 0, 12);
        $enclen = strlen($encpad);
        $envpad = substr($decrypted, 0, $enclen);

        if ($encpad !== $envpad) {
            return $inputValue;
        }

        $decrypted = substr($decrypted, $enclen);
        return $decrypted;
    }

    /**
     * @param string|int $value
     * @return string
     */
    protected function sslEncrypt($value)
    {
        if (!$this->isValidKeySalt() || $value === '' || !$this->hasSsl || $this->isEncrypted($value)) {
            return $value;
        }

        $method = 'aes-256-ctr';
        $ivlen  = openssl_cipher_iv_length($method);
        $iv     = openssl_random_pseudo_bytes($ivlen);

        $rawValue = openssl_encrypt($value . $this->salt, $method, $this->key, 0, $iv);
        if (!$rawValue) {
            return $value;
        }

        return $this->setPrefixType('ssl') . $this->normalizeBase64Encode($iv . $rawValue);
    }

    /**
     * @param string $inputValue
     * @return string
     */
    protected function sslDecrypt($inputValue)
    {
        if (!$this->isValidKeySalt() || !is_string($inputValue) || $inputValue === '' || !$this->hasSsl || !$this->isEncrypted($inputValue) || !$this->verifyPrefix($inputValue, 'ssl')) {
            return $inputValue;
        }

        $rawValue = $this->stripPrefix($inputValue, 'ssl');
        $rawValue = $this->normalizeBase64Decode($rawValue);

        $method = 'aes-256-ctr';
        $ivlen  = openssl_cipher_iv_length($method);
        $iv     = substr($rawValue, 0, $ivlen);

        $rawValue = substr($rawValue, $ivlen);

        $value = openssl_decrypt($rawValue, $method, $this->key, 0, $iv);
        if (! $value || $this->salt !== substr($value, - strlen($this->salt))) {
            return $inputValue;
        }

        return substr($value, 0, - strlen($this->salt));
    }

    /** @return true */
    protected function disableUseSssl()
    {
        $this->hasSsl = false;
        return true;
    }

    /**
     * @param string $value
     * @return string
     */
    public function setKey($value)
    {
        $this->key = (string)$value;
        return $this->key;
    }

    /**
     * @param string $value
     * @return string
     */
    public function setSalt($value)
    {
        $this->salt = (string)$value;
        return $this->salt;
    }

    /** @return string */
    public function getKey()
    {
        return $this->key;
    }

    /** @return string */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $value
     * @param string $type
     * @return bool
     */
    protected function verifyPrefix($value, $type)
    {
        $type = '!' . $type . '!';
        return substr($value, 0, strlen($this->prefix . $type)) === $this->prefix . $type;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function isEncrypted($value)
    {
        return preg_match('@^' . preg_quote($this->prefix, '@') . '!(b64|ssl)!([a-zA-Z0-9\-_]+)$@', $value);
    }

    /** @return bool */
    protected function isValidKeySalt()
    {
        $key  = $this->getKey();
        $salt = $this->getSalt();
        return (!empty($key) && is_string($key)) && (!empty($salt) && is_string($salt));
    }

    /** @return string */
    protected function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function setPrefix($value)
    {
        $this->prefix = $value;
        return $this->prefix;
    }

    /** @return string|false */
    private function getDefaultKey()
    {
        if (defined('WPSTG_ENCRYPTION_KEY') && !empty(WPSTG_ENCRYPTION_KEY) && is_string(WPSTG_ENCRYPTION_KEY)) {
            return WPSTG_ENCRYPTION_KEY;
        }

        return false;
    }

    /** @return string|false */
    private function getDefaultSalt()
    {
        if (defined('WPSTG_ENCRYPTION_SALT') && !empty(WPSTG_ENCRYPTION_SALT) && is_string(WPSTG_ENCRYPTION_SALT)) {
            return WPSTG_ENCRYPTION_SALT;
        }

        return false;
    }

    /**
     * @param string $value
     * @param string $type
     * @return string
     */
    private function stripPrefix($value, $type)
    {
        $type = '!' . $type . '!';
        return substr($value, strlen($this->prefix . $type));
    }

    /**
     * @param string $type
     * @return string
     */
    private function setPrefixType($type)
    {
        return $this->prefix . '!' . $type . '!';
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalizeBase64Encode($value)
    {
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($value));
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalizeBase64Decode($value)
    {
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $value));
    }
}
