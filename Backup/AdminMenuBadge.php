<?php

namespace WPStaging\Backup;





class AdminMenuBadge
{
 
    private $backupScheduler;

    public function __construct(BackupScheduler $backupScheduler)
    {
        $this->backupScheduler = $backupScheduler;
    }

 
    public function maybeAddBadge()
    {
        if (!$this->canReadBadgeState() || !$this->backupScheduler->shouldShowMenuBadge()) {
            return;
        }

        global $menu, $submenu;

        $parentSlug = $this->appendBadgeToMenuItem($menu, ['wpstg_clone', 'wpstg_backup']);

        if ($parentSlug === null || !isset($submenu[$parentSlug])) {
            return;
        }

        $this->appendBadgeToMenuItem($submenu[$parentSlug], ['wpstg_backup']);
    }







    private function canReadBadgeState(): bool
    {
        return method_exists($this->backupScheduler, 'shouldShowMenuBadge');
    }






    private function appendBadgeToMenuItem(array &$menuItems, array $slugs)
    {
        foreach ($menuItems as $key => $item) {
            if (!isset($item[2])) {
                continue;
            }

            if (!in_array($item[2], $slugs, true)) {
                continue;
            }

            $menuItems[$key][0] .= $this->getBadgeHtml();
            return $item[2];
        }

        return null;
    }

 
    private function getBadgeHtml(): string
    {
        return sprintf(
            ' <span class="update-plugins count-1"><span class="plugin-count" aria-hidden="true">!</span><span class="screen-reader-text">%s</span></span>',
            esc_html($this->getBadgeLabel())
        );
    }






    private function getBadgeLabel(): string
    {
        if ($this->backupScheduler->getWarningType() === BackupScheduler::CRON_WARNING_TYPE_FAILURE) {
            return __('Last scheduled backup failed.', 'wp-staging');
        }

        return __('Scheduled backup is overdue.', 'wp-staging');
    }
}
