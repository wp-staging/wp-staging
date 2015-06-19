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
	?>
	<div id="wpstg-clonepage-wrapper">
		<ul id="wpstg-steps">
			<li class="wpstg-current-step"><span class="wpstg-step-num">1</span>Overview</li>
			<li><span class="wpstg-step-num">2</span>Scanning</li>
			<li><span class="wpstg-step-num">3</span>Cloning</li>
		</ul> <!-- #wpstg-steps -->
		<div id="wpstg-workflow">
			<?php echo wpstg_overview(); ?>
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
		<?php if (!empty($existing_clones)) : ?>
			<h3>Existing clones:</h3>
			<?php foreach ($existing_clones as $clone) : ?>
				<div class="wpstg-clone" id="<?php echo $clone; ?>">
					<?php echo $clone; ?>
					<a href="#" class="wpstg-remove-clone" data-clone="<?php echo $clone; ?>">remove</a>
				</div> <!-- .wpstg-clone -->
			<?php endforeach; ?>
		<?php endif; ?>
	</div> <!-- #wpstg-existing-clones -->
	<?php
	wp_die();
}
add_action('wp_ajax_overview', 'wpstg_overview');

// 2nd step: Scanning
function wpstg_scanning() {
	global $wpdb, $wpstg_options, $all_files;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );

	//Scan DB
	$tables = $wpdb->get_results("show table status like '" . $wpdb->prefix . "_%'");
	$wpstg_options['all_tables'] = $wpdb->get_col("show tables like '" . $wpdb->prefix . "%'");

	//Scan Files
	$folders = wpstg_scan_files(get_home_path());
	$path = __DIR__ . '/remaining_files.json';
	file_put_contents($path, json_encode($all_files));

	update_option('wpstg_settings', $wpstg_options);
	$clone_id = '';
	if (isset($wpstg_options['current_clone']))
		$clone_id = 'value="' . $wpstg_options['current_clone'] . '" disabled';
	?>
	<label id="wpstg-clone-label" for="wpstg-new-clone">
		Clone ID
		<input type="text" id="wpstg-new-clone" <?php echo $clone_id; ?>>
		<span class="wpstg-error-msg"></span>
	</label>
	<a href="#" id="wpstg-start-cloning" class="wpstg-next-step-link" data-action="cloning">Start Cloning</a>

	<div class="wpstg-tabs-wrapper">
		<a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db">DB</a>
		<a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files">Files</a>
		<div class="wpstg-tab-section" id="wpstg-scanning-db">
			<?php wpstg_show_tables($tables); ?>
		</div> <!-- #wpstg-scanning-db -->
		<div class="wpstg-tab-section" id="wpstg-scanning-files">
			<?php wpstg_directory_strucrure($folders); ?>
		</div> <!-- #wpstg-scanning-files -->
	</div>
	<a href="#" class="wpstg-prev-step-link">Back</a>
	<?php
	wp_die();
}
add_action('wp_ajax_scanning', 'wpstg_scanning');

//Display db tables
function wpstg_show_tables($tables) {
	global $wpstg_options;
	$cloned_tables = isset($wpstg_options['cloned_tables']) ? $wpstg_options['cloned_tables'] : array();

	foreach ($tables as $table) { ?>
		<div class="wpstg-db-table">
			<label>
				<input type="checkbox" checked name="<?php echo $table->Name; ?>" <?php echo in_array($table->Name, $cloned_tables) ? 'disabled' : ''; ?>>
				<?php echo $table->Name; ?>
			</label>
			<span class="wpstg-table-info">
				Size: <?php echo $table->Data_length + $table->Index_length; ?> bytes
			</span>
		</div>
	<?php }
}

//Scan all files and shape directory structure
function wpstg_scan_files($path, &$folders = array()) {
	global $all_files;

	if (is_dir($path)) {
		$dir = dir($path);
		while (false !== $entry = $dir->read()) {
			if ($entry == '.' || $entry == '..')
				continue;
			if (is_file("$path/$entry")) {
				$all_files[] = "$path/$entry";
				continue;
			}
			wpstg_scan_files("$path/$entry", $folders[$entry]);
		}
	}
	return $folders;
}

//Display directory structure
function wpstg_directory_strucrure($folders, $path = null) {
	$path = $path === null ? rtrim(get_home_path(), '/') : $path;
	foreach ($folders as $name => $folder) { ?>
		<div class="wpstg-dir">
			<input type="checkbox" class="wpstg-check-dir" checked name="<?php echo "$path/$name"; ?>">
			<a href="#" class="wpstg-expand-dirs"><?php echo $name;?></a>
			<div class="wpstg-dir wpstg-subdir">
				<?php
					if (!empty ($folder))
						wpstg_directory_strucrure($folder, "$path/$name");
				?>
			</div>
		</div>
	<?php }
}

//Check cloneID
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
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_options['current_clone'] = isset($wpstg_options['current_clone']) ? $wpstg_options['current_clone'] : $_POST['cloneID'];

	$wpstg_options['cloned_tables'] = isset($wpstg_options['cloned_tables']) ? $wpstg_options['cloned_tables'] : array();
	if (isset($_POST['uncheckedTables']))
		$wpstg_options['cloned_tables'] = array_merge($wpstg_options['cloned_tables'], $_POST['uncheckedTables']);

	$wpstg_options['excluded_folders'] = isset($wpstg_options['excluded_folders']) ? $wpstg_options['excluded_folders'] : array();
	if (isset($_POST['excludedFolders'])) {
		$path = __DIR__ . '/remaining_files.json';
		$all_files = json_decode(file_get_contents($path));
		$excluded_files = array();
		foreach ($wpstg_options['excluded_folders'] as $folder) {
			$dir = dir($folder);
			while (false !== $entry = $dir->read())
				$excluded_files[] = $folder . '/' . $entry;
		}
		$remaining_files = array_dif($all_files, $excluded_files);
		file_put_contents($path, json_encode($remaining_files));
	}


	$wpstg_options['db_progress'] = isset($wpstg_options['db_progress']) ? $wpstg_options['db_progress'] : 0;
	$wpstg_options['files_progress'] = isset($wpstg_options['files_progress']) ? $wpstg_options['files_progress'] : 0;
	$wpstg_options['links_progress'] = isset($wpstg_options['links_progress']) ? $wpstg_options['links_progress'] : 0;

	update_option('wpstg_settings', $wpstg_options);
	?>
	<div class="wpstg-cloning-section">DB
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-db-progress" style="width: <?php echo 100 * $wpstg_options['db_progress']; ?>%;"></div>
		</div>
	</div>
	<div class="wpstg-cloning-section">Files
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-files-progress" style="width: <?php echo 100 * $wpstg_options['files_progress']; ?>%;"></div>
		</div>
	</div>
	<div class="wpstg-cloning-section">Links
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-links-progress" style="width: <?php echo 100 * $wpstg_options['links_progress']; ?>%"></div>
		</div>
	</div>
	<span id="wpstg-cloning-result"></span>
	<?php
	wp_die();
}
add_action('wp_ajax_cloning', 'wpstg_cloning');

function wpstg_clone_db() {
	global $wpdb, $wpstg_options;

	$progress = isset($wpstg_options['db_progress']) ? $wpstg_options['db_progress'] : 0;
	if ($progress >= 1)
		wp_die(1);

	$limit = isset($wpstg_options['wpstg_query_limit']) ? $wpstg_options['wpstg_query_limit'] : 1000;
	$table = isset($wpstg_options['current_table']) ? $wpstg_options['current_table'] : null;
	$is_new = false;

	if ($table === null) {
		$tables = $wpstg_options['all_tables'];
		$cloned_tables = !empty($wpstg_options['cloned_tables']) ? $wpstg_options['cloned_tables'] : array(); //already cloned tables
		$tables = array_diff($tables, $cloned_tables);
		if (empty($tables)) //exit condition
			wp_die(1);
		$table = reset($tables);
		$is_new = true;
		WPSTG()->logger->info('Start cloning table ' . $table);
	} else
		WPSTG()->logger->info('Continue cloning table ' . $table);

	$new_table = $wpstg_options['current_clone'] . '_' . $table;
	$offset = isset($wpstg_options['offsets'][$table]) ? $wpstg_options['offsets'][$table] : 0;
	$is_cloned = true;

	if ($is_new) {
		$is_cloned = $wpdb->query(
			"create table $new_table like $table"
		);
		$wpstg_options['current_table'] = $table;
	}
	if ($is_cloned) {
		$inserted_rows = $wpdb->query(
			"insert $new_table select * from $table limit $offset, $limit"
		);
		if ($inserted_rows !== false) {
			$wpstg_options['offsets'][$table] = $offset + $limit;
			if ($inserted_rows < $limit) {
				$wpstg_options['cloned_tables'][] = $table;
				unset($wpstg_options['current_table']);

				$all_tables_count = count($wpstg_options['all_tables']);
				$cloned_tables_count = count($wpstg_options['cloned_tables']);
				$wpstg_options['db_progress'] = round($cloned_tables_count / $all_tables_count, 2);
				$progress = $wpstg_options['db_progress'];
			}
		} else {
			WPSTG()->logger->info('Table ' . $table . ' has beed created, BUT inserting rows falied. Offset: ' . $offset);
			wp_die(-1);
		}
	} else {
		WPSTG()->logger->info('Creating table ' . $table . ' has been failed.');
		wp_die(-1);
	}
	update_option('wpstg_settings', $wpstg_options);
	wp_die($progress);
}
add_action('wp_ajax_wpstg_clone_db', 'wpstg_clone_db');

function wpstg_copy_files() {
	global $wpstg_options;

	if (isset($wpstg_options['files_progress']) && $wpstg_options['files_progress'] >= 1)
		wp_die(1);

	$clone = get_home_path() . $wpstg_options['current_clone'];
	$path = __DIR__ . '/remaining_files.json';
	$files = json_decode(file_get_contents($path));
	$start_index = isset($wpstg_options['file_index']) ? $wpstg_options['file_index'] : 0;
	$batch_size = isset($wpstg_options['wpstg_batch_size']) ? $wpstg_options['wpstg_batch_size'] : 20;
	$batch_size *= 1024*1024;

	if (!is_dir($clone))
		mkdir($clone);

	for ($i = $start_index; $i < count($files); $i++) {
		$size = filesize($files[$i]);
		if ($size > $batch_size) {

		}
	}
}
//add_action('wp_ajax_copy_files', 'wpstg_copy_files');


function wpstg_copy_dir() {
	global $wpstg_options, $skip, $copied_size;

	if (isset($wpstg_options['files_progress']) && $wpstg_options['files_progress'] >= 1)
		wp_die(1);

	$home = rtrim(get_home_path(), '/');
	$copied_size = 0;
	$cur_file = isset($wpstg_options['current_file']) ? $wpstg_options['current_file'] : null;

	if ($cur_file !== null) {
		$tmp = substr($cur_file, strlen($home));
		$skip = explode('/', trim($tmp, '/'));
		WPSTG()->logger->info('Continue coping files. Current file: ' . $cur_file);
	} else
		WPSTG()->logger->info('Start coping files.');

	$clone = $home . '/' . $wpstg_options['current_clone'];
	copy_r($home, $clone);

	WPSTG()->logger->info('Coping files has been completed successfully.');
	$wpstg_options['files_progress'] = 1;
	update_option('wpstg_settings', $wpstg_options);
	wp_die(1);
}
add_action('wp_ajax_copy_dir', 'wpstg_copy_dir');

function copy_r($source, $dest) {
	global $wpstg_options, $skip, $copied_size;
	clearstatcache();

	$batch_size = isset($wpstg_options['wpstg_batch_size']) ? $wpstg_options['wpstg_batch_size'] : 20;
	$batch_size *= 1024*1024;

	//Skip already copied files and folders
	if (! empty($skip)) {
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
		if ($size > $batch_size) {
			WPSTG()->logger->info('START coping large file: ' . $source);
			$fin = fopen($source, 'rb');
			$fout = fopen($dest, 'w');
			while (! feof($fin))
				if (false === fwrite($fout, fread($fin, $batch_size))) {
					WPSTG()->logger->info('Coping large file FAILED: ' . $source);
					$wpstg_options['current_file'] = $source;
					update_option('wpstg_settings', $wpstg_options);
					fclose($fin);
					fclose($fout);
					wp_die(0);
				}
			fclose($fin);
			fclose($fout);
			$copied_size += $size;
			WPSTG()->logger->info('Large file has been COPIED: ' . $source);
			return true;
		}
		if ($batch_size > $copied_size + $size) {
			if (copy($source, $dest)) {
				$copied_size += $size;
				return true;
			} else {
				WPSTG()->logger->info('Coping failed: ' . $source);
				$wpstg_options['current_file'] = $source;
				update_option('wpstg_settings', $wpstg_options);
				wp_die(0);
			}
		} else {
			$wpstg_options['current_file'] = $source;
			update_option('wpstg_settings', $wpstg_options);
			WPSTG()->logger->info('Batch complete: ' . $copied_size . '; Current File: ' . $source);
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
		if (in_array($entry, $skip_dirs))
			continue;

		// Deep copy directories
		copy_r("$source/$entry", "$dest/$entry");
	}
	$dir->close();
	return true;
}

function wpstg_replace_links() {
	global $wpdb, $wpstg_options;

	$new_prefix = $wpstg_options['current_clone'] . '_' . $wpdb->prefix;
	$wpstg_options['links_progress'] = isset($wpstg_options['links_progress']) ? $wpstg_options['links_progress'] : 0;
	//replace site url in options
	if ($wpstg_options['links_progress'] < 1) {
		$result = $wpdb->query(
			$wpdb->prepare(
				'update ' . $new_prefix . 'options set option_value = %s where option_name = \'siteurl\' or option_name = \'home\'',
				get_home_url() . '/' . $wpstg_options['current_clone']
			)
		);
		if (!$result) {
			WPSTG()->logger->info('Replacing site url has been failed.');
			wp_die(1);
		} else {
			$wpstg_options['links_progress'] = 33;
			update_option('wpstg_settings', $wpstg_options);
		}
	}

	//replace table prefix in meta keys
	if ($wpstg_options['links_progress'] < 50) {
		$result_options = $wpdb->query(
			$wpdb->prepare(
				'update ' . $new_prefix . 'usermeta set meta_key = replace(meta_key, %s, %s) where meta_key like %s',
				$wpdb->prefix,
				$new_prefix,
				$wpdb->prefix . '_%'
			)
		);
		$result_usermeta = $wpdb->query(
			$wpdb->prepare(
				'update ' . $new_prefix . 'options set option_name = replace(option_name, %s, %s) where option_name like %s',
				$wpdb->prefix,
				$new_prefix,
				$wpdb->prefix . '_%'
			)
		);
		if (!$result_options || !$result_usermeta) {
			WPSTG()->logger->info('Replacing table prefix has been failed.');
			wp_die(33);
		} else {
			$wpstg_options['links_progress'] = 66;
			update_option('wpstg_settings', $wpstg_options);
		}
	}

	//replace $table_prefix in wp-config.php
	if ($wpstg_options['links_progress'] < 100) {
		$config = file_get_contents(get_home_path() . '/' . $wpstg_options['current_clone'] . '/wp-config.php');
		if ($config) {
			$config = str_replace('$table_prefix', '$table_prefix = \'' . $new_prefix . '\';//', $config);
			file_put_contents(get_home_path() . '/' . $wpstg_options['current_clone'] . '/wp-config.php', $config);
		} else {
			WPSTG()->logger->info('Editing wp-config.php has been failed.');
			wp_die(66);
		}
	}

	$wpstg_options['existing_clones'][] = $wpstg_options['current_clone'];
	wpstg_clear_options();

	wp_die(0);
}
add_action('wp_ajax_replace_links', 'wpstg_replace_links');

function wpstg_clear_options() {
	global $wpstg_options;

	unset($wpstg_options['current_clone']);
	unset($wpstg_options['all_tables']);
	unset($wpstg_options['cloned_tables']);
	unset($wpstg_options['offsets']);
	unset($wpstg_options['db_progress']);
	unset($wpstg_options['current_file']);
	unset($wpstg_options['files_progress']);
	unset($wpstg_options['links_progress']);
	update_option('wpstg_settings', $wpstg_options);
}

function get_wp_size($path, $is_root = false) {
	global $wpstg_options;
	if (! file_exists($path)) return 0;
	if (is_file($path)) {
		$fsize = filesize($path);
		if ($is_root) {
			$batch_size = isset($wpstg_options['wpstg_batch_size']) ? $wpstg_options['wpstg_batch_size'] : 20;
			$batch_size *= 1024 * 1024;
			if ($fsize > $batch_size)
				$wpstg_options['big_files'][] = $path;
		}
		return $fsize;
	}
	$size = 0;
	foreach(glob($path . '/*') as $fn) {
		$skip_dirs = isset($wpstg_options['existing_clones']) ? $wpstg_options['existing_clones'] : array();
		$check = explode('/', $fn);
		if (in_array(array_pop($check), $skip_dirs))
			continue;
		$size += get_wp_size($fn, $is_root);
	}
	return $size;
}

function deleteDirectory($dir) {
	if (!file_exists($dir))
		return true;
	if (!is_dir($dir))
		return unlink($dir);

	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..')
			continue;

		if (!deleteDirectory($dir . '/' . $item))
			return false;

	}

	return rmdir($dir);
}

function wpstg_check_files_progress() {
	global $wpstg_options;
	$clone_size = get_wp_size(get_home_path() . $wpstg_options['current_clone']);
	wp_die(round($clone_size / $wpstg_options['total_wp_size'], 2));
}
add_action('wp_ajax_check_files_progress', 'wpstg_check_files_progress');

function wpstg_delete_clone() {
	global $wpdb, $wpstg_options;
	$clone = $_POST['cloneID'];

	WPSTG()->logger->info('Removing clone( ' . $clone . ' ) has been started.');
	//drop clone tables
	$tables = $wpdb->get_col('show tables like \'' . $clone . '_%\'');
	foreach ($tables as $table) {
		$result = $wpdb->query('drop table ' . $table);
		if (! $result)
			WPSTG()->logger->info('Droping table ' . $table . ' has been failed.');
	}

	//remove clone folder
	$result = deleteDirectory(get_home_path() . $clone);
	if (! $result)
		WPSTG()->logger->info('Removing clone folder has been failed.');

	$key = array_search($clone, $wpstg_options['existing_clones']);
	unset($wpstg_options['existing_clones'][$key]);
	update_option('wpstg_settings', $wpstg_options);
	WPSTG()->logger->info('Clone( ' . $clone . ' ) has been removed successfully.');
	wp_die(0);
}
add_action('wp_ajax_delete_clone', 'wpstg_delete_clone');

//tmp
function getDirStructure($directory, &$array = array()) {
	global $wpstg_options;
	$clone_dirs = isset($wpstg_options['existing_clones']) ? $wpstg_options['existing_clones'] : array();
	$clone_dirs[] = '.';
	$clone_dirs[] = '..';
	if (is_file($directory))
		return true;
	$dir = dir($directory);
	while (false !== $entry = $dir->read()) {
		if (in_array($entry, $clone_dirs))
			continue;
		if (is_file("$directory/$entry")) {
			$array[] = $entry;
			getDirStructure("$directory/$entry", $array);
		} else
			getDirStructure("$directory/$entry", $array[$entry]);
	}
	return $array;
}

function showDirStructure($structure) {
	foreach ($structure as $folder => $children) {
		if (is_array($children)) : ?>
			<div class="wpstg-fs-folder">
				<a href="#" class="wpstg-expand-folder">
					<span class="wpstg-plus-minus">+</span>
					<?php echo $folder; ?>
				</a>
				<div class="wpstg-fs-children">
					<?php showDirStructure($children); ?>
				</div>
			</div> <!-- .wpstg-fs-folder -->
		<?php else : ?>
			<span class="wpstg-fs-file"><?php echo $children; ?></span>
		<?php endif;
	}
}