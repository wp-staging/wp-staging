<?php

namespace WPStaging\Framework\Utils;

use wpdb;
use ArrayIterator;
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

    /**
     * Check if the current user has the grants given in arguments.
     *
     * @param array $grantsToCheck
     * @return bool
     */
    public function isAllowed(array $grantsToCheck): bool
    {
        $grants = $this->wpdb->get_results("SHOW GRANTS;");
        if (empty($grants) || $this->wpdb->last_error) {
            return false;
        }

        $hasGranted = array_filter($grants, function ($grant) use ($grantsToCheck) {
            $grantString = (new ArrayIterator($grant))->current();

            // Check if this grant applies to our database or all databases
            if (!$this->hasGrantForCurrentDatabase($grantString)) {
                return false;
            }

            // Check if ALL PRIVILEGES is granted (covers everything)
            if ($this->hasAllPrivileges($grantString)) {
                return true;
            }

            // Check specific permissions
            return $this->hasRequiredPermissions($grantString, $grantsToCheck);
        });

        return !empty($hasGranted);
    }

    /**
     * @return string
     */
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
            $grantString = (new ArrayIterator($grant))->current();
            $grantString = preg_replace('/IDENTIFIED BY PASSWORD\s+([\'"])(?:\\\\.|(?!\1).)*\1/', "IDENTIFIED BY PASSWORD '********'", $grantString);
            $grantString = preg_replace('/IDENTIFIED BY\s+([\'"])(?:\\\\.|(?!\1).)*\1/', "IDENTIFIED BY '********'", $grantString);
            $grantsHtml .= PHP_EOL . $grantString . ';';
        }

        $data .= PHP_EOL . __('User Grants: ', 'wp-staging') . $grantsHtml;
        $data .= '</textarea>';

        return wp_kses_post($data);
    }

    /**
     * @param wpdb $wpdb
     * @return void
     */
    public function setDB(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
    }

    /**
     * @param string $grantString
     * @return bool
     */
    private function hasGrantForCurrentDatabase(string $grantString): bool
    {
        $dbName = $this->wpdb->dbname;
        // Global privileges (applies to all databases)
        if (stripos($grantString, '*.*') !== false) {
            return true;
        }

        // Database-specific grants - handle various formats:
        // `dbname`.*, "dbname".*, dbname.*, ON `dbname`.*, etc.
        $patterns = [
            '/\bON\s+\*\.\*/i',                                  // Global: ON *.*
            '/\bON\s+`' . preg_quote($dbName, '/') . '`\.\*/i',  // Quoted: ON `dbname`.*
            '/\bON\s+"' . preg_quote($dbName, '/') . '"\.\*/i',  // Double-quoted: ON "dbname".*
            '/\bON\s+' . preg_quote($dbName, '/') . '\.\*/i',    // Unquoted: ON dbname.*
            '/`' . preg_quote($dbName, '/') . '`\.\*/i',         // Direct reference: `dbname`.*
            '/"' . preg_quote($dbName, '/') . '"\.\*/i',         // Direct double quoted: "dbname".*
            '/\b' . preg_quote($dbName, '/') . '\.\*/i',         // Direct unquoted: dbname.*
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $grantString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $grantString
     * @return bool
     */
    private function hasAllPrivileges(string $grantString): bool
    {
        return preg_match('/\bGRANT\s+ALL(\s+PRIVILEGES)?\b/i', $grantString) === 1; // Match both "ALL" and "ALL PRIVILEGES" formats
    }

    /**
     * @param string $grantString
     * @param array $grantsToCheck
     * @return bool
     */
    private function hasRequiredPermissions(string $grantString, array $grantsToCheck): bool
    {
        if (!preg_match('/GRANT\s+(.*?)\s+ON\s+/i', $grantString, $matches)) {
            return false;
        }

        $permissionsString       = strtoupper(trim($matches[1]));
        $permissionsString       = preg_replace('/\s*,\s*/', ',', $permissionsString);
        $grantedPermissions      = array_filter(array_map('trim', explode(',', $permissionsString))); // Split and normalize granted permissions
        $grantedPermissionsAssoc = array_flip($grantedPermissions); // Convert granted permissions to associative array for O(1) lookups

        // Check each required permission
        foreach ($grantsToCheck as $requiredPermission) {
            $requiredPermission = strtoupper(trim($requiredPermission));
            if (!in_array($requiredPermission, $grantedPermissions, true)) {
                return false;
            }
        }

        return true;
    }
}
