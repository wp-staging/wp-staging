<?php

namespace WPStaging\Backup\Service\Encryption;

use RuntimeException;
use WPStaging\Backup\Entity\FileBeingExtracted;
use WPStaging\Vendor\phpseclib3\Crypt\AES;
use WPStaging\Vendor\phpseclib3\Crypt\Hash;









class BackupEncryptionService
{
 
    const DEV_CONSTANT_BACKUP_ENCRYPTION = 'WPSTG_DEV_BACKUP_ENCRYPTION';

 
    const CIPHER_MODE = 'ctr';

 
    const KEY_LENGTH = 32;

 
    const IV_LENGTH = 16;

 
    const SALT_LENGTH = 16;

 
    const PBKDF2_ITERATIONS = 100000;

 
    const MASTER_KEY_LENGTH = 64;

 
    const HMAC_TAG_LENGTH = 32;

 
    const CHUNK_SIZE = 256 * 1024;

 
    const ENC_KEY_INFO = 'wpstg-enc-v1';

 
    const MAC_KEY_INFO = 'wpstg-mac-v1';

 
    const MIN_PASSWORD_LENGTH = 8;

 
    const VERIFICATION_PLAINTEXT = 'WPSTG_ENCRYPTION_VERIFY';






    public function isEncryptionAvailable()
    {
        return function_exists('openssl_encrypt') && class_exists(AES::class) && class_exists(Hash::class);
    }




    public function isBackupEncryptionEnabled()
    {
        return defined(self::DEV_CONSTANT_BACKUP_ENCRYPTION) && constant(self::DEV_CONSTANT_BACKUP_ENCRYPTION) === true;
    }








    public function deriveKey($password, $salt)
    {
        $keys = $this->deriveKeys($password, $salt);
        return $keys[0];
    }








    public function deriveKeys($password, $salt)
    {
        $masterKey = hash_pbkdf2('sha256', $password, $salt, self::PBKDF2_ITERATIONS, self::MASTER_KEY_LENGTH, true);

        return [
            $this->computeHmac(self::ENC_KEY_INFO, $masterKey),
            $this->computeHmac(self::MAC_KEY_INFO, $masterKey),
        ];
    }








    public function computeHmac($data, $macKey)
    {
        $hash = new Hash('sha256');
        $hash->setKey($macKey);

        return $hash->hash($data);
    }









    public function verifyHmac($data, $tag, $macKey)
    {
        return hash_equals($tag, $this->computeHmac($data, $macKey));
    }













    public function encryptChunk($data, $key, $fileIv, $chunkIndex)
    {
        $chunkIv = $this->deriveChunkIv($fileIv, $chunkIndex);
        $cipher = $this->createCipher($key, $chunkIv);
        return $cipher->encrypt($data);
    }










    public function decryptChunk($data, $key, $fileIv, $chunkIndex)
    {
        $chunkIv = $this->deriveChunkIv($fileIv, $chunkIndex);
        $cipher = $this->createCipher($key, $chunkIv);
        return $cipher->decrypt($data);
    }











    public function sealChunk($data, $key, $macKey, $fileIv, $chunkIndex)
    {
        $ciphertext = $this->encryptChunk($data, $key, $fileIv, $chunkIndex);

        return $ciphertext . $this->authenticateChunk($ciphertext, $macKey, $fileIv, $chunkIndex);
    }











    public function openChunk($sealedChunk, $key, $macKey, FileBeingExtracted $fileBeingExtracted)
    {
        $chunkIndex = intdiv($fileBeingExtracted->getWrittenBytes(), self::CHUNK_SIZE);
        $ciphertext = (string)substr($sealedChunk, 0, -self::HMAC_TAG_LENGTH);
        $tag        = (string)substr($sealedChunk, -self::HMAC_TAG_LENGTH);
        $fileIv     = $fileBeingExtracted->getEncryptionIv();
        if ($ciphertext === '' || !hash_equals($this->authenticateChunk($ciphertext, $macKey, $fileIv, $chunkIndex), $tag)) {
            throw new RuntimeException(sprintf('HMAC verification failed for encrypted file %s.', $fileBeingExtracted->getPath()));
        }

        return $this->decryptChunk($ciphertext, $key, $fileIv, $chunkIndex);
    }










    public function authenticateChunk($ciphertext, $macKey, $fileIv, $chunkIndex)
    {
        return $this->computeHmac($fileIv . pack('N', $chunkIndex) . $ciphertext, $macKey);
    }






    public function generateSalt()
    {
        return random_bytes(self::SALT_LENGTH);
    }






    public function generateIv()
    {
        do {
            $iv = random_bytes(self::IV_LENGTH);
        } while (strpbrk($iv, "\r\n") !== false);

        return $iv;
    }









    public function createVerificationToken($key, $iv)
    {
        $cipher = $this->createCipher($key, $iv);
        $encrypted = $cipher->encrypt(self::VERIFICATION_PLAINTEXT);
        return base64_encode($iv . $encrypted);
    }








    public function verifyKey($key, $token)
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false || strlen($decoded) <= self::IV_LENGTH) {
            return false;
        }

        $iv = substr($decoded, 0, self::IV_LENGTH);
        $encryptedData = substr($decoded, self::IV_LENGTH);

        $cipher = $this->createCipher($key, $iv);
        $decrypted = $cipher->decrypt($encryptedData);

        return $decrypted === self::VERIFICATION_PLAINTEXT;
    }






    public static function isEncryptedIntegrityException(\Exception $exception): bool
    {
        $message = $exception->getMessage();

        return strpos($message, 'HMAC verification failed') === 0
            || strpos($message, 'Cannot restore encrypted file') === 0
            || strpos($message, 'Invalid encryption IV') === 0;
    }









    private function deriveChunkIv($fileIv, $chunkIndex)
    {
        return substr($fileIv, 0, 8) . pack('N', $chunkIndex) . "\0\0\0\0";
    }








    private function createCipher($key, $iv)
    {
        $cipher = new AES(self::CIPHER_MODE);
        $cipher->setKeyLength(self::KEY_LENGTH * 8);
        $cipher->setKey($key);
        $cipher->setIV($iv);
        return $cipher;
    }
}
