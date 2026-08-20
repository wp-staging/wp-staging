<?php











use WPStaging\Framework\Utils\WpDefaultDirectories;









function wpstg_replace_windows_directory_separator($path)
{
    return preg_replace('/[\\\\]+/', '/', $path);
}







function wpstg_replace_first_match($needle, $replace, $haystack)
{
    $result = $haystack;
    $pos    = strpos($haystack, $needle);
    if ($pos !== false) {
        $result = substr_replace($haystack, $replace, $pos, strlen($needle));
    }

    return $result;
}







function wpstg_is_valid_date($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
 
    return $d && $date === $d->format($format);
}







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






function wpstg_unique_constraint($query)
{
 
    $query = preg_replace_callback("/CONSTRAINT\s`(\w+)`/", function () {
        return "CONSTRAINT `" . uniqid() . "`";
    }, $query);

    return $query;
}










function wpstg_get_abs_upload_dir()
{
    return (new WpDefaultDirectories())->getUploadsPath();
}







function wpstg_starts_with($haystack, $needle)
{
    $length = strlen($needle);
    return ($needle === substr($haystack, 0, $length));
}






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







function wpstg_get_upload_dir()
{
    $uploads = wp_upload_dir(null, false);

    $baseDir = wpstg_replace_windows_directory_separator($uploads['basedir']);

 
    if (is_multisite() && !(is_main_network() && is_main_site() && defined('MULTISITE'))) {
 
        if (strpos($baseDir, 'blogs.dir') !== false) {
 
            $uploadDir = wpstg_replace_first_match('/blogs.dir/' . get_current_blog_id() . '/files', null, $baseDir);
            $dir       = wpstg_replace_windows_directory_separator($uploadDir . '/blogs.dir');
        } else {
 
            $uploadDir = wpstg_replace_first_match('/sites/' . get_current_blog_id(), null, $baseDir);
            $dir       = wpstg_replace_windows_directory_separator($uploadDir . '/sites');
        }


        return $dir;
    }

    return false;
}







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
