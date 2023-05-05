<?php

namespace WPStaging\Frontend;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Facades\Sanitize;
use WPStaging\Framework\Security\AccessToken;

class LoginAfterRestore
{
    /**
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
        include __DIR__ . '/views/loginAfterRestore.php';
    }

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
