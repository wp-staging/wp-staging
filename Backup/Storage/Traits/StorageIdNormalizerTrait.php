<?php

namespace WPStaging\Backup\Storage\Traits;

use WPStaging\Backup\Storage\Providers;

/**
 * Provides methods to normalize storage identifiers between legacy camelCase
 * and new hyphenated formats for backward compatibility.
 */
trait StorageIdNormalizerTrait
{
    /**
     * Normalize a storage identifier to the new hyphenated format.
     *
     * @param string $identifier Storage ID that may be in legacy format
     * @return string Normalized storage ID in hyphenated format
     */
    public function normalizeStorageId(string $identifier): string
    {
        if (empty($identifier)) {
            return '';
        }

        return Providers::LEGACY_ID_MAP[$identifier] ?? $identifier;
    }

    /**
     * Get the legacy storage identifier for backward compatibility with option names.
     *
     * @param string|null $identifier Normalized storage ID in hyphenated format
     * @return string Legacy storage ID, or the identifier itself if no legacy mapping exists
     */
    public function getLegacyStorageId($identifier): string
    {
        if (empty($identifier)) {
            return '';
        }

        return Providers::REVERSE_LEGACY_ID_MAP[$identifier] ?? $identifier;
    }
}
