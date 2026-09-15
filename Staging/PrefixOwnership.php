<?php

namespace WPStaging\Staging;

use RuntimeException;
use wpdb;
use WPStaging\Framework\Database\DbInfo;
use WPStaging\Framework\Database\WpDbInfo;
use WPStaging\Staging\Dto\StagingSiteDto;




class PrefixOwnership
{
 
    private $sites;

    public function __construct(Sites $sites)
    {
        $this->sites = $sites;
    }










    public function canDeleteTables(string $prefix, string $cloneId, wpdb $database): bool
    {
        $records = $this->sites->tryGettingStagingSites();
        if (isset($records[$cloneId]['ownsDatabaseTables']) && $records[$cloneId]['ownsDatabaseTables'] === false) {
            return false;
        }

        if (!$this->isProductionDatabase($database)) {
            return true;
        }

        if (isset($records[$cloneId])) {
            $site = $this->sites->getStagingSiteDtoByCloneId($cloneId);
            if ($site->getUsedPrefix() !== '' && strcasecmp($prefix, $site->getUsedPrefix()) !== 0) {
                return false;
            }
        }

        global $wpdb;
        $productionPrefix = strtolower($wpdb->base_prefix ?: $wpdb->prefix);
        if ($prefix === '' || strpos(strtolower($prefix), $productionPrefix) === 0 || strpos($productionPrefix, strtolower($prefix)) === 0) {
            return false;
        }

        $candidates = $this->getCandidates($prefix, $records);
        if (count($candidates) < 2) {
            return empty($candidates) || $candidates[0] === $cloneId;
        }

        $tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($prefix) . '%'));
        if ($wpdb->last_error !== '') {
            throw $this->ambiguousOwnership();
        }

        if (empty($tables)) {
            return true;
        }

        $owner = $this->findPrefixOwner($prefix, $candidates);
        if ($owner === null) {
            throw $this->ambiguousOwnership();
        }

        $this->restrictNonOwners($candidates, $owner, $records);
        return $cloneId === $owner;
    }










    public function assertCanModifyTables(string $prefix, string $cloneId, $jobSite = null)
    {
        global $wpdb;
        $site = $this->sites->getStagingSiteDtoByCloneId($cloneId);
        try {
            if (
                ($site->getUsedPrefix() !== '' && strcasecmp($prefix, $site->getUsedPrefix()) !== 0)
                || ($jobSite !== null && $this->databaseSettings($site) !== $this->databaseSettings($jobSite))
            ) {
                throw $this->ambiguousOwnership();
            }

            if ($site->getOwnsDatabaseTables() && !$this->recordUsesProduction($site)) {
                return;
            }

            $canModify = $this->canDeleteTables($prefix, $cloneId, $wpdb);
            $candidates = $this->getCandidates($prefix, $this->sites->tryGettingStagingSites());
            if ($canModify && (count($candidates) < 2 || $this->findPrefixOwner($prefix, $candidates) === $cloneId)) {
                return;
            }
        } catch (RuntimeException $e) {
 
        }

        throw new RuntimeException(__('Cannot reset or update this staging site because it does not have verified ownership of its database tables. Delete the broken entry and create a new staging site, or contact support@wp-staging.com.', 'wp-staging'));
    }









    public function findPrefixOwner(string $prefix, array $candidateCloneIds)
    {
        global $wpdb;
        $records = $this->sites->tryGettingStagingSites();
        $owners = [];
        foreach ($candidateCloneIds as $id) {
            if (($records[$id]['ownsDatabaseTables'] ?? null) === false) {
                continue;
            }

            $site = $this->sites->getStagingSiteDtoByCloneId($id);
            $path = $site->getPath();
            if ($path === '' && $site->getDirectoryName() !== '') {
                $path = trailingslashit(ABSPATH) . $site->getDirectoryName();
            }

            $declared = $path === '' ? null : $this->readConfigPrefix(trailingslashit($path) . 'wp-config.php', $site->getDatabaseName(), $site->getIsCustomDatabaseConnection() ? $site->getDatabaseServer() : DB_HOST);
            if ($declared !== null && strcasecmp($prefix, $declared) === 0 && strcasecmp($declared, $wpdb->base_prefix) !== 0) {
                $owners[] = $id;
            }
        }

        if (count($owners) > 1) {
            return null;
        }

        $configOwner = $owners[0] ?? null;
        $owners = [];
        $options = $prefix . 'options';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($options)));
        if ($this->queryFailed($wpdb)) {
            throw $this->ambiguousOwnership();
        }

        if ($exists === null) {
            return $configOwner;
        }

        $url = $wpdb->get_var("SELECT option_value FROM `" . str_replace('`', '``', $options) . "` WHERE option_name = 'siteurl'");
        if ($this->queryFailed($wpdb)) {
            throw $this->ambiguousOwnership();
        }

        if (!is_string($url) || $url === '') {
            return $configOwner;
        }

        foreach ($candidateCloneIds as $id) {
            if (($records[$id]['ownsDatabaseTables'] ?? null) !== false && untrailingslashit((string)($records[$id]['url'] ?? '')) === untrailingslashit($url)) {
                $owners[] = $id;
            }
        }

        if (count($owners) > 1 || (count($owners) === 1 && $configOwner !== null && $owners[0] !== $configOwner)) {
            return null;
        }

        return $configOwner ?? ($owners[0] ?? null);
    }

 
    private function databaseSettings(StagingSiteDto $site): array
    {
        if (!$site->getIsCustomDatabaseConnection()) {
            return [false];
        }

        return [true, $site->getDatabaseName(), $site->getDatabaseServer(), $site->getDatabaseUser(), $site->getDatabasePassword(), $site->getDatabaseSsl()];
    }

 
    private function queryFailed(wpdb $database): bool
    {
        return $database->last_error !== '';
    }







    public function repairCollisions()
    {
        global $wpdb;
        $groups = [];
        foreach ($this->sites->tryGettingStagingSites() as $id => $record) {
            $dto = new StagingSiteDto();
            $dto->hydrate((array)$record);
            if ($dto->getUsedPrefix() !== '') {
                $groups[strtolower($dto->getUsedPrefix())][] = (string)$id;
            }
        }

        foreach ($groups as $prefix => $ids) {
            if (count($ids) < 2) {
                continue;
            }

            try {
                foreach ($ids as $id) {
                    $site = $this->sites->getStagingSiteDtoByCloneId($id);
                    if ($site->getOwnsDatabaseTables() && $this->recordUsesProduction($site)) {
                        $this->canDeleteTables($site->getUsedPrefix(), $id, $wpdb);
                        break;
                    }
                }
            } catch (RuntimeException $e) {
 
            }
        }
    }







    public function isProductionDatabase(wpdb $database): bool
    {
        global $wpdb;
        if ($database === $wpdb) {
            return true;
        }

        if ($database->dbname !== $wpdb->dbname && ($database->get_var('SELECT @@lower_case_table_names') === '0' || strcasecmp($database->dbname, $wpdb->dbname) !== 0)) {
            return false;
        }

        return $this->sameServer(new WpDbInfo($database), new WpDbInfo($wpdb));
    }

 
    private function ambiguousOwnership(): RuntimeException
    {
        return new RuntimeException(__('Cannot delete this staging site because another staging site uses the same table prefix. Contact support@wp-staging.com to correct the staging site records.', 'wp-staging'));
    }

 
    private function getCandidates(string $prefix, array $records): array
    {
        $ids = [];
        foreach ($records as $id => $record) {
            if (($record['ownsDatabaseTables'] ?? null) === false) {
                continue;
            }

            $site = new StagingSiteDto();
            $site->hydrate((array)$record);
            if ($site->getUsedPrefix() !== '' && strcasecmp($prefix, $site->getUsedPrefix()) === 0 && $this->recordUsesProduction($site)) {
                $ids[] = (string)$id;
            }
        }

        return $ids;
    }

 
    private function recordUsesProduction(StagingSiteDto $site): bool
    {
        global $wpdb;
        if (!$site->getIsExternalDatabase()) {
            return true;
        }

        if ($site->getDatabaseName() !== $wpdb->dbname && ($wpdb->get_var('SELECT @@lower_case_table_names') === '0' || strcasecmp($site->getDatabaseName(), $wpdb->dbname) !== 0)) {
            return false;
        }

        try {
            $info = new DbInfo($site->getDatabaseServer(), $site->getDatabaseUser(), $site->getDatabasePassword(), $site->getDatabaseName(), $site->getDatabaseSsl());
            return $this->sameServer($info, new WpDbInfo($wpdb));
        } catch (\Throwable $e) {
            throw $this->ambiguousOwnership();
        }
    }

 
    private function sameServer(WpDbInfo $first, WpDbInfo $second): bool
    {
        return $first->getServerIp() === '' || $second->getServerIp() === '' || $first->getServer() === $second->getServer();
    }

 
    private function restrictNonOwners(array $ids, $owner, array $snapshot)
    {
        $records = $this->sites->tryGettingStagingSites();
        $changed = false;
        foreach ($ids as $id) {
            if ($id !== $owner && isset($records[$id]) && $records[$id] === $snapshot[$id] && ($records[$id]['ownsDatabaseTables'] ?? null) !== false) {
                $records[$id]['ownsDatabaseTables'] = false;
                $changed = true;
            }
        }

        if ($changed) {
            $this->sites->updateStagingSites($records);
        }

        $saved = $this->sites->tryGettingStagingSites();
        foreach ($ids as $id) {
            if ($id !== $owner && isset($saved[$id]) && ($saved[$id]['ownsDatabaseTables'] ?? null) !== false) {
                throw $this->ambiguousOwnership();
            }
        }
    }







    private function readConfigPrefix(string $path, string $databaseName, string $databaseHost)
    {
        $source = @file_get_contents($path);
        if ($source === false) {
            return null;
        }

        $tokens = array_values(array_filter(token_get_all($source), function ($token) {
            return !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
        }));
        $values = [];
        $depth = 0;
        $conditional = false;
        foreach ($tokens as $i => $token) {
            if ($token === '{') {
                $depth++;
            } elseif ($token === '}') {
                $depth--;
            }

            if (!is_array($token)) {
                continue;
            }

            if (in_array($token[0], [T_IF, T_ELSEIF, T_ELSE, T_SWITCH, T_CASE, T_DEFAULT, T_WHILE, T_FOR, T_FOREACH, T_DO, T_TRY, T_CATCH, T_FINALLY, T_FUNCTION, T_RETURN, T_GOTO, T_THROW, T_EXIT, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_EVAL, T_HALT_COMPILER, T_NAMESPACE], true)) {
                $conditional = true;
            }

            $key = null;
            $value = null;
            if ($token[0] === T_VARIABLE && $token[1] === '$table_prefix') {
                $key = 'prefix';
                $value = $tokens[$i + 2] ?? null;
                if (($tokens[$i + 1] ?? null) !== '=' || ($tokens[$i + 3] ?? null) !== ';') {
                    return null;
                }
            } elseif ($token[0] === T_STRING && strtolower($token[1]) === 'define') {
                $name = $tokens[$i + 2] ?? null;
                if (!is_array($name) || !in_array(substr($name[1], 1, -1), ['DB_NAME', 'DB_HOST'], true)) {
                    continue;
                }

                $key = substr($name[1], 1, -1);
                $value = $tokens[$i + 4] ?? null;
                if (($tokens[$i + 1] ?? null) !== '(' || ($tokens[$i + 3] ?? null) !== ',' || ($tokens[$i + 5] ?? null) !== ')' || ($tokens[$i + 6] ?? null) !== ';') {
                    return null;
                }
            }

            if ($key === null) {
                continue;
            }

            $previous = $tokens[$i - 1] ?? null;
            $startsStatement = $previous === ';' || (is_array($previous) && $previous[0] === T_OPEN_TAG);
            if ($conditional || $depth !== 0 || !$startsStatement || isset($values[$key]) || !is_array($value) || $value[0] !== T_CONSTANT_ENCAPSED_STRING) {
                return null;
            }

            $values[$key] = substr($value[1], 1, -1);
        }

        if (($values['DB_NAME'] ?? null) !== $databaseName || ($values['DB_HOST'] ?? null) !== $databaseHost) {
            return null;
        }

        $prefix = $values['prefix'] ?? '';
        return preg_match('/^[a-zA-Z0-9_]+$/D', $prefix) ? $prefix : null;
    }
}
