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

            $title  = rtrim($item[0]);
            $prefix = '';
            if ($isSubmenu) {
                $lastSpace = strrpos($title, ' ');
                $prefix    = $lastSpace === false ? '' : substr($title, 0, $lastSpace + 1);
                $title     = substr($title, strlen($prefix));
            }

            $menuItems[$key][0] = $prefix . $this->getLabelWithBadgeOnOneLine($title);
            return $item[2];
        }

        return null;
    }





    private function getLabelWithBadgeOnOneLine(string $label): string
    {
        return '<span style="white-space: nowrap; letter-spacing: -0.2px;">' . $label . $this->getBadgeHtml() . '</span>';
    }

 
    private function getBadgeHtml(): string
    {
        $notificationCount = $this->getNotificationCount();
        $compactCircle     = 'min-width:14px;height:14px;line-height:14px;border-radius:7px;padding:0 3px;'
            . 'margin-block:3px -1px;margin-inline:2px 0;font-size:9px;';

        return sprintf(
            '<span class="update-plugins count-%1$d" style="%3$s"><span class="plugin-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span></span>',
            $notificationCount,
            esc_html($this->getBadgeLabel()),
            $compactCircle
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
