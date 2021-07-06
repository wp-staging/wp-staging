<?php

namespace WPStaging\Frontend;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\AccessToken;

class LoginAfterImport
{
    /**
     * @see \WPStaging\Frontend\FrontendServiceProvider::registerLoginAfterImport
     */
    public function showMessage()
    {
        // Early bail: Not after Import
        if (!isset($_GET['wpstgAfterImport']) || $_GET['wpstgAfterImport'] !== 'yes') {
            return;
        }

        // Early bail: No access token
        if (!isset($_GET['accessToken'])) {
            return;
        }

        // Late instantiation, since this runs on the FE on every request
        $auth = WPStaging::getInstance()->getContainer()->make(AccessToken::class);

        // Early bail: Invalid access token
        if (!$auth->isValidToken($_GET['accessToken'])) {
            return;
        }

        // Used by loginAfterImport
        $adminEmails = $this->getListOfAdminEmails();
        include __DIR__ . '/views/loginAfterImport.php';
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
