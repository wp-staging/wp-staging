<?php

namespace WPStaging\Framework\Utils;

use wpdb;
use ArrayIterator;
use WPStaging\Framework\Security\Auth;





class DBPermissions
{
 
    protected $wpdb;

 
    private $auth;

    public function __construct(wpdb $wpdb, Auth $auth)
    {
        $this->wpdb = $wpdb;
        $this->auth = $auth;
    }




    public function ajaxCheckDBPermissions()
    {
        if (!$this->auth->isAuthenticatedRequest()) {
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

        $action = !empty($type) ? $type : 'restore';
        $permissions = $action === 'push' ? 'CREATE, UPDATE, ALTER, INSERT, DROP' : 'CREATE, UPDATE, INSERT, DROP';

        $message = sprintf(
            __("The database user might not have sufficient permissions to use the %s action. Continue the process anyway by clicking the 'Proceed' button or change the user's DB permissions and resume the process.<br/><br/> Required permissions are: %s.", 'wp-staging'),
            $action,
            $permissions
        );


        $message = '<span id="wpstg-permission-info-output">' . $message . '</span>';
        $message .= '<span id="wpstg-permission-info-data">' . $this->getDebugInfo() . '</span>';
        $message .= '<br/><button type="button" id="wpstg-db-permission-show-full-message" class="wpstg-link-btn wpstg-blue-primary">' . __("Show Full Message", "wp-staging") . '</button>';

        wp_send_json_error([
            'message' => wp_kses_post($message),
        ]);
    }







    public function isAllowed(array $grantsToCheck): bool
    {
        $grants = $this->wpdb->get_results("SHOW GRANTS;");
        if (empty($grants) || $this->wpdb->last_error) {
            return false;
        }

        $hasGranted = array_filter($grants, function ($grant) use ($grantsToCheck) {
            $grantString = (new ArrayIterator((array)$grant))->current();

 
            if (!$this->hasGrantForCurrentDatabase($grantString)) {
                return false;
            }

 
            if ($this->hasAllPrivileges($grantString)) {
                return true;
            }

 
            return $this->hasRequiredPermissions($grantString, $grantsToCheck);
        });

        return !empty($hasGranted);
    }




    public function getDebugInfo(): string
    {
        $dbUser = empty($_POST['databaseUser']) ? DB_USER : sanitize_text_field($_POST['databaseUser']);
        $dbName = empty($_POST['databaseDatabase']) ? DB_NAME : sanitize_text_field($_POST['databaseDatabase']);
        $dbHost = empty($_POST['databaseServer']) ? DB_HOST : sanitize_text_field($_POST['databaseServer']);

        $data = '<textarea class="wpstg-permission-info-output wpstg-textbox" readonly="readonly" name="wpstg-permission-info" title="' . __('Please copy and paste this message and report it to us!', 'wp-staging') . '">';
        $data .= PHP_EOL . __('DB Name: ', 'wp-staging') . $dbName;
        $data .= PHP_EOL . __('DB User: ', 'wp-staging') . $dbUser;
        $data .= PHP_EOL . __('DB Host: ', 'wp-staging') . $dbHost;

        $grants = $this->wpdb->get_results("SHOW GRANTS;");
        if (empty($grants) || $this->wpdb->last_error) {
            $data .= PHP_EOL . __('wpdb query error: ', 'wp-staging') . $this->wpdb->last_error;
            return wp_kses_post($data);
        }

        $grantsHtml = '';
        foreach ($grants as $grant) {
            $grantString = (new ArrayIterator((array)$grant))->current();
            $grantString = preg_replace('/IDENTIFIED BY PASSWORD\s+([\'"])(?:\\\\.|(?!\1).)*\1/', "IDENTIFIED BY PASSWORD '********'", $grantString);
            $grantString = preg_replace('/IDENTIFIED BY\s+([\'"])(?:\\\\.|(?!\1).)*\1/', "IDENTIFIED BY '********'", $grantString);
            $grantsHtml .= PHP_EOL . $grantString . ';';
        }

        $data .= PHP_EOL . __('User Grants: ', 'wp-staging') . $grantsHtml;
        $data .= '</textarea>';

        return wp_kses_post($data);
    }





    public function setDB(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }





    private function hasGrantForCurrentDatabase(string $grantString): bool
    {
        $dbName = $this->wpdb->dbname;
 
        if (stripos($grantString, '*.*') !== false) {
            return true;
        }

 
 
 
        $escapedDbName = str_replace('_', '\\_', $dbName);

        $patterns = [
            '/\bON\s+\*\.\*/i', 
            '/\bON\s+`' . preg_quote($dbName, '/') . '`\.\*/i', 
            '/\bON\s+`' . preg_quote($escapedDbName, '/') . '`\.\*/i', 
            '/\bON\s+"' . preg_quote($dbName, '/') . '"\.\*/i', 
            '/\bON\s+"' . preg_quote($escapedDbName, '/') . '"\.\*/i', 
            '/\bON\s+' . preg_quote($dbName, '/') . '\.\*/i', 
            '/\bON\s+' . preg_quote($escapedDbName, '/') . '\.\*/i', 
            '/`' . preg_quote($dbName, '/') . '`\.\*/i', 
            '/`' . preg_quote($escapedDbName, '/') . '`\.\*/i', 
            '/"' . preg_quote($dbName, '/') . '"\.\*/i', 
            '/"' . preg_quote($escapedDbName, '/') . '"\.\*/i', 
            '/\b' . preg_quote($dbName, '/') . '\.\*/i', 
            '/\b' . preg_quote($escapedDbName, '/') . '\.\*/i', 
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $grantString)) {
                return true;
            }
        }

        return false;
    }





    private function hasAllPrivileges(string $grantString): bool
    {
        return preg_match('/\bGRANT\s+ALL(\s+PRIVILEGES)?\b/i', $grantString) === 1; 
    }






    private function hasRequiredPermissions(string $grantString, array $grantsToCheck): bool
    {
        if (!preg_match('/GRANT\s+(.*?)\s+ON\s+/i', $grantString, $matches)) {
            return false;
        }

        $permissionsString       = strtoupper(trim($matches[1]));
        $permissionsString       = preg_replace('/\s*,\s*/', ',', $permissionsString);
        $grantedPermissions      = array_filter(array_map('trim', explode(',', $permissionsString))); 
        $grantedPermissionsAssoc = array_flip($grantedPermissions); 

 
        foreach ($grantsToCheck as $requiredPermission) {
            $requiredPermission = strtoupper(trim($requiredPermission));
            if (!in_array($requiredPermission, $grantedPermissions, true)) {
                return false;
            }
        }

        return true;
    }
}
