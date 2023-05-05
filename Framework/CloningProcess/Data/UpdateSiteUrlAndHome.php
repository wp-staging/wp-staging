<?php

namespace WPStaging\Framework\CloningProcess\Data;

use WPStaging\Backend\Modules\Jobs\Exceptions\FatalException;
use WPStaging\Framework\Utils\Strings;

class UpdateSiteUrlAndHome extends DBCloningService
{
    /**
     * Replace "siteurl" and "home"
     * If complete network clone also update "domain" and "path" in "blogs" and "site" table
     * @return bool
     */
    protected function internalExecute()
    {
        if ($this->isNetworkClone()) {
            $this->updateSiteTable();
            return $this->updateAllOptionsTables();
        }

        return $this->updateOptionsTable('options', $this->dto->getStagingSiteUrl());
    }

    /**
     * Wrapper for DOMAIN_CURRENT_SITE for mocking
     * @return string
     */
    protected function getCurrentSiteDomain()
    {
        return DOMAIN_CURRENT_SITE;
    }

    /**
     * Wrapper for PATH_CURRENT_SITE for mocking
     * @return string
     */
    protected function getCurrentSitePath()
    {
        return PATH_CURRENT_SITE;
    }

    /**
     * Wrapper for get_sites for mocking
     *
     * @return array
     */
    protected function getSites()
    {
        return get_sites();
    }

    /**
     * @return bool
     */
    protected function updateAllOptionsTables()
    {
        $stagingURLhasWWWPrefix = false;
        $stagingSiteURL = $this->dto->getStagingSiteUrl();
        if (strpos($stagingSiteURL, '//www.') !== false) {
            $stagingURLhasWWWPrefix = true;
        }

        $baseDomain = $this->getCurrentSiteDomain();
        $basePath   = trailingslashit($this->getCurrentSitePath());
        $stagingSiteDomain = $this->dto->getStagingSiteDomain();
        $stagingSitePath   = trailingslashit($this->dto->getStagingSitePath());
        $str   = new Strings();
        foreach ($this->getSites() as $site) {
            $tableName = $this->getOptionTableWithoutBasePrefix($site->blog_id);

            $stagingDomain = $str->str_replace_first($baseDomain, $stagingSiteDomain, $site->domain);

            $subsiteHasWWWPrefix = false;
            // remove www prefix from domain
            if (strpos($stagingDomain, 'www.') !== false) {
                $stagingDomain = $str->str_replace_first('www.', '', $stagingDomain);
                $subsiteHasWWWPrefix = true;
            }

            $stagingPath = $str->str_replace_first($basePath, $stagingSitePath, $site->path);
            $this->updateBlogsTable($site->blog_id, $stagingDomain, $stagingPath);

            $wwwPrefix = '';
            if ($stagingURLhasWWWPrefix || $subsiteHasWWWPrefix) {
                $wwwPrefix = 'www.';
            }

            $siteUrl = parse_url($stagingSiteURL)["scheme"] . "://" . $wwwPrefix . $stagingDomain . $stagingPath;

            $this->updateOptionsTable($tableName, $siteUrl);
        }

        return true;
    }

    /**
     * @param string $tableName
     * @param string $siteUrl
     * @return bool
     *
     * @throws FatalException
     */
    protected function updateOptionsTable($tableName, $siteUrl)
    {
        if ($this->skipTable($tableName)) {
            $this->log("{$this->dto->getPrefix()}{$tableName} Skipped");
            return true;
        }

        $this->log("Updating siteurl and homeurl in {$this->dto->getPrefix()}{$tableName} to " . $siteUrl);
        // Replace URLs
        $result = $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}{$tableName} SET option_value = %s WHERE option_name = 'siteurl' or option_name='home'",
                $siteUrl
            )
        );

        if ($result === false) {
            throw new FatalException("Failed to update siteurl and homeurl in {$this->dto->getPrefix()}{$tableName}. {$this->dto->getStagingDb()->last_error}");
        }

        return true;
    }

    /**
     * Update Multisite Site Table
     * @return bool
     *
     * @throws FatalException
     */
    protected function updateSiteTable()
    {
        $tableName = 'site';
        $domain = $this->dto->getStagingSiteDomain();
        $path = trailingslashit($this->dto->getStagingSitePath());
        if ($this->skipTable($tableName)) {
            $this->log("{$this->dto->getPrefix()}{$tableName} Skipped");
            return true;
        }

        $this->log("Updating domain and path in {$this->dto->getPrefix()}{$tableName} to " . $domain . " and " . $path . " respectively");
        // Replace URLs
        $result = $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}{$tableName} SET domain = %s, path = %s",
                $domain,
                $path
            )
        );

        if ($result === false) {
            throw new FatalException("Failed to update domain and path in {$this->dto->getPrefix()}{$tableName}. {$this->dto->getStagingDb()->last_error}");
        }

        return true;
    }

    /**
     * @param int $blogID
     * @param string $domain
     * @param string $path
     * @return bool
     *
     * @throws FatalException
     */
    protected function updateBlogsTable($blogID, $domain, $path)
    {
        $tableName = 'blogs';
        if ($this->skipTable($tableName)) {
            $this->log("{$this->dto->getPrefix()}{$tableName} Skipped");
            return true;
        }

        $this->log("Updating domain in {$this->dto->getPrefix()}{$tableName} to " . $domain  . " and " . $path . " respectively");
        // Replace URLs
        $result = $this->dto->getStagingDb()->query(
            $this->dto->getStagingDb()->prepare(
                "UPDATE {$this->dto->getPrefix()}{$tableName} SET domain = %s, path = %s WHERE blog_id = %s",
                $domain,
                $path,
                $blogID
            )
        );

        if ($result === false) {
            throw new FatalException("Failed to update domain and path in {$this->dto->getPrefix()}{$tableName}. {$this->dto->getStagingDb()->last_error}");
        }

        return true;
    }
}
