<?php
/**
 * Globally applicable very tiny functions that have only one specific use case but that are needed more than one time.
 * We use snake case prefix 'wpstg_' to differentiate them with the rest of our code base
 * Yeah, evil in terms of some best "dogmatic" practices and made by laziness... but effective.
 * As they are prefixed we can easily find and refactor them over time.
 *
 * @todo refactor - Split this file into classes for strings, database, filesystem and so on
 *
 */

/**
 * Get directory permissions
 *
 * @return int
 */
function wpstg_get_permissions_for_directory()
{
    $octal = 0755;
    if (defined('FS_CHMOD_DIR')) {
        $octal = FS_CHMOD_DIR;
    }

    return apply_filters('wpstg_folder_permission', $octal);
}

/**
 * Get file permissions
 *
 * @return int
 */
function wpstg_get_permissions_for_file()
{
    if (defined('FS_CHMOD_FILE')) {
        return FS_CHMOD_FILE;
    }

    return 0644;
}

/**
 * PHP setup environment
 *
 * @return void
 */
function wpstg_setup_environment()
{
    // Set whether a client disconnect should abort script execution
    @ignore_user_abort(true);

    // Set maximum execution time
    @set_time_limit(0);

    // Set maximum time in seconds a script is allowed to parse input data
    @ini_set('max_input_time', '-1');

    // Set maximum backtracking steps
    @ini_set('pcre.backtrack_limit', PHP_INT_MAX);

}

/**
 * Windows Compatibility Fix
 * Replace Windows directory separator (Backward slash)
 * Replace backward slash with forward slash directory separator
 * Reason: Windows understands backward and forward slash while linux only understands forward slash
 *
 * @param string
 **/
function wpstg_replace_windows_directory_separator($path)
{
    return preg_replace('/[\\\\]+/', '/', $path);
}

/**
 * Mimics the mysql_real_escape_string function. Adapted from a post by 'feedr' on php.net.
 * @link   http://php.net/manual/en/function.mysql-real-escape-string.php#101248
 * @access public
 * @param string $input The string to escape.
 * @return string
 */
function wpstg_mysql_escape_mimic($input)
{
    if (is_array($input)) {
        return array_map(__METHOD__, $input);
    }
    if (!empty($input) && is_string($input)) {
        return str_replace(['\\', "\0", "\n", "\r", "'", '"', "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'], $input);
    }

    return $input;
}

/**
 * Search & Replace first occurence of string in haystack
 * @param string $haystack
 * @param string $needle
 * @return string
 */
function wpstg_replace_first_match($needle, $replace, $haystack)
{
    $result = $haystack;
    $pos = strpos($haystack, $needle);
    if ($pos !== false) {
        $result = substr_replace($haystack, $replace, $pos, strlen($needle));
    }
    return $result;
}

/**
 * Search & Replace last occurence of string in haystack
 * @param string $haystack
 * @param string $needle
 * @return string
 */
function wpstg_replace_last_match($needle, $replace, $haystack)
{
    $result = $haystack;
    $pos = strrpos($haystack, $needle);
    if ($pos !== false) {
        $result = substr_replace($haystack, $replace, $pos, strlen($needle));
    }
    return $result;
}

/**
 * Check if string is valid date
 * @param string $date
 * @param string $format
 * @return bool
 */
function wpstg_is_valid_date($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $date === $d->format($format);
}

/**
 * Convert all values of a string or an array into url decoded values
 * Main use for preventing Wordfence firewall rule 'local file inclusion'
 * @param mixed string | array $data
 * @return mixed string | array
 */
function wpstg_urldecode($data)
{
    if (empty($data)) {
        return $data;
    }

    if (is_string($data)) {
        return urldecode($data);
    }

    if (is_array($data)) {
        $array = [];
        foreach ($data as $string) {
            $array[] = urldecode($string);
        }
        return $array;
    }

    return $data;
}

/**
 * Check if it is a staging site
 *
 * @deprecated
 * @see         \WPStaging\Framework\SiteInfo::isStaging Removed in favor of this.
 * @todo        Remove this in future versions.
 *
 * @return bool
 */
function wpstg_is_stagingsite()
{
    return (new \WPStaging\Framework\SiteInfo)->isStaging();
}

/**
 * @param string $memory
 * @return int
 */
function wpstg_get_memory_in_bytes($memory)
{
    // Handle unlimited ones
    if ((int)$memory < 1) {
        //return (int) $memory;
        // 128 MB default value
        return (int)134217728;
    }

    $bytes = (int)$memory; // grab only the number
    $size = trim(str_replace($bytes, null, strtolower($memory))); // strip away number and lower-case it
    // Actual calculation
    switch ($size) {
        case 'k':
            $bytes *= 1024;
            break;
        case 'm':
            $bytes *= (1024 * 1024);
            break;
        case 'g':
            $bytes *= (1024 * 1024 * 1024);
            break;
    }

    return $bytes;
}

/**
 * Invalidate constraints
 * @param string $query
 * @return string
 */
function wpstg_unique_constraint($query)
{
    // Change name to random in all constraints, if there, to prevent trouble with existing  
    $query = preg_replace_callback("/CONSTRAINT\s`(\w+)`/", function () {
        return "CONSTRAINT `" . uniqid() . "`";
    }, $query);

    return $query;
}

/**
 * Get root relative path to the uploads folder, can be a custom folder e.g 'assets' or default folder 'wp-content/uploads'
 * @return string
 * @todo delete
 */
/*function wpstg_get_rel_upload_dir()
{
    // Get upload directory information. Default is ABSPATH . 'wp-content/uploads'
    // Can be customized by populating the db option upload_path or the constant UPLOADS
    // If both are defined WordPress will uses the value of the UPLOADS constant
    $uploads = wp_upload_dir();

    // Get absolute path to wordpress uploads directory e.g srv/www/htdocs/sitename/wp-content/uploads
    $uploadsAbsPath = trailingslashit($uploads['basedir']);

    // Get relative path to the uploads folder, e.g assets
    $relPath = str_replace(ABSPATH, null, $uploadsAbsPath);

    return $relPath;
}*/

/**
 * Get relative path to the uploads folder, can be a custom folder e.g assets or default folder wp-content/uploads
 *
 * @deprecated
 * @see         \WPStaging\Framework\Utils\WpDefaultDirectories::getUploadPath Removed in favor of this.
 * @todo        Remove this in future versions.
 *
 * @return string
 */
function wpstg_get_abs_upload_dir()
{
    return (new \WPStaging\Framework\Utils\WpDefaultDirectories())->getUploadPath();
}

/**
 * Get hostname of production site including scheme
 * @return string
 */
function wpstg_get_production_hostname()
{

    $connection = get_option('wpstg_connection');

    // Get the stored hostname
    if (!empty($connection['prodHostname'])) {
        return $connection['prodHostname'];
    }

    // Default. Try to get the hostname from the main domain (Workaround for WP Staging Pro older < 2.9.1)
    $siteurl = get_site_url();
    $result = parse_url($siteurl);
    return $result['scheme'] . "://" . $result['host'];
}

/**
 * Check if string starts with specific string
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function wpstg_starts_with($haystack, $needle)
{
    $length = strlen($needle);
    return ($needle === substr($haystack, 0, $length));
}

/**
 * Check if folder is empty
 * @param string $dir
 * @return boolean
 */
function wpstg_is_empty_dir($dir)
{
    if (!is_dir($dir)) {
        return true;
    }
    $iterator = new \FilesystemIterator($dir);
    if ($iterator->valid()) {
        return false;
    }
    return true;
}

/**
 * Get absolute WP uploads path e.g.
 * Multisites: /var/www/htdocs/example.com/wp-content/uploads/sites/1 or /var/www/htdocs/example.com/wp-content/blogs.dir/1/files
 * Single sites: /var/www/htdocs/example.com/wp-content/uploads
 * @return string
 */
function wpstg_get_upload_dir()
{
    $uploads = wp_upload_dir(null, false);

    $baseDir = wpstg_replace_windows_directory_separator($uploads['basedir']);

    // If multisite (and if not the main site in a post-MU network)
    if (is_multisite() && !(is_main_network() && is_main_site() && defined('MULTISITE'))) {
        // blogs.dir is used on WP 3.5 and earlier
        if (strpos($baseDir, 'blogs.dir') !== false) {
            // remove this piece from the basedir: /blogs.dir/2/files
            $uploadDir = wpstg_replace_first_match('/blogs.dir/' . get_current_blog_id() . '/files', null, $baseDir);
            $dir = wpstg_replace_windows_directory_separator($uploadDir . '/blogs.dir');
        } else {
            // remove this piece from the basedir: /sites/2
            $uploadDir = wpstg_replace_first_match('/sites/' . get_current_blog_id(), null, $baseDir);
            $dir = wpstg_replace_windows_directory_separator($uploadDir . '/sites');
        }


        return $dir;
    }
    return false;
}

/**
 * Get the base of a string
 * @param type $input
 * @return type
 */
function wpstg_base($input)
{
    $keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    $chr1 = $chr2 = $chr3 = "";
    $enc1 = $enc2 = $enc3 = $enc4 = "";
    $i = 0;
    $output = "";
    $input = preg_replace("[^A-Za-z0-9\+\/\=]", "", $input);
    do {
        $enc1 = strpos($keyStr, substr($input, $i++, 1));
        $enc2 = strpos($keyStr, substr($input, $i++, 1));
        $enc3 = strpos($keyStr, substr($input, $i++, 1));
        $enc4 = strpos($keyStr, substr($input, $i++, 1));
        $chr1 = ($enc1 << 2) | ($enc2 >> 4);
        $chr2 = (($enc2 & 15) << 4) | ($enc3 >> 2);
        $chr3 = (($enc3 & 3) << 6) | $enc4;
        $output = $output . chr((int)$chr1);
        if ($enc3 != 64) {
            $output = $output . chr((int)$chr2);
        }
        if ($enc4 != 64) {
            $output = $output . chr((int)$chr3);
        }
        $chr1 = $chr2 = $chr3 = "";
        $enc1 = $enc2 = $enc3 = $enc4 = "";
    } while ($i < strlen($input));
    return urldecode($output);
}

/**
 * Write data to file
 * An alternative function for file_put_contents which is disabled on some hosts
 *
 * @param string $file
 * @param string $contents
 * @param int | false $mode
 * @return boolean
 */
function wpstg_put_contents($file, $contents, $mode = false)
{
    $fp = @fopen($file, 'wb');

    if (!$fp) {
        return false;
    }

    mbstring_binary_safe_encoding();

    $data_length = strlen($contents);

    $bytes_written = fwrite($fp, $contents);

    reset_mbstring_encoding();

    fclose($fp);

    if ($data_length !== $bytes_written) {
        return false;
    }

    wpstg_chmod($file, $mode);

    return true;
}

/**
 * Change chmod of file or folder
 * @param string $file path to file
 * @param mixed $mode false or specific octal value like 0755
 * @param type $recursive
 * @return boolean
 */
function wpstg_chmod($file, $mode = false)
{
    if (!$mode) {
        if (@is_file($file)) {
            if (defined('FS_CHMOD_FILE')) {
                $mode = FS_CHMOD_FILE;
            } else {
                $mode = (int)0644;
            }
        } elseif (@is_dir($file)) {
            if (defined('FS_CHMOD_FILE')) {
                $mode = FS_CHMOD_DIR;
            } else {
                $mode = (int)0755;
            }
        } else {
            return false;
        }
    }

    if (!@is_dir($file)) {
        return @chmod($file, $mode);
    }

    return true;
}

/**
 * Create file if it does not exist
 *
 * @param string $path
 * @param (int|false) $chmod The permissions as octal number (or false to skip chmod)
 * @param (string|int) $chown A user name or number (or false to skip chown).
 * @return boolean true on success, false on failure.
 */
function wpstg_mkdir($path, $chmod = false, $chown = false)
{
    // Safe mode fails with a trailing slash under certain PHP versions.
    $path = untrailingslashit($path);
    if (empty($path)) {
        return false;
    }

    if (!$chmod) {
        $chmod = FS_CHMOD_DIR;
    }

    if (!@mkdir($path)) {
        return false;
    }
    wpstg_chmod($path, $chmod);

    if ($chown) {
        wpstg_chown($path, $chown);
    }

    return true;
}

/**
 * Changes the owner of a file or directory.
 *
 *
 * @param string $file Path to the file or directory.
 * @param string|int $owner A user name or number.
 * @param bool $recursive Optional. If set to true, changes file owner recursively.
 *                              Default false.
 * @return bool True on success, false on failure.
 */
function wpstg_chown($file, $owner)
{
    if (!@file_exists($file)) {
        return false;
    }

    if (!@is_dir($file)) {
        return @chown($file, $owner);
    }
    return true;
}

/*
 * Check if website is installed locally
 * @return boolean
 */
function wpstg_is_local()
{
    $localHostname = ['.local', '.test', 'localhost'];

    foreach ($localHostname as $hostname) {
        if (strpos(get_site_url(), $hostname) !== false) {
            return true;
        }
    }

    return false;
}
