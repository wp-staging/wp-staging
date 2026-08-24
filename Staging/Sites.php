<?php

namespace WPStaging\Staging;

use Exception;
use WPStaging\Framework\Exceptions\WPStagingException;
use WPStaging\Staging\Dto\StagingSiteDto;










class Sites
{



    const STAGING_SITES_OPTION = 'wpstg_staging_sites';




    const STAGING_LOGIN_LINK_SETTINGS = 'wpstg_login_link_settings';





    const OLD_STAGING_SITES_OPTION = 'wpstg_existing_clones_beta';





    const BACKUP_STAGING_SITES_OPTION = 'wpstg_staging_sites_backup';





    const MISSING_CLONE_NAME_ROUTINE_EXECUTED = 'wpstg_missing_cloneName_routine_executed';




    const STAGING_EXCLUDED_FILES_OPTION = 'wpstg_clone_excluded_files_list';




    const STAGING_EXCLUDED_HOSTING_FILES_OPTION = 'wpstg_clone_excluded_hosting_files';




    const THROW_EXCEPTION = true;







    public function getSortedStagingSites()
    {
        $stagingSites = $this->tryGettingStagingSites(self::THROW_EXCEPTION);

 
        if (empty($stagingSites) || count($stagingSites) === 1) {
            return $stagingSites;
        }

 
        uasort($stagingSites, function ($site1, $site2) {
 
 
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







    public function upgradeStagingSitesOption()
    {
        $newSitesOption = get_option(self::STAGING_SITES_OPTION, []);

 
        if (!is_array($newSitesOption)) {
            $newSitesOption = [];
        }

 
        $oldSitesOption = get_option(self::OLD_STAGING_SITES_OPTION, []);

 
        if (empty($oldSitesOption)) {
            return;
        }

 
        $allStagingSites = $newSitesOption;

        foreach ($oldSitesOption as $oldSiteSlug => $oldSite) {
 
            if (!array_key_exists($oldSiteSlug, $allStagingSites)) {
                $allStagingSites[$oldSiteSlug] = $oldSite;
                continue;
            }

 
            if ($allStagingSites[$oldSiteSlug]['path'] === $oldSite['path']) {
                continue;
            }

 
            $i = 0;

            do {
                $oldSiteSlug = $oldSiteSlug . '_' . $i;
            } while (array_key_exists($oldSiteSlug, $allStagingSites));

            $allStagingSites[$oldSiteSlug] = $oldSite;
        }

        if ($this->updateStagingSites($allStagingSites)) {
 
            update_option(self::BACKUP_STAGING_SITES_OPTION, $oldSitesOption, false);
            delete_option(self::OLD_STAGING_SITES_OPTION);
        }
    }








    public function tryGettingStagingSites(bool $throwException = false): array
    {
        $stagingSites = get_option(self::STAGING_SITES_OPTION, []);
        if (empty($stagingSites)) {
            return [];
        }

        if (is_array($stagingSites)) {
            return $stagingSites;
        }

        if ($throwException) {
            throw new WPStagingException('Staging sites option is not an array.');
        }

        return [];
    }







    public function updateStagingSites($stagingSites)
    {
        return update_option(self::STAGING_SITES_OPTION, $stagingSites, false);
    }




    public function addMissingCloneNameUpgradeStructure()
    {
        $isAdded = get_option(self::MISSING_CLONE_NAME_ROUTINE_EXECUTED, false);
        if ($isAdded) {
            return;
        }

 
        $sites = $this->tryGettingStagingSites();

 
        if (empty($sites)) {
            update_option(self::MISSING_CLONE_NAME_ROUTINE_EXECUTED, true);
            return;
        }

 
        foreach ($sites as $key => $site) {
            if (isset($sites[$key]['cloneName'])) {
                continue;
            }

            $sites[$key]['cloneName'] = $sites[$key]['directoryName'];
        }

        $this->updateStagingSites($sites);
        update_option(self::MISSING_CLONE_NAME_ROUTINE_EXECUTED, true);
    }







    public function sanitizeDirectoryName($cloneName)
    {
        $cloneDirectoryName = preg_replace("#\W+#", '-', strtolower($cloneName));
        return substr($cloneDirectoryName, 0, 16);
    }








    public function generateStagingSiteName(string $fallback): string
    {
        $nameList = [
            'enterprise',
            'voyager',
            'defiant',
            'discovery',
            'excelsior',
            'intrepid',
            'constitution',
            'reliant',
            'grissom',
            'yamato',
            'excelsior',
            'venture',
            'cerritos',
            'prometheus',
            'bellerophon',
            'sanpablo',
            'sutherland',
            'shenzhou',
            'titan',
            'reliant',
            'stargazer',
            'franklin',
            'protostar',
        ];

        shuffle($nameList);

 
        $existingDirectoryNames = wp_list_pluck($this->tryGettingStagingSites(), 'directoryName');

        foreach ($nameList as $name) {
            $name = $this->sanitizeDirectoryName(sanitize_text_field($name));
            if (!empty($name) && $this->isNameAvailableForNewSite($name, $existingDirectoryNames)) {
                return $name;
            }
        }

        $fallback = $this->sanitizeDirectoryName($fallback);
        if (!empty($fallback) && $this->isNameAvailableForNewSite($fallback, $existingDirectoryNames)) {
            return $fallback;
        }

        for ($i = 1; $i <= 10000; $i++) {
            $name = $this->sanitizeDirectoryName(sprintf('staging-%d', $i));
            if ($this->isNameAvailableForNewSite($name, $existingDirectoryNames)) {
                return $name;
            }
        }

        return empty($fallback) ? 'staging' : $fallback;
    }










    private function isNameAvailableForNewSite(string $name, array $existingDirectoryNames): bool
    {
        if (file_exists(trailingslashit(get_home_path()) . $name)) {
            return false;
        }

        return !in_array($name, $existingDirectoryNames, true);
    }








    public function isCloneExists($directoryName)
    {
        $cloneDirectoryPath = trailingslashit(get_home_path()) . $directoryName;
        if (is_file($cloneDirectoryPath)) {
            return sprintf(esc_html__("Warning: Use another site name! A file named %s already exists where the staging site would be created.", 'wp-staging'), $directoryName);
        }

        if (!wpstg_is_empty_dir($cloneDirectoryPath)) {
            return sprintf(esc_html__("Warning: Use another site name! Clone destination directory %s already exists and is not empty. As default, WP STAGING uses the site name as subdirectory for the clone.", 'wp-staging'), $cloneDirectoryPath);
        }

        $stagingSites = $this->tryGettingStagingSites();
        foreach ($stagingSites as $site) {
            if ($site['directoryName'] === $directoryName) {
                return sprintf(esc_html__("Site name %s is already in use, please choose another name for the staging site.", "wp-staging"), $directoryName);
            }
        }

        return false;
    }





    public function getStagingDirectories(): array
    {
        $stagingSites = $this->tryGettingStagingSites();
        return wp_list_pluck($stagingSites, 'path');
    }







    public function getStagingSiteDtoByCloneId(string $cloneId): StagingSiteDto
    {
        $stagingSites = $this->tryGettingStagingSites();
        if (empty($stagingSites)) {
            throw new Exception('No staging sites found.');
        }

        if (!array_key_exists($cloneId, $stagingSites)) {
            throw new Exception('Staging site not found.');
        }

        $stagingSiteArray = $stagingSites[$cloneId];
        $stagingSiteDto   = new StagingSiteDto();
        $stagingSiteDto->hydrate($stagingSiteArray);
        $stagingSiteDto->setCloneId($cloneId);

        return $stagingSiteDto;
    }







    public function getStagingSiteDtoByCloneName(string $cloneName): StagingSiteDto
    {
        $stagingSites = $this->tryGettingStagingSites();
        if (empty($stagingSites)) {
            throw new Exception('No staging sites found.');
        }

        foreach ($stagingSites as $cloneId => $stagingSiteArray) {
            if ($stagingSiteArray['cloneName'] === $cloneName) {
                $stagingSiteDto = new StagingSiteDto();
                $stagingSiteDto->hydrate($stagingSiteArray);
                $stagingSiteDto->setCloneId($cloneId);

                return $stagingSiteDto;
            }
        }

        throw new Exception('Staging site not found.');
    }





    public function isExistingClone(string $clone): bool
    {
        $existingClones = get_option(self::STAGING_SITES_OPTION, []);
        return isset($existingClones[$clone]);
    }
}
