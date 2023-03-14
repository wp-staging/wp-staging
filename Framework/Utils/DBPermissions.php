<?php

namespace WPStaging\Framework\Utils;

use wpdb;

/**
 * Class Check user permissions on DB
 * @package WPStaging\Framework\Utils
 */
class DBPermissions
{
    /** @var wpdb */
    protected $db;

    public function __construct(wpdb $db)
    {
        $this->db = $db;
    }

    /**
     * Check if the current user has the grants given in arguments.
     *
     * @param  array $grantsToCheck
     * @return bool
     */
    public function isAllowed($grantsToCheck)
    {
        $grants = $this->db->get_results("SHOW GRANTS;");
        $hasGranted = array_filter($grants, function ($grant) use ($grantsToCheck) {
            $grant = current($grant);
            if (stripos($grant, '`' . DB_NAME . '`') !== false || stripos($grant, '*.*') !== false) {
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
     * @param  wpdb $db
     * @return void
     */
    public function setDB(wpdb $db)
    {
        $this->db = $db;
    }
}
