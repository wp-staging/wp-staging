<?php

namespace WPStaging\Framework\Security;

use WPStaging\Framework\Facades\Hooks;
use WPStaging\Vendor\phpseclib3\Crypt\RSA;
use WPStaging\Vendor\phpseclib3\Crypt\PublicKeyLoader;

use function WPStaging\functions\debug_log;








class DataEncryption
{
 
    const FILTER_FRAMEWORK_SECURITY_DATA_ENCRYPTION_USE_SSL = 'wpstg.framework.security.dataEncryption.useSsl';

 
    private $hasSsl;

 
    private $prefix;

 
    private $key;

 
    private $salt;

    public function __construct()
    {
        if (!Hooks::applyFilters(self::FILTER_FRAMEWORK_SECURITY_DATA_ENCRYPTION_USE_SSL, true)) {
            $this->hasSsl = false;
        } else {
            $this->hasSsl = extension_loaded('openssl') && function_exists('openssl_encrypt') && function_exists('openssl_decrypt') && (bool)in_array('aes-256-ctr', openssl_get_cipher_methods());
        }

        $this->prefix = '!wpstg!';
        $this->key    = $this->getDefaultKey();
        $this->salt   = $this->getDefaultSalt();
    }

    public function isPhpSecLibAvailable(): bool
    {
        return class_exists(\WPStaging\Vendor\phpseclib3\Crypt\PublicKeyLoader::class);
    }





    public function encrypt($value): string
    {
        if ($this->hasSsl) {
            return $this->sslEncrypt($value);
        }

        return $this->base64Encrypt($value);
    }





    public function decrypt(string $value): string
    {
        if ($this->verifyPrefix($value, 'ssl')) {
            return $this->sslDecrypt($value);
        }

        return $this->base64Decrypt($value);
    }





    protected function base64Encrypt($value): string
    {
        if (!$this->isValidKeySalt() || $value === '' || $this->isEncrypted($value)) {
            return (string)$value;
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





    protected function base64Decrypt(string $inputValue): string
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





    protected function sslEncrypt($value): string
    {
        if (!$this->isValidKeySalt() || $value === '' || !$this->hasSsl || $this->isEncrypted($value)) {
            return (string)$value;
        }

        $method = 'aes-256-ctr';
        $ivlen  = openssl_cipher_iv_length($method);
        $iv     = openssl_random_pseudo_bytes($ivlen);

        $rawValue = openssl_encrypt($value . $this->salt, $method, $this->key, 0, $iv);
        if (!$rawValue) {
            return (string)$value;
        }

        return $this->setPrefixType('ssl') . $this->normalizeBase64Encode($iv . $rawValue);
    }





    protected function sslDecrypt(string $inputValue): string
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
        if (!$value || $this->salt !== substr($value, - strlen($this->salt))) {
            return $inputValue;
        }

        return substr($value, 0, - strlen($this->salt));
    }

 
    protected function disableUseSssl()
    {
        $this->hasSsl = false;
        return true;
    }





    public function setKey(string $value): string
    {
        $this->key = (string)$value;
        return $this->key;
    }





    public function setSalt(string $value): string
    {
        $this->salt = (string)$value;
        return $this->salt;
    }

 
    public function getKey(): string
    {
        return $this->key;
    }

 
    public function getSalt(): string
    {
        return $this->salt;
    }






    protected function verifyPrefix(string $value, string $type): bool
    {
        $type = '!' . $type . '!';
        return substr($value, 0, strlen($this->prefix . $type)) === $this->prefix . $type;
    }





    public function isEncrypted(string $value): bool
    {
        return preg_match('@^' . preg_quote($this->prefix, '@') . '!(b64|ssl|rsa)!([a-zA-Z0-9\-_]+)$@', $value);
    }

 
    protected function isValidKeySalt(): bool
    {
        $key  = $this->getKey();
        $salt = $this->getSalt();
        return (!empty($key) && is_string($key)) && (!empty($salt) && is_string($salt));
    }

 
    protected function getPrefix(): string
    {
        return $this->prefix;
    }





    protected function setPrefix(string $value): string
    {
        $this->prefix = $value;
        return $this->prefix;
    }

 
    private function getDefaultKey()
    {
        if (defined('WPSTG_ENCRYPTION_KEY') && !empty(WPSTG_ENCRYPTION_KEY) && is_string(WPSTG_ENCRYPTION_KEY)) {
            return WPSTG_ENCRYPTION_KEY;
        }

        if (defined('AUTH_KEY') && !empty(AUTH_KEY) && is_string(AUTH_KEY)) {
            return AUTH_KEY;
        }

        return false;
    }

 
    private function getDefaultSalt()
    {
        if (defined('WPSTG_ENCRYPTION_SALT') && !empty(WPSTG_ENCRYPTION_SALT) && is_string(WPSTG_ENCRYPTION_SALT)) {
            return WPSTG_ENCRYPTION_SALT;
        }

        if (defined('AUTH_SALT') && !empty(AUTH_SALT) && is_string(AUTH_SALT)) {
            return AUTH_SALT;
        }

        return false;
    }






    private function stripPrefix(string $value, string $type): string
    {
        $type = '!' . $type . '!';
        return substr($value, strlen($this->prefix . $type));
    }





    private function setPrefixType(string $type): string
    {
        return $this->prefix . '!' . $type . '!';
    }





    private function normalizeBase64Encode(string $value): string
    {
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($value));
    }





    private function normalizeBase64Decode(string $value)
    {
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $value));
    }

    public function getPublicKey(): string
    {
        $keyPath = WPSTG_RESOURCES_DIR . '/wpstg-public-key.pem';

        if (!file_exists($keyPath)) {
            return '';
        }

        $keyData = file_get_contents($keyPath);
        return $keyData;
    }

    public function generateKeys(): array
    {
        $privateKey = RSA::createKey();
        $publicKey  = $privateKey->getPublicKey();

        return [
            'publicKey'  => $publicKey,
            'privateKey' => $privateKey,
        ];
    }






    public function rsaEncrypt($value, string $publicKey): string
    {
        if ($value === '' || $this->isEncrypted($value)) {
            return (string)$value;
        }

        $keyHandle = PublicKeyLoader::load($publicKey);
        if (!is_object($keyHandle) || !strpos(get_class($keyHandle), 'RSA\PublicKey') || !method_exists($keyHandle, 'encrypt')) {
            debug_log(sprintf('[DataEncryption] Failed to load public key: %s', get_class($keyHandle)), 'info', false);
            return (string)$value;
        }

        $cipherText = $keyHandle->encrypt($value);
        return $this->setPrefixType('rsa') . $this->normalizeBase64Encode($cipherText);
    }






    public function rsaDecrypt(string $inputValue, string $privateKey): string
    {
        if (!is_string($inputValue) || $inputValue === '' || !$this->isEncrypted($inputValue) || !$this->verifyPrefix($inputValue, 'rsa')) {
            return $inputValue;
        }

        $keyHandle = PublicKeyLoader::load($privateKey);
        if (!is_object($keyHandle) || !strpos(get_class($keyHandle), 'RSA\PrivateKey') || !method_exists($keyHandle, 'decrypt')) {
            debug_log(sprintf('[DataEncryption] Failed to load private key: %s', get_class($keyHandle)), 'info', false);
            return $inputValue;
        }

        $value = $this->stripPrefix($inputValue, 'rsa');
        $value = $this->normalizeBase64Decode($value);

        return $keyHandle->decrypt($value);
    }
}
