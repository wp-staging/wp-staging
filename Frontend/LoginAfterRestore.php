<?php

namespace WPStaging\Frontend;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\SiteInfo;

class LoginAfterRestore
{
    /**
     * @return void
     * @see \WPStaging\Frontend\FrontendServiceProvider::registerLoginAfterRestore
     */
    public function showMessage()
    {
        // Early bail: Not after Restore
        if (!isset($_GET['wpstgAfterRestore']) || !Sanitize::sanitizeBool($_GET['wpstgAfterRestore'])) {
            return;
        }

        // Early bail: No access token
        if (!isset($_GET['accessToken'])) {
            return;
        }

        // Late instantiation, since this runs on the FE on every request
        /** @var AccessToken $auth */
        $auth = WPStaging::make(AccessToken::class);

        // Early bail: Invalid access token
        if (!$auth->isValidToken($_GET['accessToken'])) {
            return;
        }

        // Used by loginAfterRestore
        $adminEmails = $this->getListOfAdminEmails();

        $isRestoredFromWpCom      = $this->getIsRestoredFromWpCom();
        $resetPasswordArticleLink = 'https://wp-staging.com/reset-your-wordpress-admin-password-manually/';

        include WPSTG_VIEWS_DIR . 'frontend/loginAfterRestore.php';
    }

    /**
     * @return bool
     */
    protected function getIsRestoredFromWpCom(): bool
    {
        /** @var SiteInfo */
        $siteInfo = WPStaging::make(SiteInfo::class);
        // Should not be shown when restoring wp.com backup on wp.com site
        if ($siteInfo->isHostedOnWordPressCom()) {
            return false;
        }

        if (isset($_GET['wpstgIsBackupCreatedOnWordPressCom']) && Sanitize::sanitizeBool($_GET['wpstgIsBackupCreatedOnWordPressCom'])) {
            return true;
        }

        return false;
    }

    /**
     * @return string[] List of admin emails
     */
    private function getListOfAdminEmails()
    {
        $adminEmails = get_users([
            'role' => 'administrator',
            'fields' => [
                'user_email',
            ],
            'number' => 10,
        ]);

        // Early bail: Nothing to show
        if (!is_array($adminEmails) || empty($adminEmails)) {
            return [];
        }

        $adminEmails = array_map(function ($stdClass) {
            if (is_object($stdClass) && property_exists($stdClass, 'user_email')) {
                return $stdClass->user_email;
            }

            return null;
        }, $adminEmails);

        $adminEmails = array_filter($adminEmails, 'is_email');

        // Early bail: Nothing to show
        if (empty($adminEmails)) {
            return [];
        }

        return $adminEmails;
    }
}
