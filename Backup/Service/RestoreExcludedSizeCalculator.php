<?php

namespace WPStaging\Backup\Service;

use WPStaging\Framework\Filesystem\PartIdentifier;




class RestoreExcludedSizeCalculator
{




    public function calculate(array $indexPartSize, array $excludedBackupParts): int
    {
        $sizes = array_merge([
            PartIdentifier::DATABASE_PART_SIZE_IDENTIFIER   => 0,
            PartIdentifier::PLUGIN_PART_SIZE_IDENTIFIER     => 0,
            PartIdentifier::MU_PLUGIN_PART_SIZE_IDENTIFIER  => 0,
            PartIdentifier::THEME_PART_SIZE_IDENTIFIER      => 0,
            PartIdentifier::UPLOAD_PART_SIZE_IDENTIFIER     => 0,
            PartIdentifier::WP_CONTENT_PART_SIZE_IDENTIFIER => 0,
            PartIdentifier::LANGUAGE_PART_SIZE_IDENTIFIER   => 0,
            PartIdentifier::WP_ROOT_PART_SIZE_IDENTIFIER    => 0,
        ], $indexPartSize);

        $wpContentSize = max(0, (int)$sizes[PartIdentifier::WP_CONTENT_PART_SIZE_IDENTIFIER]);

        $excludedSize = 0;
        $excludedSize += $this->isExcluded(PartIdentifier::DATABASE_PART_IDENTIFIER, $excludedBackupParts) ? max(0, (int)$sizes[PartIdentifier::DATABASE_PART_SIZE_IDENTIFIER]) : 0;
        $excludedSize += $this->isExcluded(PartIdentifier::PLUGIN_PART_IDENTIFIER, $excludedBackupParts) ? max(0, (int)$sizes[PartIdentifier::PLUGIN_PART_SIZE_IDENTIFIER]) : 0;
        $excludedSize += $this->isExcluded(PartIdentifier::MU_PLUGIN_PART_IDENTIFIER, $excludedBackupParts) ? max(0, (int)$sizes[PartIdentifier::MU_PLUGIN_PART_SIZE_IDENTIFIER]) : 0;
        $excludedSize += $this->isExcluded(PartIdentifier::THEME_PART_IDENTIFIER, $excludedBackupParts) ? max(0, (int)$sizes[PartIdentifier::THEME_PART_SIZE_IDENTIFIER]) : 0;
        $excludedSize += $this->isExcluded(PartIdentifier::UPLOAD_PART_IDENTIFIER, $excludedBackupParts) ? max(0, (int)$sizes[PartIdentifier::UPLOAD_PART_SIZE_IDENTIFIER]) : 0;
        $excludedSize += $this->isExcluded(PartIdentifier::WP_CONTENT_PART_IDENTIFIER, $excludedBackupParts) ? $wpContentSize : 0;
        $excludedSize += $this->isExcluded(PartIdentifier::LANGUAGE_PART_IDENTIFIER, $excludedBackupParts) ? max(0, (int)$sizes[PartIdentifier::LANGUAGE_PART_SIZE_IDENTIFIER]) : 0;
        $excludedSize += $this->isExcluded(PartIdentifier::WP_ROOT_PART_IDENTIFIER, $excludedBackupParts) ? max(0, (int)$sizes[PartIdentifier::WP_ROOT_PART_SIZE_IDENTIFIER]) : 0;

        return $excludedSize;
    }




    private function isExcluded(string $partIdentifier, array $excludedBackupParts): bool
    {
        return in_array($partIdentifier, $excludedBackupParts, true);
    }
}
