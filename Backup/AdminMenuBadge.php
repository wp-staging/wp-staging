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

        $this->appendBadgeToMenuItem($submenu[$parentSlug], ['wpstg_backup'], true);
    }







    private function canReadBadgeState(): bool
    {
        return method_exists($this->backupScheduler, 'shouldShowMenuBadge');
    }







    private function appendBadgeToMenuItem(array &$menuItems, array $slugs, bool $isSubmenu = false)
    {
        foreach ($menuItems as $key => $item) {
            if (!isset($item[2])) {
                continue;
            }

            if (!in_array($item[2], $slugs, true)) {
                continue;
            }

            $title = rtrim($item[0]);
            if ($isSubmenu) {
                $lastSpace = strrpos($title, ' ');
                $prefix = $lastSpace === false ? '' : substr($title, 0, $lastSpace + 1);
                $lastWord = substr($title, strlen($prefix));
                $menuItems[$key][0] = $prefix . '<span style="white-space: nowrap;">' . $lastWord . $this->getBadgeHtml() . '</span>';
            } else {
                $menuItems[$key][0] = $title . $this->getBadgeHtml();
            }
            return $item[2];
        }

        return null;
    }

 
    private function getBadgeHtml(): string
    {
        $notificationCount = $this->getNotificationCount();

        return sprintf(
            ' <span class="update-plugins count-%1$d"><span class="plugin-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>',
            $notificationCount,
            esc_html($this->getBadgeLabel())
        );
    }

 
    private function getNotificationCount(): int
    {
        return 1;
    }






    private function getBadgeLabel(): string
    {
        if ($this->backupScheduler->getWarningType() === BackupScheduler::CRON_WARNING_TYPE_FAILURE) {
            return __('Last scheduled backup failed.', 'wp-staging');
        }

        return __('Scheduled backup is overdue.', 'wp-staging');
    }
}
