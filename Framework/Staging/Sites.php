<?php

namespace WPStaging\Framework\Staging;

/**
 * Class Sites
 *
 * This is used to manage settings on the staging site
 *
 * @package WPStaging\Framework\Staging
 *
 * @todo Manage staging sites option CRUD here?
 */
class Sites
{
    /**
     * The option that stores the staging sites
     */
    const STAGING_SITES_OPTION = 'wpstg_staging_sites';

    /**
     * The option that stores login link settings
     */
    const STAGING_LOGIN_LINK_SETTINGS = 'wpstg_login_link_settings';

    /**
     * The old option that was used to store the staging sites
     * @deprecated 4.0.5
     */
    const OLD_STAGING_SITES_OPTION = 'wpstg_existing_clones_beta';

    /**
     * Before upgrading structure, backup old staging site options
     * @since 4.0.6
     */
    const BACKUP_STAGING_SITES_OPTION = 'wpstg_staging_sites_backup';

    /**
     * Missing cloneName routine executed
     * @since 4.0.7
     */
    const MISSING_CLONE_NAME_ROUTINE_EXECUTED = 'wpstg_missing_cloneName_routine_executed';

    /**
     * Return list of staging sites in descending order of their creation time.
     *
     * @return array
     */
    public function getSortedStagingSites()
    {
        $stagingSites = get_option(self::STAGING_SITES_OPTION, []);

        // No need to sort if no sites or only one site
        if (empty($stagingSites) || count($stagingSites) === 1) {
            return $stagingSites;
        }

        // Sort staging sites in descending order
        uasort($stagingSites, function ($site1, $site2) {
            // If datetime is same, sort by directory name
            // Will also work if both sites datetime are not set
            if ($site1['datetime'] === $site2['datetime']) {
                return strcmp($site2['directoryName'], $site1['directoryName']);
            }

            if (!isset($site1['datetime'])) {
                return 1;
            }

            if (!isset($site2['datetime'])) {
                return -1;
            }

            return $site2['datetime'] < $site1['datetime'] ? -1 : 1;
        });

        return $stagingSites;
    }

    /**
     * Copy data from old staging site option wpstg_existing_clones_beta to new staging site option wpstg_staging_sites
     *
     * @see \WPStaging\Backend\Upgrade\Upgrade::upgrade2_8_7 (Free version)
     * @see \WPStaging\Backend\Pro\Upgrade\Upgrade::upgrade4_0_5 (Pro version)
     */
    public function upgradeStagingSitesOption()
    {
        $newSitesOption = get_option(self::STAGING_SITES_OPTION, []);

        // Early bail: If it's already populated and not empty do nothing. It has been migrated already.
        if (!empty($newSiteOption)) {
            return;
        }

        // If its no valid array, it is broken
        if (!is_array($newSitesOption)) {
            $newSitesOption = [];
        }

        // Get the staging sites from old option
        $oldSitesOption = get_option(self::OLD_STAGING_SITES_OPTION, []);

        // Early bail: No sites to migrate
        if (empty($oldSitesOption)) {
            return;
        }

        // Convert old format to new, including when there are staging sites in both formats
        $allStagingSites = $newSitesOption;

        foreach ($oldSitesOption as $oldSiteSlug => $oldSite) {
            // Migrate old site to new format
            if (!array_key_exists($oldSiteSlug, $allStagingSites)) {
                $allStagingSites[$oldSiteSlug] = $oldSite;
                continue;
            }

            // If key exists and path matches, skip
            if ($allStagingSites[$oldSiteSlug]['path'] === $oldSite['path']) {
                continue;
            }

            // Migrate old site to new format when site slug exists in both options
            $i = 0;

            do {
                $oldSiteSlug = $oldSiteSlug . '_' . $i;
            } while (array_key_exists($oldSiteSlug, $allStagingSites));

            $allStagingSites[$oldSiteSlug] = $oldSite;
        }

        if (update_option(self::STAGING_SITES_OPTION, $allStagingSites)) {
            // Keep a backup just in case
            update_option(self::BACKUP_STAGING_SITES_OPTION, $oldSitesOption, false);
            delete_option(self::OLD_STAGING_SITES_OPTION);
        }
    }

    /**
     * Will try gettings staging sites from new option
     * If that is empty, will get staging sites from old option
     *
     * @return array
     */
    public function tryGettingStagingSites()
    {
        $stagingSites = get_option(self::STAGING_SITES_OPTION, []);
        if (!empty($stagingSites)) {
            return $stagingSites;
        }

        return get_option(self::OLD_STAGING_SITES_OPTION, []);
    }

    /**
     * Update staging sites option
     *
     * @param array $stagingSites
     * @return bool
     */
    public function updateStagingSites($stagingSites)
    {
        return update_option(self::STAGING_SITES_OPTION, $stagingSites);
    }

    /**
     * Upgrade the staging site data structure, add the missing cloneName, if not present
     */
    public function addMissingCloneNameUpgradeStructure()
    {
        $isAdded = get_option(self::MISSING_CLONE_NAME_ROUTINE_EXECUTED, false);
        if ($isAdded) {
            return;
        }

        // Current options
        $sites = $this->tryGettingStagingSites();

        // Early bail if no sites
        if (empty($sites)) {
            update_option(self::MISSING_CLONE_NAME_ROUTINE_EXECUTED, true);
            return;
        }

        // Add missing cloneName if not exists
        foreach ($sites as $key => $site) {
            if (isset($sites[$key]['cloneName'])) {
                continue;
            }

            $sites[$key]['cloneName'] = $sites[$key]['directoryName'];
        }

        $this->updateStagingSites($sites);
        update_option(self::MISSING_CLONE_NAME_ROUTINE_EXECUTED, true);
    }

    /**
     * Sanitize the clone name to be used as directory
     *
     * @param string $cloneName
     * @return string
     */
    public function sanitizeDirectoryName($cloneName)
    {
        $cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($cloneName));
        return substr($cloneDirectoryName, 0, 16);
    }

    /**
     * Return false if site not exists else return reason behind existing
     *
     * @param string $directoryName
     * @return bool|string
     */
    public function isCloneExists($directoryName)
    {
        $cloneDirectoryPath = trailingslashit(get_home_path()) . $directoryName;
        if (!wpstg_is_empty_dir($cloneDirectoryPath)) {
            return sprintf(__("Warning: Use another site name! Clone destination directory %s already exists and is not empty. As default, WP STAGING uses the site name as subdirectory for the clone.", 'wp-staging'), $cloneDirectoryPath);
        }

        $stagingSites = $this->tryGettingStagingSites();
        foreach ($stagingSites as $site) {
            if ($site['directoryName'] === $directoryName) {
                return __("Site name is already in use, please choose another name for the staging site.", "wp-staging");
            }
        }

        return false;
    }
}
