<?php

namespace WPStaging\Framework\Security;

/**
 * Renders the stale-encryption admin notice.
 *
 * A credential is "stale" when it was encrypted with a key that is no longer
 * available: it looks encrypted but decryption silently returns it unchanged.
 */
class EncryptionNoticeService
{
    /** @var DataEncryption */
    private $dataEncryption;

    public function __construct(DataEncryption $dataEncryption)
    {
        $this->dataEncryption = $dataEncryption;
    }

    /**
     * Renders the notice if any credential field in the option is stale.
     *
     * @param string          $optionName     wp_options key to read
     * @param string|string[] $credentialKeys Field(s) inside the option to check
     * @param string          $label          Name displayed in the notice(e.g. "Amazon S3", used in the view; don't remove it)
     * @return void
     */
    public function renderEncryptedNotice(string $optionName, $credentialKeys, string $label)
    {
        if ($this->hasStaleCredential($optionName, $credentialKeys)) {
            require WPSTG_VIEWS_DIR . '_main/partials/encrypted-notice.php';
        }
    }

    /**
     * Returns true if any of the given credential fields in the option cannot be decrypted.
     *
     * @param string          $optionName
     * @param string|string[] $credentialKeys
     * @return bool
     */
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

    /**
     * Returns true if the value is encrypted but can no longer be decrypted.
     *
     * @param string $value
     * @return bool
     */
    private function isStale(string $value): bool
    {
        // Nothing to check
        if (empty($value)) {
            return false;
        }

        // Plain-text values are never stale
        if (!$this->dataEncryption->isEncrypted($value)) {
            return false;
        }

        // Both sslDecrypt and base64Decrypt return the input unchanged on failure,
        // so equality means the key is gone/changed and the credential can't be recovered
        return $this->dataEncryption->decrypt($value) === $value;
    }
}
