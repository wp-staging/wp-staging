<?php

namespace WPStaging\Backup\Service\Database\Importer;

use WPStaging\Backup\Ajax\Restore\PrepareRestore;

class QueryCompatibility
{
    /**
     * Sometimes CREATE queries for Tables and Views can contain a DEFINER, which is a security
     * policy in MySQL that provides advanced permission controls. We remove these, since they cause
     * issues if the Query runs in a server different than the one where it was backup.
     *
     * When we do this, we give regular execution permissions to the user running the query,
     * which is the default behavior.
     *
     * @param string $query DDL query.
     */
    public function removeDefiner(&$query)
    {
        // Early bail: DEFINER not set.
        if (!stripos($query, 'DEFINER')) {
            return;
        }

        $query = preg_replace('# DEFINER\s?=\s?(.+?(?= )) #i', ' ', $query);
    }

    /**
     * @param string $query DDL query.
     * @see removeDefiner
     *
     */
    public function removeSqlSecurity(&$query)
    {
        // Early bail: SQL SECURITY not set.
        if (!stripos($query, 'SQL SECURITY')) {
            return;
        }

        $query = preg_replace('# SQL SECURITY \w+ #i', ' ', $query);
    }

    /**
     * @param string $query DDL query.
     * @see removeDefiner
     *
     */
    public function removeAlgorithm(&$query)
    {
        // Early bail: ALGORITHM not set.
        if (!stripos($query, 'ALGORITHM')) {
            return;
        }

        $query = preg_replace('# ALGORITHM\s?=\s?`?\w+`? #i', ' ', $query);
    }

    /**
     * Some servers (like Microsoft Azure) do not support MyISAM.
     *
     * @link https://docs.microsoft.com/pt-br/azure/mariadb/concepts-limits#unsupported
     *
     * @param string $input DDL query with MyISAM
     */
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

    /**
     * Replace table row format
     *
     * @link https://dev.mysql.com/doc/refman/5.7/en/innodb-row-format.html
     * @link https://stackoverflow.com/questions/1814532/1071-specified-key-was-too-long-max-key-length-is-767-bytes
     *
     * @param string $query DDL query
     */
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

    /**
     * FULLTEXT indexes are supported on MySQL ^5 for MyISAM tables
     * Support for FULLTEXT was added to InnoDB on Mysql 5.6
     *
     * Plugins that improve WordPress search (like YARPP, Relevanssi, etc)
     * often add a FULLTEXT index to WordPress tables, like wp_posts.
     *
     * @link https://stackoverflow.com/a/963551/2056484
     * @link https://dev.mysql.com/doc/refman/5.6/en/create-index.html
     *
     * @param string $query DDL query
     */
    public function removeFullTextIndexes(&$query)
    {
        $query = preg_replace('#,\s?FULLTEXT \w+\s?`?\w+`?\s?\([^)]+\)#i', '', $query);
    }

    /**
     * Convert utf8mb4 to utf8
     *
     * On MySQL < 5.7 the max key length is 767 bytes for innoDB and 1000 bytes for MyISAM tables.
     * On MySQL > 5.7 the max key length has been increased to 3072 bytes.
     *
     * When restoring a backup created with MySQL >= 5.7, the key length on a server with MySQL < 5.7 may be too long and therefore fail.
     * A solution is to convert the failed key to from Utf8Mb4 to Utf8. Utf8Mb4 uses 4 bytes per character while Utf8 only consumes 3 bytes per character.
     * E.g. The maximum key length can be 192 characters on ut8mb4 (767/4 = 192) and 255 characters on utf8 (767/3 = 255)
     *
     * @link https://stackoverflow.com/questions/1814532/1071-specified-key-was-too-long-max-key-length-is-767-bytes
     *
     * @param string $query DDL query
     */
    public function convertUtf8Mb4toUtf8(&$query)
    {
        $query = str_ireplace('utf8mb4', 'utf8', $query);
    }

    /**
     * MySQL has a character limit of 64 characters for tables name
     * and key identifiers
     *
     * Tables name are handled separately, we shorten key identifiers here
     *
     * @param string $query DDL query
     * @return array
     */
    public function shortenKeyIdentifiers(&$query)
    {
        $shortIdentifiers = [];
        $matches          = [];
        preg_match_all("#KEY `(.*?)`#", $query, $matches);
        foreach ($matches[1] as $identifier) {
            if (strlen($identifier) < 64) {
                continue;
            }

            $shortIdentifier                    = uniqid(PrepareRestore::TMP_DATABASE_PREFIX) . str_pad(rand(0, 999999), 6, '0');
            $shortIdentifiers[$shortIdentifier] = $identifier;
        }

        $query = str_replace(array_values($shortIdentifiers), array_keys($shortIdentifiers), $query);

        return $shortIdentifiers;
    }

    /**
     * PAGE_COMPRESSED syntax is supported on MariaDB > 10.3 but not on MySQL
     * So when importing a MariaDB backup on MySQL, we need to remove PAGE_COMPRESSED from the query
     * Remove PAGE_COMPRESSED from query
     * @param string $query DDL query
     * @param string $errorMessage
     *
     * @return string
     */
    public function pageCompressionMySQL(&$query, $errorMessage)
    {
        // Early bail: syntax error is not about PAGE_COMPRESSED
        if (strpos($errorMessage, 'PAGE_COMPRESSED') === false) {
            return '';
        }

        $query = str_replace([
            "`PAGE_COMPRESSED`='ON'",
            "`PAGE_COMPRESSED`='OFF'",
            "`PAGE_COMPRESSED`='0'",
            "`PAGE_COMPRESSED`='1'",
        ], ['', '', '', ''], $query);

        // extract table name from query
        preg_match('/create\s+table\s+\`?(\w+)`/i', $query, $matches);

        // return table name
        return $matches[1];
    }
}
