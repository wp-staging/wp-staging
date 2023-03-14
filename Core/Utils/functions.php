<?php

/**
 * Globally applicable very tiny functions that have only one specific use case but that are needed more than one time.
 * We use snake case prefix 'wpstg_' to differentiate them with the rest of our code base
 * Yeah, evil in terms of some best "dogmatic" practices and made by laziness... but effective.
 * As they are prefixed we can easily find and refactor them over time.
 *
 * @todo refactor! Split this file into classes for strings, database, filesystem and so on. Move everything under /Frameworks
 *
 */

use WPStaging\Framework\Utils\WpDefaultDirectories;

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
 * @param mixed string|array
 * @return mixed string|array
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
                $array[] = is_string($string) ? urldecode($string) : $string;
        }
        return $array;
    }

    return $data;
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
 * Get relative path to the uploads folder, can be a custom folder e.g assets or default folder wp-content/uploads
 *
 * @return string
 *@see         \WPStaging\Framework\Utils\WpDefaultDirectories::getUploadsPath Removed in favor of this.
 * @todo        Remove this in future versions.
 *
 * @deprecated
 */
function wpstg_get_abs_upload_dir()
{
    return (new WpDefaultDirectories())->getUploadsPath();
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
    $iterator = new FilesystemIterator($dir);
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
 * @param string $input
 * @return string
 */
function wpstg_base($input)
{
    $keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
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

/*
 * Check if website is installed locally
 * @return boolean
 */
function wpstg_is_local()
{
    $localHostname = ['.local', '.test', 'localhost', '.dev', '10.0.0.', '172.16.0.', '192.168.0.'];

    $siteUrl = get_site_url();

    foreach ($localHostname as $hostname) {
        if (strpos($siteUrl, $hostname) !== false) {
            return true;
        }
    }

    return false;
}
