<?php

namespace WPStaging\Framework\Settings;

use WPStaging\Core\WPStaging;
use WPStaging\Framework\Notices\Notices;
use WPStaging\Framework\Security\Auth;
use WPStaging\Framework\Utils\Sanitize;

class DarkMode
{
    /**
     * @var string
     */
    const OPTION_DEFAULT_COLOR_MODE = 'wpstg_default_color_mode';

    /**
     * @var string
     */
    const OPTION_DEFAULT_OS_COLOR_MODE = 'wpstg_default_os_color_mode';

    /**
     * @var Sanitize
     */
    private $sanitize;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @var string
     */
    private $defaultColorMode;

    /**
     * @var string
     */
    private $defaultOsColorMode;

    /**
     * @var Notices
     */
    private $notices;

    public function __construct()
    {
        $this->auth = WPStaging::make(Auth::class);
        $this->sanitize = WPStaging::make(Sanitize::class);
        $this->notices = WPStaging::make(Notices::class);
        $this->getDefaultColorMode();
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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
            'defaultColorMode' => $this->defaultColorMode
        ]);
    }

    /**
     * @return void
     */
    public function mayBeShowDarkMode()
    {
        $isDarkModeEnabled = $this->isDarkModeEnabled();

        if (!$isDarkModeEnabled) {
            return;
        }

        add_filter('admin_body_class', function ($classes) {
            return $classes . ' wpstg-dark';
        });
    }

    /**
     * @return bool
     */
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

    /**
     * @return string
     */
    private function getDefaultColorMode()
    {
        $this->defaultColorMode = get_option(self::OPTION_DEFAULT_COLOR_MODE, '');
        return $this->defaultColorMode;
    }

    /**
     * @return string
     */
    private function getDefaultOsColorMode()
    {
        $this->defaultOsColorMode = get_option(self::OPTION_DEFAULT_OS_COLOR_MODE, '');
        return $this->defaultOsColorMode;
    }
}
