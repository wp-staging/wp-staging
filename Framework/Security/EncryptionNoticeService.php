<?php

namespace WPStaging\Framework\Security;







class EncryptionNoticeService
{
 
    private $dataEncryption;

    public function __construct(DataEncryption $dataEncryption)
    {
        $this->dataEncryption = $dataEncryption;
    }









    public function renderEncryptedNotice(string $optionName, $credentialKeys, string $label)
    {
        if ($this->hasStaleCredential($optionName, $credentialKeys)) {
            require WPSTG_VIEWS_DIR . '_main/partials/encrypted-notice.php';
        }
    }








    private function hasStaleCredential(string $optionName, $credentialKeys): bool
    {
        $option = get_option($optionName, []);
        if (empty($option) || !is_array($option)) {
            return false;
        }

        foreach ((array)$credentialKeys as $key) {
            if ($this->isStale($option[$key] ?? '')) {
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
