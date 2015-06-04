<?php
/**
 * Template Functions
 *
 * @package     WPSTG
 * @subpackage  Functions/Templates
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

//Clone Page
function wpstg_clone_page() {
	?>
	<div id="wpstg_clonepage_wrapper">
		<input type="text" id="wpstg_clone_id">
		<a href="#" id="wpstg_clone_link">GOGO POWER RANGERS!</a>
		<span id="wpstg_cloning_status"></span><br>
		<a href="#" id="wpstg_copy_dir">COPY DIR (test)</a>
		<span id="wpstg_coping_status"></span>
	</div> <!-- #wpstg_clonepage_wrapper -->
	<?php
}

function wpstg_clone_db() {
	global $wpdb, $wpstg_options;
        
        check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
        
	$clone_id = $_POST['wpstg_clone_id'];
	$query_limit = isset($wpstg_options['wpstg_query_limit']) ? $wpstg_options['wpstg_query_limit'] : 2;
	//check clone id
	if (!wpstg_check_clone_id($clone_id)) {
		$msg = 'Error: This clone(ID: ' . $clone_id . ') is already exists.';
		wp_die($msg);
	}

	$tables = $wpdb->get_col("show tables like '" . $wpdb->prefix . "_%'");
	foreach ($tables as $table) {
		$new_table = $clone_id . '_' . $table;
		$table_status = $wpdb->query('create table ' . $new_table . ' like ' . $table);
		if ($table_status !== false) {
			$offset = isset($wpstg_options['wpstg_limits'][$table]) ? $wpstg_options['wpstg_limits'][$table] : 0;
			$rows_status = $wpdb->query('insert ' . $new_table . ' select * from ' . $table . ' limit ' . $offset . ', ' . $query_limit);
			if ($rows_status !== false) {
				$wpstg_options['wpstg_limits'][$table] = $offset + $query_limit;
				update_option('wpstg_settings', $wpstg_options);
			} else {
				$msg = 'Error: Coping rows from ' . $table . ' crushed.';
				wp_die($msg);
			}
		} else {
			//save crushed table
			$msg = 'Error: Table ' . $table . ' crushed.';
			wp_die($msg);
		}
	}
	wp_die(0);
}
add_action('wp_ajax_wpstg_clone_db', 'wpstg_clone_db');

function wpstg_check_clone_id($clone_id) {
	global $wpstg_options;

        check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
        
	if (empty($wpstg_options['exists_clones']) || !in_array($clone_id, $wpstg_options['exists_clones'])) {
		$wpstg_options['exists_clones'][] = $clone_id;
		update_option('wpstg_settings', $wpstg_options);
		return true;
	}
	return false;
}

//tmp
function wpstg_copy_dir() {
	$home = get_home_path();
	$dest = $home . $_POST['wpstg_clone_id'];
	copy_r($home, $dest);

	wp_die();
}
add_action('wp_ajax_copy_dir', 'wpstg_copy_dir');

function copy_r($source, $dest)
{
	if (is_file($source)) {
		return copy($source, $dest);
	}

	if (!is_dir($dest)) {
		mkdir($dest);
	}

	$tmp = explode('/', $dest);
	$dir_name = array_pop($tmp);
	$dir = dir($source);
	while (false !== $entry = $dir->read()) {
		// Skip pointers
		if ($entry == '.' || $entry == '..' || $entry == $dir_name) {
			continue;
		}

		// Deep copy directories
		copy_r("$source/$entry", "$dest/$entry");
	}

	$dir->close();
	return true;
}