<?php

namespace WPStaging\Frontend;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\AccessToken;
use WPStaging\Framework\SiteInfo;

class LoginAfterRestore
{




    public function showMessage()
    {
 
        if (!isset($_GET['wpstgAfterRestore']) || !Sanitize::sanitizeBool($_GET['wpstgAfterRestore'])) {
            return;
        }

 
        if (!isset($_GET['accessToken'])) {
            return;
        }

 
 
        $auth = WPStaging::make(AccessToken::class);

 
        if (!$auth->isValidToken($_GET['accessToken'])) {
            return;
        }

 
        $adminEmails = $this->getListOfAdminEmails();

        $isRestoredFromWpCom      = $this->getIsRestoredFromWpCom();
        $resetPasswordArticleLink = 'https://wp-staging.com/reset-your-wordpress-admin-password-manually/';

        include WPSTG_VIEWS_DIR . 'frontend/loginAfterRestore.php';
    }




    protected function getIsRestoredFromWpCom(): bool
    {
 
        $siteInfo = WPStaging::make(SiteInfo::class);
 
        if ($siteInfo->isHostedOnWordPressCom()) {
            return false;
        }

        if (isset($_GET['wpstgIsBackupCreatedOnWordPressCom']) && Sanitize::sanitizeBool($_GET['wpstgIsBackupCreatedOnWordPressCom'])) {
            return true;
        }

        return false;
    }




    private function getListOfAdminEmails()
    {
        $adminEmails = get_users([
            'role'   => 'administrator',
            'fields' => [
                'user_email',
            ],
            'number' => 10,
        ]);

 
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

 
        if (empty($adminEmails)) {
            return [];
        }

        return $adminEmails;
    }
}
