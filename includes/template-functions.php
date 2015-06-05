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
		<input type="text" id="wpstg_clone_id"><br>
		<a href="#" id="wpstg_clone_link">Clone DB ;)</a>
		<span id="wpstg_cloning_status"></span><br>
		<a href="#" id="wpstg_copy_dir">Copy files (better don't click)</a>
		<span id="wpstg_coping_status"></span>
	</div> <!-- #wpstg_clonepage_wrapper -->
	<?php
}

function wpstg_clone_db() {
	global $wpdb, $wpstg_options;

	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

	$clone_id = $_POST['wpstg_clone_id'];
	$limit = isset($wpstg_options['query_limit']) ? $wpstg_options['query_limit'] : 2; //change to normal value (100)
	$table = isset($wpstg_options['current_table']) ? $wpstg_options['current_table'] : null;
	$is_new = false;

	$msg = 0;

	//check clone id
	if ($table === null) {
		$tables = $wpdb->get_col("show tables like '" . $wpdb->prefix . "_%'");
		$cloned_tables = empty($wpstg_options['cloned_tables']) ? array() : $wpstg_options['cloned_tables']; //already cloned tables
		$tables = array_diff($tables, $cloned_tables);
		if (empty($tables)) { //exit condition
			unset($wpstg_options['cloned_tables']);
			unset($wpstg_options['offsets']);
			update_option('wpstg_settings', $wpstg_options);
			wp_die(1);
		}
		$table = reset($tables);
		$is_new = true;
	}

	$new_table = $clone_id . '_' . $table;
	$offset = isset($wpstg_options['offsets'][$table]) ? $wpstg_options['offsets'][$table] : 0;
	$is_cloned = true;
	if ($is_new) {
		$is_cloned = $wpdb->query("create table $new_table like $table");
		$wpstg_options['current_table'] = $table;
	}
	if ($is_cloned) {
		$inserted_rows = $wpdb->query("insert $new_table select * from  $table limit $offset, $limit");
		if ($inserted_rows !== false) {
			$wpstg_options['offsets'][$table] = $offset + $limit;
			if ($inserted_rows < $limit) {
				$wpstg_options['cloned_tables'][] = $table;
				unset($wpstg_options['current_table']);
			}
		} else {
			$msg = 'Error: Table ' . $table . ' has been crushed. Offset: ' . $offset;
		}
	} else {
		$msg = 'Error: Table ' . $table . ' has been crushed.';
	}
	update_option('wpstg_settings', $wpstg_options);
	wp_die($msg);
}
add_action('wp_ajax_wpstg_clone_db', 'wpstg_clone_db');

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