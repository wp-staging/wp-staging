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

function wpstg_clone_page() {
	global $wpstg_options;
	?>
	<div id="wpstg-clonepage-wrapper">
		<ul id="wpstg-steps">
			<li class="wpstg-current-step"><span class="wpstg-step-num">1</span>Overview</li>
			<li><span class="wpstg-step-num">2</span>Scanning</li>
			<li><span class="wpstg-step-num">3</span>Cloning</li>
		</ul> <!-- #wpstg-steps -->
		<div id="wpstg-workflow">
			<?= wpstg_overview(); ?>
		</div> <!-- #wpstg-workflow -->
	</div> <!-- #wpstg-clonepage-wrapper -->
	<?php
}

// 1st step: Overview
function wpstg_overview() {
	global $wpstg_options;
	$existing_clones = isset($wpstg_options['existing_clones']) ? $wpstg_options['existing_clones'] : array();
	?>
	<a href="#" id="wpstg-new-clone" class="wpstg-next-step-link" data-action="scanning">New Clone</a>
	<div id="wpstg-existing-clones">
		<?php foreach ($existing_clones as $clone) : ?>
			<div class="wpstg-clone" id="<?= $clone; ?>">
				<?= $clone; ?>
			</div> <!-- .wpstg-clone -->
		<?php endforeach; ?>
	</div> <!-- #wpstg-existing-clones -->
	<?php
	wp_die();
}
add_action('wp_ajax_overview', 'wpstg_overview');

// 2nd step: Scanning
function wpstg_scanning() {
	global $wpdb;
	$tables = $wpdb->get_results("show table status like '" . $wpdb->prefix . "_%'");
	?>
	<label id="wpstg-clone-label" for="wpstg-new-clone">
		Clone ID
		<input type="text" id="wpstg-new-clone">
	</label>
	<div id="wpstg-scanning-db">
		<h3>DB</h3>
		<?php foreach ($tables as $table) : ?>
			<div class="wpstg-db-table">
				<label>
					<input type="checkbox" checked data-table="<?= $table->Name; ?>">
					<?= $table->Name; ?>
				</label>
				<span class="wpstg-table-info" style="color: #999;">
					Size: <?= $table->Data_length + $table->Index_length; ?> bytes
				</span>
			</div>
		<?php endforeach; ?>
	</div> <!-- #wpstg-scanning-db -->
	<a href="#" id="wpstg-start-cloning" class="wpstg-next-step-link" data-action="cloning">Start Cloning</a>
	<?php
	wp_die();
}
add_action('wp_ajax_scanning', 'wpstg_scanning');

//check cloneID
function wpstg_check_clone() {
	global $wpstg_options;
	$existing_clones = isset($wpstg_options['existing_clones']) ? $wpstg_options['existing_clones'] : array();
	$new_clone = $_POST['cloneID'];
	wp_die(! in_array($new_clone, $existing_clones));
}
add_action('wp_ajax_check_clone', 'wpstg_check_clone');

//3rd step: Cloning
function wpstg_cloning() {
	global $wpstg_options;
	//check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_options['current_clone'] = $_POST['cloneID'];
	update_option('wpstg_settings', $wpstg_options);
	$db_progress = isset($wpstg_options['db_progress']) ? $wpstg_options['db_progress'] : 0;
	?>
	<div class="wpstg-cloning-section">DB
		<div class="progress-bar">
			<div class="progress" id="wpstg-db-progress" style="width: <?= 100 * $db_progress; ?>%;"></div>
		</div>
	</div>
	<div class="wpstg-cloning-section">Files
		<div class="progress-bar">
			<div class="progress" id="wpstg-files-progress"></div>
		</div>
	</div>
	<div class="wpstg-cloning-section">Links
		<div class="progress-bar">
			<div class="progress" id="wpstg-links-progress"></div>
		</div>
	</div>
	<?php
	//wpstg_clone_db();
	wp_die();
}
add_action('wp_ajax_cloning', 'wpstg_cloning');

function wpstg_clone_db() {
	global $wpdb, $wpstg_options;

	$limit = isset($wpstg_options['query_limit']) ? $wpstg_options['query_limit'] : 100;
	$table = isset($wpstg_options['current_table']) ? $wpstg_options['current_table'] : null;
	$is_new = false;

	$msg = 0;//delete

	if ($table === null) {
		$tables = $wpdb->get_col("show tables like '" . $wpdb->prefix . "%'");
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

	$clone = $home . '/' . $wpstg_options['current_clone'];
	copy_r($home, $clone);

	unset($wpstg_options['current_file']);
	update_option('wpstg_settings', $wpstg_options);
	wp_die(1);
}
add_action('wp_ajax_copy_dir', 'wpstg_copy_dir');

function copy_r($source, $dest)
{
	global $skip, $copied_size, $wpstg_options;
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

	$skip_dirs = isset($wpstg_options['existing_clones']) ? $wpstg_options['existing_clones'] : array();
	$tmp = explode('/', $dest);
	$skip_dirs[] = array_pop($tmp);
	$skip_dirs[] = '.';
	$skip_dirs[] = '..';
	$dir = isset($dir) ? $dir : dir($source);
	while (false !== $entry = $dir->read()) {
		// Skip pointers
		if (in_array($entry, $skip_dirs)) {
			continue;
		}

		// Deep copy directories
		copy_r("$source/$entry", "$dest/$entry");
	}
	$dir->close();
	return true;
}

function wpstg_replace_links() {
	global $wpdb, $wpstg_options;
	$new_prefix = $wpstg_options['current_clone'] . '_' . $wpdb->prefix;
	//replace site url in options
	$wpdb->query('update ' . $new_prefix . 'options set option_value = \'' . get_home_url() . '/' . $wpstg_options['current_clone'] . '\' where option_name = \'siteurl\' or option_name = \'home\'');

	//replace table prefix in meta keys
	$wpdb->query('update ' . $new_prefix . 'usermeta set meta_key = replace(meta_key, \'' . $wpdb->prefix . '\', \'' . $new_prefix . '\') where meta_key like \'' . $wpdb->prefix . '_%\'');
	$wpdb->query('update ' . $new_prefix . 'options set option_name = replace(option_name, \'' . $wpdb->prefix . '\', \'' . $new_prefix . '\') where option_name like \'' . $wpdb->prefix . '_%\'');

	//replace $table_prefix in wp-config.php
	$config = file_get_contents(get_home_path() . '/' . $wpstg_options['current_clone'] . '/wp-config.php');
	$config = str_replace('$table_prefix', '$table_prefix = \'' . $new_prefix . '\';//', $config);
	file_put_contents(get_home_path() . '/' . $wpstg_options['current_clone'] . '/wp-config.php', $config);

	$wpstg_options['existing_clones'][] = $wpstg_options['current_clone'];
	unset($wpstg_options['current_clone']);
	update_option('wpstg_settings', $wpstg_options);

	wp_die();
}
add_action('wp_ajax_replace_links', 'wpstg_replace_links');