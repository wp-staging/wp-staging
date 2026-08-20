<?php

namespace WPStaging\Framework\Settings;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Traits\PagesTrait;
use WPStaging\Framework\Utils\Sanitize;

class DarkMode
{
    use PagesTrait;




    const OPTION_DEFAULT_COLOR_MODE = 'wpstg_default_color_mode';




    const OPTION_DEFAULT_OS_COLOR_MODE = 'wpstg_default_os_color_mode';




    private $sanitize;




    private $auth;




    private $defaultColorMode;

    public function __construct()
    {
        $this->auth = WPStaging::make(Auth::class);
        $this->sanitize = WPStaging::make(Sanitize::class);
        $this->getDefaultColorMode();
    }




    public function ajaxEnableDefaultColorMode()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        $defaultColorMode = isset($_POST['mode']) ? $this->sanitize->sanitizeString($_POST['mode']) : '';

        if (empty($defaultColorMode)) {
            wp_send_json_error();
        }

        if ($this->defaultColorMode === $defaultColorMode) {
            wp_send_json_success();
        }

        update_option(self::OPTION_DEFAULT_COLOR_MODE, $defaultColorMode);

        wp_send_json_success();
    }




    public function ajaxSetDefaultOsMode()
    {
        if (!$this->auth->isAuthenticatedRequest('', 'manage_options')) {
            wp_send_json_error();
        }

        $defaultOsColorMode = (isset($_POST['defaultOsColorMode'])) ? $this->sanitize->sanitizeString($_POST['defaultOsColorMode']) : '';

        if (empty($defaultOsColorMode)) {
            wp_send_json_error();
        }

        update_option(self::OPTION_DEFAULT_OS_COLOR_MODE, $defaultOsColorMode);
        wp_send_json_success([
            'defaultColorMode' => $this->defaultColorMode,
        ]);
    }




    public function mayBeShowDarkMode()
    {
        if (!$this->isWPStagingAdminPage()) {
            return;
        }

        if (!$this->isDarkModeEnabled()) {
            return;
        }

        add_filter('admin_body_class', function ($classes) {
            return $classes . ' wpstg-dark';
        });
    }




    private function isDarkModeEnabled(): bool
    {
        $defaultColorMode = get_option(self::OPTION_DEFAULT_COLOR_MODE, '');
        if (empty($defaultColorMode)) {
            return false;
        }

        $defaultOsColorMode = get_option(self::OPTION_DEFAULT_OS_COLOR_MODE, '');

        if ($defaultColorMode === 'system' && $defaultOsColorMode === 'light') {
            return false;
        }

        if ($defaultColorMode === 'light') {
            return false;
        }

        return true;
    }




    private function getDefaultColorMode()
    {
        $this->defaultColorMode = get_option(self::OPTION_DEFAULT_COLOR_MODE, '');
        return $this->defaultColorMode;
    }
}
