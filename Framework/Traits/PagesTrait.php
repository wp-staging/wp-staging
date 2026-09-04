<?php

namespace WPStaging\Framework\Traits;

use WPStaging\Backend\Administrator;
use WPStaging\Framework\Facades\Sanitize;




trait PagesTrait
{






    public function isWPStagingAdminPage(): bool
    {
        if (is_admin() && $this->isWPStagingPageSlug()) {
            return true;
        }

        return $this->isWPStagingAjaxAction();
    }






    public function isWPStagingAdminPageWithoutAjax(): bool
    {
        return is_admin() && !wp_doing_ajax() && $this->isWPStagingPageSlug();
    }








    public function isWordPressUpdatePage(): bool
    {
        if (!is_admin()) {
            return false;
        }

        global $pagenow;

        return in_array($pagenow, ['plugins.php', 'update-core.php', 'themes.php', 'plugin-install.php'], true);
    }






    public function isWPStagingAjaxAction(): bool
    {
        if (!wp_doing_ajax()) {
            return false;
        }

        $ajaxAction = isset($_POST['action']) ? Sanitize::sanitizeString($_POST['action']) : null;
        return !empty($ajaxAction) && (strpos($ajaxAction, 'wpstg-') === 0 || strpos($ajaxAction, 'wpstg_') === 0);
    }




    public function isWPStagingInstallPage(): bool
    {
        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;
        return $currentPage === 'wpstg-install';
    }






    public function isWPStagingClonePage(): bool
    {
        if (!$this->isWPStagingAdminPage()) {
            return false;
        }

        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;
        return $currentPage === 'wpstg_clone';
    }




    public function isWPStagingWelcomePage(): bool
    {
        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;

        return $currentPage === 'wpstg-welcome';
    }




    private function isWPStagingPageSlug(): bool
    {
        $currentPage = isset($_GET["page"]) ? Sanitize::sanitizeString($_GET["page"]) : null;

        return !empty($currentPage) && in_array($currentPage, Administrator::ADMIN_PAGE_SLUGS, true);
    }
}
