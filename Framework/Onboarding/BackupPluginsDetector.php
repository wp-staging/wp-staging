<?php

namespace WPStaging\Framework\Onboarding;

/**
 * Detects competing backup plugins that are active alongside WP STAGING.
 *
 * The list is deliberately closed: only plugins WP STAGING explicitly knows
 * about are recognised, and only their normalized identifier ever leaves the
 * site. When several are active the first entry wins.
 */
class BackupPluginsDetector
{
    /**
     * Plugin directory => [normalized id, display name], most installed first.
     *
     * Keyed on the directory rather than the main plugin file: wordpress.org
     * guarantees the slug, not the filename inside it.
     *
     * @var array<string, string[]>
     */
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

    /** @var string Reported when no supported competitor is active. */
    const NO_COMPETITOR = 'none';

    /** @var string[]|null|false The [id, name] pair found, null when none was, false before detection ran. */
    private $detected = false;

    public function hasCompetingPlugin(): bool
    {
        return $this->detect() !== null;
    }

    /**
     * @return string The normalized identifier of the highest priority active
     *                backup plugin, or `none`. Safe to send to analytics.
     */
    public function getCompetitorId(): string
    {
        $detected = $this->detect();

        return $detected === null ? self::NO_COMPETITOR : $detected[0];
    }

    /**
     * @return string The human readable name of the detected plugin, for use in
     *                the interface. Empty when nothing was detected.
     */
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
