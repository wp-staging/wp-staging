<?php

namespace WPStaging\Framework\Security;







class EncryptionNoticeService
{
 
    private $dataEncryption;

    public function __construct(DataEncryption $dataEncryption)
    {
        $this->dataEncryption = $dataEncryption;
    }









    public function renderEncryptedNotice($storedOptions, $credentialKeys, string $label)
    {
        if ($this->hasStaleCredential($storedOptions, $credentialKeys)) {
            require WPSTG_VIEWS_DIR . '_main/partials/encrypted-notice.php';
        }
    }








    private function hasStaleCredential($storedOptions, $credentialKeys): bool
    {
        if (empty($storedOptions) || !is_array($storedOptions)) {
            return false;
        }

        foreach ((array)$credentialKeys as $key) {
            if ($this->isStale($storedOptions[$key] ?? '')) {
                return true;
            }
        }

        return false;
    }







    private function isStale(string $value): bool
    {
 
        if (empty($value)) {
            return false;
        }

 
        if (!$this->dataEncryption->isEncrypted($value)) {
            return false;
        }

 
 
        return $this->dataEncryption->decrypt($value) === $value;
    }
}
