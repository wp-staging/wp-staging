<?php

namespace WPStaging\Backup\Storage\Traits;

use WPStaging\Backup\Storage\Providers;





trait StorageIdNormalizerTrait
{






    public function normalizeStorageId(string $identifier): string
    {
        if (empty($identifier)) {
            return '';
        }

        return Providers::LEGACY_ID_MAP[$identifier] ?? $identifier;
    }







    public function getLegacyStorageId($identifier): string
    {
        if (empty($identifier)) {
            return '';
        }

        return Providers::REVERSE_LEGACY_ID_MAP[$identifier] ?? $identifier;
    }
}
