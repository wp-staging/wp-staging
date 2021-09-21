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
     * The old option that was used to store the staging sites
     * @deprecated 4.0.5
     */
    const OLD_STAGING_SITES_OPTION = 'wpstg_existing_clones_beta';

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
     * Upgrade old existing clone beta option to new staging site option
     *
     * @see \WPStaging\Backend\Upgrade\Upgrade::upgrade2_8_7 (Free version)
     * @see \WPStaging\Backend\Pro\Upgrade\Upgrade::upgrade4_0_5 (Pro version)
     */
    public function upgradeStagingSitesOption()
    {
        // Get the staging sites from old option
        $oldSites = get_option(self::OLD_STAGING_SITES_OPTION, []);

        // Early bail: No sites to migrate
        if (empty($oldSites)) {
            return;
        }

        $newSites = get_option(self::STAGING_SITES_OPTION, []);

        // Convert old format to new, including when there are staging sites in both formats
        $allStagingSites = $newSites;

        foreach ($oldSites as $oldSiteSlug => $oldSite) {
            // Migrate old site to new format
            if (!array_key_exists($oldSiteSlug, $allStagingSites)) {
                $allStagingSites[$oldSiteSlug] = $oldSite;
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
            update_option('wpstg_staging_sites_backup', $oldSites, false);
            delete_option(self::OLD_STAGING_SITES_OPTION);
        } else {
            if (defined('WPSTG_DEBUG') && WPSTG_DEBUG) {
                error_log('WPSTAGING DEBUG: update_option for option renaming of staging sites failed.');
            }
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
     * @return boolean
     */
    public function updateStagingSites($stagingSites)
    {
        return update_option(self::STAGING_SITES_OPTION, $stagingSites);
    }
}
