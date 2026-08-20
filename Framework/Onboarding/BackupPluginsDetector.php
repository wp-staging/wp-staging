<?php

namespace WPStaging\Framework\Onboarding;








class BackupPluginsDetector
{








    const SUPPORTED_PLUGINS = [
        'all-in-one-wp-migration'    => ['all_in_one_wp_migration', 'All-in-One WP Migration'],
        'updraftplus'                => ['updraftplus', 'UpdraftPlus'],
        'duplicator'                 => ['duplicator', 'Duplicator'],
        'wpvivid-backuprestore'      => ['wpvivid', 'WPvivid'],
        'backuply'                   => ['backuply', 'Backuply'],
        'backwpup'                   => ['backwpup', 'BackWPup'],
        'migrate-guru'               => ['migrate_guru', 'Migrate Guru'],
        'backup'                     => ['jetbackup', 'JetBackup'],
        'blogvault-real-time-backup' => ['blogvault', 'BlogVault'],
        'backup-backup'              => ['backup_migration', 'Backup Migration'],
    ];

 
    const NO_COMPETITOR = 'none';

 
    private $detected = false;

    public function hasCompetingPlugin(): bool
    {
        return $this->detect() !== null;
    }





    public function getCompetitorId(): string
    {
        $detected = $this->detect();

        return $detected === null ? self::NO_COMPETITOR : $detected[0];
    }





    public function getCompetitorName(): string
    {
        $detected = $this->detect();

        return $detected === null ? '' : $detected[1];
    }

    private function detect()
    {
        if ($this->detected !== false) {
            return $this->detected;
        }

        $active         = $this->getActivePluginSlugs();
        $this->detected = null;

        foreach (self::SUPPORTED_PLUGINS as $slug => $plugin) {
            if (isset($active[$slug])) {
                $this->detected = $plugin;
                break;
            }
        }

        return $this->detected;
    }

    private function getActivePluginSlugs(): array
    {
        $plugins = (array)get_option('active_plugins', []);

        if (is_multisite()) {
            $plugins = array_merge($plugins, array_keys((array)get_site_option('active_sitewide_plugins', [])));
        }

        return array_flip(array_map('dirname', $plugins));
    }
}
