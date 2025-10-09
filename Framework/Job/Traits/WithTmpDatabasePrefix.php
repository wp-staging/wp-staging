<?php

namespace WPStaging\Framework\Job\Traits;

use WPStaging\Backup\Service\Database\DatabaseImporter;

trait WithTmpDatabasePrefix
{
    protected function getTmpDatabasePrefix(): string
    {
        $tmpDatabasePrefix = apply_filters(DatabaseImporter::CUSTOM_TMP_PREFIX_FILTER, DatabaseImporter::TMP_DATABASE_PREFIX);
        if ($tmpDatabasePrefix === DatabaseImporter::TMP_DATABASE_PREFIX) {
            return DatabaseImporter::TMP_DATABASE_PREFIX;
        }

        if ($this->isTmpPrefixAvailable($tmpDatabasePrefix)) {
            return $tmpDatabasePrefix;
        }

        return DatabaseImporter::TMP_DATABASE_PREFIX;
    }

    protected function isTmpPrefixAvailable(string $tmpDatabasePrefix): bool
    {
        if (count($this->tableService->findTableNamesStartWith($tmpDatabasePrefix)) > 0) {
            return false;
        }

        return true;
    }
}
