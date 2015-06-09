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
	global $wpstg_options;
	$clone = isset($wpstg_options['current_clone']) ? $wpstg_options['current_clone'] : null;
	$crushed_file = isset($wpstg_options['current_file']) ? $wpstg_options['current_file'] : null;
	?>
	<div id="wpstg_clonepage_wrapper">
		<input type="text" id="wpstg_clone_id" value="<?= $clone; ?>"><br>
		<a href="#" id="wpstg_clone_link">1) <?= $clone === null ? 'Clone DB ;)' : 'Continue cloning...';?></a>
		<span id="wpstg_cloning_status"></span><br>
		<a href="#" id="wpstg_copy_dir">2) <?= $crushed_file === null ? 'Copy files' : 'Continue coping...';?></a>
		<span id="wpstg_coping_status"></span>
		<hr>
		<a href="#" id="wpstg_replace">3) Replace DB entries and table prefix</a>
	</div> <!-- #wpstg_clonepage_wrapper -->
<?php
}

function wpstg_clone_db() {
	global $wpdb, $wpstg_options;

	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

	if (!isset($wpstg_options['current_clone']))
		$wpstg_options['current_clone'] = $_POST['wpstg_clone_id'];
	$limit = isset($wpstg_options['query_limit']) ? $wpstg_options['query_limit'] : 100; //change to normal value (100)
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
			unset($wpstg_options['current_clone']);
			update_option('wpstg_settings', $wpstg_options);
			wp_die(1);
		}
		$table = reset($tables);
		$is_new = true;
	}

	$new_table = $wpstg_options['current_clone'] . '_' . $table;
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
	global $wpstg_options, $skip, $copied_size;
	$home = rtrim(get_home_path(), '/');
	$copied_size = 0;
	$cur_file = isset($wpstg_options['current_file']) ? $wpstg_options['current_file'] : null;
	if ($cur_file !== null) {
		$tmp = substr($cur_file, strlen($home));
		$skip = explode('/', trim($tmp, '/'));
	}

	$clone = $home . '/' . $_POST['wpstg_clone_id'];
	copy_r($home, $clone);

	unset($wpstg_options['current_file']);
	update_option('wpstg_settings', $wpstg_options);
	wp_die(1);
}
add_action('wp_ajax_copy_dir', 'wpstg_copy_dir');

function copy_r($source, $dest)
{
	global $skip, $copied_size, $wpstg_options, $out;
	clearstatcache();
	$batch_size = isset($wpstg_options['wpstg_batch_size']) ? $wpstg_options['wpstg_batch_size'] : 20;
	$batch_size *= 1024*1024;

	//Skip already copied files and folders
	if (!empty($skip)) {
		if (is_dir($source)) {
			$dir = dir($source);
			while (false !== $entry = $dir->read())
				if ($entry == $skip[0]) {
					array_shift($skip);
					copy_r("$source/$entry", "$dest/$entry");
					break;
				}
		}
	}

	if (is_file($source)) {
		$size = filesize($source);
		if ($batch_size > $copied_size + $size) {
			$copied_size += $size;
			return copy($source, $dest);
		} else {
			$wpstg_options['current_file'] = $source;
			update_option('wpstg_settings', $wpstg_options);
			wp_die(0);
		}
	}

	if (!is_dir($dest))
		mkdir($dest);

	$tmp = explode('/', $dest);
	$dir_name = array_pop($tmp);
	$dir = isset($dir) ? $dir : dir($source);
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

function wpstg_replace_links() {
	global $wpdb;
	$new_prefix = $_POST['wpstg_clone_id'] . '_' . $wpdb->prefix;
	//replace site url in options
	$wpdb->query('update ' . $new_prefix . 'options set option_value = \'' . get_home_url() . '/' . $_POST['wpstg_clone_id'] . '\' where option_name = \'siteurl\' or option_name = \'home\'');

	//replace table prefix in meta keys
	$wpdb->query('update ' . $new_prefix . 'usermeta set meta_key = replace(meta_key, \'' . $wpdb->prefix . '\', \'' . $new_prefix . '\') where meta_key like \'' . $wpdb->prefix . '_%\'');
	$wpdb->query('update ' . $new_prefix . 'options set option_name = replace(option_name, \'' . $wpdb->prefix . '\', \'' . $new_prefix . '\') where option_name like \'' . $wpdb->prefix . '_%\'');

	//replace $table_prefix in wp-config.php
	$config = file_get_contents(get_home_path() . '/' . $_POST['wpstg_clone_id'] . '/wp-config.php');
	$config = str_replace('$table_prefix', '$table_prefix = \'' . $new_prefix . 's\';//', $config);
	file_put_contents(get_home_path() . '/' . $_POST['wpstg_clone_id'] . '/wp-config.php', $config);
	wp_die();
}
add_action('wp_ajax_replace_links', 'wpstg_replace_links');