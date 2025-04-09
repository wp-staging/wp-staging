<?php
namespace WPStaging\Backup\Service\Database\Importer;
use WPStaging\Backup\Service\Database\DatabaseImporter;
use WPStaging\Framework\Traits\ApplyFiltersTrait;
class QueryCompatibility
{
    use ApplyFiltersTrait;

    public function removeDefiner(&$query)
    {
        if (!stripos($query, 'DEFINER')) {
            return;
        }
        $query = preg_replace('# DEFINER\s?=\s?(.+?(?= )) #i', ' ', $query);
    }

    public function removeSqlSecurity(&$query)
    {
        if (!stripos($query, 'SQL SECURITY')) {
            return;
        }
        $query = preg_replace('# SQL SECURITY \w+ #i', ' ', $query);
    }

    public function removeAlgorithm(&$query)
    {
        if (!stripos($query, 'ALGORITHM')) {
            return;
        }
        $query = preg_replace('# ALGORITHM\s?=\s?`?\w+`? #i', ' ', $query);
    }

    public function replaceTableEngineIfUnsupported(&$query)
    {
        $query = str_ireplace([
            'ENGINE=MyISAM',
            'ENGINE=Aria',
        ], [
            'ENGINE=InnoDB',
            'ENGINE=InnoDB',
        ], $query);
    }

    public function replaceTableRowFormat(&$query)
    {
        $query = str_ireplace([
            'ENGINE=InnoDB',
            'ENGINE=MyISAM',
        ], [
            'ENGINE=InnoDB ROW_FORMAT=DYNAMIC',
            'ENGINE=MyISAM ROW_FORMAT=DYNAMIC',
        ], $query);
    }

    public function removeFullTextIndexes(&$query)
    {
        $query = preg_replace('#,\s?FULLTEXT \w+\s?`?\w+`?\s?\([^)]+\)#i', '', $query);
    }

    public function convertUtf8Mb4toUtf8(&$query)
    {
        $query = str_ireplace('utf8mb4', 'utf8', $query);
    }

    public function shortenKeyIdentifiers(&$query)
    {
        $shortIdentifiers = [];
        $matches          = [];
        preg_match_all("#KEY `(.*?)`#", $query, $matches);
        foreach ($matches[1] as $identifier) {
            if (strlen($identifier) < 64) {
                continue;
            }
            $shortIdentifier                    = uniqid(DatabaseImporter::TMP_DATABASE_PREFIX) . str_pad(rand(0, 999999), 6, '0');
            $shortIdentifiers[$shortIdentifier] = $identifier;
        }
        $query = str_replace(array_values($shortIdentifiers), array_keys($shortIdentifiers), $query);
        return $shortIdentifiers;
    }

    public function pageCompressionMySQL(&$query, $errorMessage)
    {
        if (strpos($errorMessage, 'PAGE_COMPRESSED') === false) {
            return '';
        }
        $query = str_replace([
            "`PAGE_COMPRESSED`='ON'",
            "`PAGE_COMPRESSED`='OFF'",
            "`PAGE_COMPRESSED`='0'",
            "`PAGE_COMPRESSED`='1'",
        ], ['', '', '', ''], $query);
        preg_match('/create\s+table\s+\`?(\w+)`/i', $query, $matches);
        return $matches[1];
    }

    public function replaceCollation(&$query, string $errorMessage): array
    {
        preg_match('/create\s+table\s+\`?(\w+)`/i', $query, $matches);
        $tableName = $matches[1];
        preg_match('/Unknown collation: \'(.*?)\'/i', $errorMessage, $matches);
        $unknownCollation = $matches[1];
        $collationBefore = '';
        $collationAfter  = '';
        $collationReplaceRules = $this->applyFilters('wpstg.database.importer.replace_collation', []);
        if (array_key_exists($unknownCollation, $collationReplaceRules)) {
            $collationBefore = $unknownCollation;
            $collationAfter  = $collationReplaceRules[$unknownCollation];
        } else {
            $collationBefore = $unknownCollation;
            $collationAfter  = $this->findCollationGeneralVariant($unknownCollation);
        }
        $query = str_replace($collationBefore, $collationAfter, $query);
        return [
            'tableName'       => $tableName,
            'collationBefore' => $collationBefore,
            'collationAfter'  => $collationAfter,
        ];
    }

    private function findCollationGeneralVariant(string $collation): string
    {
        $collation         = strtolower($collation);
        $collationSegments = explode('_', $collation);
        return $collationSegments[0] . '_general_ci';
    }
}
