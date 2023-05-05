<?php

namespace WPStaging\Framework\Utils;

use wpdb;
use WPStaging\Framework\Security\Auth;

/**
 * Class Check user permissions on DB
 * @package WPStaging\Framework\Utils
 */
class DBPermissions
{
    /** @var wpdb */
    protected $wpdb;

    /** @var Auth */
    private $auth;

    public function __construct(wpdb $wpdb, Auth $auth)
    {
        $this->wpdb = $wpdb;
        $this->auth = $auth;
    }

    /**
     * Ajax check user permissions on DB before push process start
     */
    public function ajaxCheckDBPermissions()
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $type          = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $grantsToCheck = ['CREATE', 'UPDATE', 'INSERT', 'DROP'];
        if ($type === 'push') {
            $grantsToCheck[] = 'ALTER';
        }

        if ($this->isAllowed(['ALL PRIVILEGES']) || $this->isAllowed($grantsToCheck)) {
            wp_send_json_success();
        }

        $message = __("The database user might not have sufficient permissions to use the restore action. Continue the process anyway by clicking the 'Proceed' button or change the user's DB permissions and resume the process.<br/><br/> Required permissions are: CREATE, UPDATE, INSERT, DROP.", 'wp-staging');
        if ($type === 'push') {
            $message = __("The database user might not have sufficient permissions to use the push action. Continue the process anyway by clicking the 'Proceed' button or alter the user's DB permissions and resume the process.<br/><br/> Required permissions are: CREATE, UPDATE, ALTER, INSERT, DROP.", 'wp-staging');
        }
        $message = '<span id="wpstg-permission-info-output">' . $message . '</span>';
        $message .= '<span id="wpstg-permission-info-data">' . $this->getDebugInfo() . '</span>';
        $message .= '<br/><br/><button type="button" id="wpstg-db-permission-show-full-message" class="wpstg-link-btn wpstg-blue-primary">' . __("Show Full Message", "wp-staging") . '</button>';

        wp_send_json_error([
            'message' => wp_kses_post($message),
        ]);
    }

    /**
     * Check if the current user has the grants given in arguments.
     *
     * @param  array $grantsToCheck
     * @return bool
     */
    public function isAllowed($grantsToCheck)
    {
        $grants = $this->wpdb->get_results("SHOW GRANTS;");
        if (empty($grants) || $this->wpdb->last_error) {
            return false;
        }

        $hasGranted = array_filter($grants, function ($grant) use ($grantsToCheck) {
            $grant = current($grant);
            if (stripos($grant, '`' . DB_NAME . '`') !== false || stripos($grant, '`' . $this->wpdb->esc_like(DB_NAME) . '`') !== false || stripos($grant, '*.*') !== false) {
                foreach ($grantsToCheck as $value) {
                    if (!preg_match("/" . $value . "[,]/", $grant) && !preg_match("/" . $value . " ON/", $grant)) {
                        return false;
                    }
                }
                return true;
            }
        });
        if (!empty($hasGranted)) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getDebugInfo()
    {
        $data = '<textarea class="wpstg-permission-info-output wpstg-textbox" readonly="readonly" name="wpstg-permission-info" title="' . __('Please copy and paste this message and report it to us!', 'wp-staging') . '">';
        $data .= PHP_EOL . __('DB Name: ', 'wp-staging') . DB_NAME;
        $data .= PHP_EOL . __('DB User: ', 'wp-staging') . DB_USER;
        $data .= PHP_EOL . __('DB Host: ', 'wp-staging') . DB_HOST;

        $grants = $this->wpdb->get_results("SHOW GRANTS;");
        if (empty($grants) || $this->wpdb->last_error) {
            $data .= PHP_EOL . __('wpdb query error: ', 'wp-staging') . $this->wpdb->last_error;
            return wp_kses_post($data);
        }
        $grantsHtml = '';
        foreach ($grants as $grant) {
            $grantsHtml .= PHP_EOL . current($grant) . ';';
        }

        $data .= PHP_EOL . __('User Grants: ', 'wp-staging') . $grantsHtml;
        $data .= '</textarea>';

        return wp_kses_post($data);
    }

    /**
     * @param  wpdb $db
     * @return void
     */
    public function setDB(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

   /**
    * @return bool Whether the current request is considered to be authenticated.
    */
    protected function isAuthenticated()
    {
        return $this->auth->isAuthenticatedRequest();
    }
}
