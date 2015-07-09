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

/**
 * Main Page
 *
 * Renders the main WP-Staging page contents.
 *
 * @since 1.0
 * @return void
*/

function wpstg_clone_page() {
	ob_start();
	?>
	<div id="wpstg-clonepage-wrapper">
			<h1 class="wp-staginglogo"> <?php echo __('Welcome to WP-Staging ', 'wpstg') . WPSTG_VERSION; ?></h1>
			<div class="wpstg-header">
				<?php echo __('Thank you for updating to the latest version!', 'wpstg');?>
				<br>
				<?php echo __('WP-Staging is ready to create a staging website for you!', 'wpstg'); ?>
				<br>
				<iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fwp-staging.com&amp;width=100&amp;layout=standard&amp;action=like&amp;show_faces=false&amp;share=true&amp;height=35&amp;appId=449277011881884" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:300px; height:20px;" allowTransparency="true"></iframe>
				<a class="twitter-follow-button" href="https://twitter.com/wp_staging" data-size="small" id="twitter-wjs">Follow @wp_staging</a>
			</div>
			<?php do_action('wpstg_notifications');?>
			<?php if (is_multisite()) {
				echo '<span class="wpstg-notice-alert" style="margin-top:20px;">' . __('Multisite is currently not supported! <a href="https://wp-staging.com/contact">Get in contact with us</a> and ask for it.', 'wpstg') . '</span>'; 
				exit;
			}?>
		<ul id="wpstg-steps">
			<li class="wpstg-current-step"><span class="wpstg-step-num">1</span><?php echo __('Overview', 'wpstg');?></li>
			<li><span class="wpstg-step-num">2</span><?php echo __('Scanning', 'wpstg');?></li>
			<li><span class="wpstg-step-num">3</span><?php echo __('Cloning', 'wpstg');?></li>
						<li><span href="#" id="wpstg-loader" style="display:none;"></span></li>
		</ul> <!-- #wpstg-steps -->
		<div id="wpstg-workflow">
			<?php echo wpstg_overview(false); ?>
		</div> <!-- #wpstg-workflow -->
	</div> <!-- #wpstg-clonepage-wrapper -->
	<?php
		echo ob_get_clean();
}

/**
 * 1st step: Overview
 * Renders the overview page content
 * 
 * @global type $wpstg_clone_details
 */
function wpstg_overview() {
	global $wpstg_clone_details;
	$wpstg_clone_details = wpstg_get_options();

	$existing_clones = get_option('wpstg_existing_clones', array());
	?>
	<?php if (isset($wpstg_clone_details['current_clone'])) : ?>
		Current clone: <?php echo $wpstg_clone_details['current_clone']; ?>
		<a href="#" id="wpstg-reset-clone" class="wpstg-link-btn" data-clone="<?php echo $wpstg_clone_details['current_clone']; ?>">Reset</a>
		<a href="#" class="wpstg-next-step-link wpstg-link-btn" data-action="scanning">Continue</a>
	<?php else : ?>
		<a href="#" id="wpstg-new-clone" class="wpstg-next-step-link wpstg-link-btn" data-action="scanning">New Staging Site</a>
	<?php endif; ?>
	<br>
	<div id="wpstg-existing-clones">
		<?php if (!empty($existing_clones)) : ?>
			<h3>Available Staging Sites:</h3>
			<?php foreach ($existing_clones as $clone) : ?>
				<div class="wpstg-clone" id="<?php echo $clone; ?>">
					<a href="<?php echo get_home_url() . "/$clone/wp-admin"; ?>" target="_blank"><?php echo $clone; ?></a>
					<a href="#" class="wpstg-remove-clone" data-clone="<?php echo $clone; ?>">&times;</a>
				</div> <!-- .wpstg-clone -->
			<?php endforeach; ?>
		<?php endif; ?>
	</div> <!-- #wpstg-existing-clones -->
	<div id="wpstg-removing-clone">

	</div> <!-- #wpstg-removing-clone -->
	<?php
	if (check_ajax_referer('wpstg_ajax_nonce', 'nonce', false))
		wp_die();
}
add_action('wp_ajax_overview', 'wpstg_overview');


/**
 * 2nd step: Scanning
 * Collect database and file data for clong
 * 
 * @global type $wpdb
 * @global type $wpstg_clone_details
 * @global type $all_files
 */
function wpstg_scanning() {
	global $wpdb, $wpstg_clone_details, $all_files;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	//Scan DB
	$tables = $wpdb->get_results("show table status like '" . $wpdb->prefix . "_%'");
	$wpstg_clone_details['all_tables'] = $wpdb->get_col("show tables like '" . $wpdb->prefix . "%'");

	//Scan Files
	$wpstg_clone_details['total_size'] = 0;
	unset($wpstg_clone_details['large_files']);
	$folders = wpstg_scan_files(rtrim(get_home_path(), '/'));
	array_pop($folders);

	$path = wpstg_get_upload_dir() . '/remaining_files.json';
	//file_put_contents($path, json_encode($all_files, JSON_FORCE_OBJECT));
	file_put_contents($path, json_encode($all_files));

	wpstg_save_options();

	$clone_id = '';
	if (isset($wpstg_clone_details['current_clone']))
		$clone_id = 'value="' . $wpstg_clone_details['current_clone'] . '" disabled';

	$free_space = disk_free_space(get_home_path());
	$overflow = $free_space < $wpstg_clone_details['total_size'] ? true : false;
	?>
	<label id="wpstg-clone-label" for="wpstg-new-clone">
				<?php echo __('Name your new Staging Site:', 'wpstg');?>
		<input type="text" id="wpstg-new-clone-id" <?php echo $clone_id; ?>>
	</label>
	<a href="#" id="wpstg-start-cloning" class="wpstg-next-step-link wpstg-link-btn" data-action="cloning"><?php echo __('Start Cloning', 'wpstg');?></a>
	<span class="wpstg-error-msg">
		<?php echo $overflow ? __('Not enough free disk space to create a staging site', 'wpstg') : ''; ?>
	</span>

	<div class="wpstg-tabs-wrapper">
		<a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db"><?php echo __('Database', 'wpstg'); ?></a>
		<a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files"><?php echo __('Files', 'wpstg'); ?></a></a>
		<div class="wpstg-tab-section" id="wpstg-scanning-db">
			<?php
				do_action('wpstg_scanning_db');
				echo '<h4 style="margin:0px;">' . __('Select the tables to be copied. Greyed out tables are already copied in previous steps and the copying will continous from this step:', 'wpstg') . '<h4>';
				wpstg_show_tables($tables); ?>
		</div> <!-- #wpstg-scanning-db -->
		<div class="wpstg-tab-section" id="wpstg-scanning-files">

			<?php
				echo '<h4 style="margin:0px;">' . __('Select the folders to be copied:', 'wpstg') . '<h4>';
				wpstg_directory_structure($folders);
				wpstg_show_large_files();
			?>
		</div> <!-- #wpstg-scanning-files -->
	</div>
	<a href="#" class="wpstg-prev-step-link wpstg-link-btn">Back</a>
	<?php
	wp_die();
}
add_action('wp_ajax_scanning', 'wpstg_scanning');

/**
 * Display db tables
 * 
 * @global type $wpstg_clone_details
 * @param type $tables
 */
function wpstg_show_tables($tables) {
	global $wpstg_clone_details;

	$cloned_tables = isset($wpstg_clone_details['cloned_tables']) ? $wpstg_clone_details['cloned_tables'] : array();
		
	foreach ($tables as $table) { ?>
		<div class="wpstg-db-table">
			<label>
				<input type="checkbox" checked name="<?php echo $table->Name; ?>" <?php echo in_array($table->Name, $cloned_tables) ? 'disabled' : ''; ?>>
				<?php echo $table->Name; ?>
			</label>
			<span class="wpstg-size-info">
				<?php echo wpstg_short_size($table->Data_length + $table->Index_length); ?>
			</span>
		</div>
	<?php }
}


/**
 * Scan all files and create directory structure
 * 
 * @global type $all_files
 * @global type $wpstg_clone_details
 * @global $wpstg_options $wpstg_options
 * @param type $path
 * @param array $folders
 * @return type
 */
function wpstg_scan_files($path, &$folders = array()) {
	global $all_files, $wpstg_clone_details, $wpstg_options;

	$batch_size = isset($wpstg_options['wpstg_batch_size']) ? $wpstg_options['wpstg_batch_size'] : 20;
	$batch_size *= 1024*1024;
	$wpstg_clone_details['large_files'] = isset($wpstg_clone_details['large_files']) ? $wpstg_clone_details['large_files'] : array();
	$clone = isset($wpstg_clone_details['current_clone']) ? $wpstg_clone_details['current_clone'] : null;

	if (is_dir($path)) {
		$dir = dir($path);
		$dirsize = 0;
		while (false !== $entry = $dir->read()) {
			if ($entry == '.' || $entry == '..' || $entry == $clone)
				continue;
			if (is_file("$path/$entry")) {
				$all_files[] = "$path/$entry";
				$dirsize += filesize("$path/$entry");
				if ($batch_size < $size = filesize("$path/$entry"))
					$wpstg_clone_details['large_files'][] = "$path/$entry";
				$wpstg_clone_details['total_size'] += $size;
				continue;
			}
			$tmp = wpstg_scan_files("$path/$entry", $folders[$entry]);
			$dirsize += $tmp['size'];
		}
		$folders['size'] = $dirsize;
	}
	return $folders;
}

/**
 * Get a list of all files which will be copied
 * 
 * @param string $folder
 * @param array $files
 * @param string $total_size
 * @return array files
 */

function wpstg_get_files($folder, &$files = array(), &$total_size) {
	if (! is_dir($folder))
		return array();
	$dir = dir($folder);
	while (false !== $entry = $dir->read()) {
		if ($entry == '.' || $entry == '..')
			continue;
		if (is_file("$folder/$entry")) {
			$files[] = "$folder/$entry";
			$total_size -= filesize("$folder/$entry");
		} else
			wpstg_get_files("$folder/$entry", $files, $total_size);
	}
	return $files;
}

/**
 * Display directory structure with checkboxes
 * 
 * @param array $folders list of folder names
 * @param string $path base path for the directory structure
 * @param string $not_checked checkbox value
 * @param string $is_removing
 */
function wpstg_directory_structure($folders, $path = null, $not_checked = false, $is_removing = false) {
	$existing_clones = get_option('wpstg_existing_clones', array());
	$path = $path === null ? rtrim(get_home_path(), '/') : $path;

	foreach ($folders as $name => $folder) {
		$size = array_pop($folder);
		if ($is_removing)
			$tmp = false;
		else
			$tmp = $not_checked ? $not_checked : in_array($name, $existing_clones); ?>
		<div class="wpstg-dir">
			<input type="checkbox" class="wpstg-check-dir" <?php echo $tmp ? '' : 'checked'; ?> name="<?php echo "$path/$name"; ?>">
			<a href="#" class="wpstg-expand-dirs <?php echo $tmp ? 'disabled' : ''; ?>"><?php echo $name;?></a>
			<span class="wpstg-size-info"><?php echo wpstg_short_size($size); ?></span>
				<?php if (!empty($folder)) : ?>
					<div class="wpstg-dir wpstg-subdir">
						<?php wpstg_directory_structure($folder, "$path/$name", $tmp, $is_removing); ?>
					</div>
				<?php endif; ?>
		</div>
	<?php }
}

/**
 * Convert byte into human readable numbers
 * 
 * @param integer $size
 * @return string
 */
function wpstg_short_size($size) {
	if (1 < $out = $size / 1000000000)
		return round($out, 2) . ' Gb';
	else if (1 < $out = $size / 1000000)
		return round($out, 2) . ' Mb';
	else if (1 < $out = $size / 1000)
		return round($out, 2) . ' Kb';
	return $size . ' bytes';
}

//Display list of large files
function wpstg_show_large_files() {
	global $wpstg_clone_details;

	$large_files = isset($wpstg_clone_details['large_files']) ? $wpstg_clone_details['large_files'] : array();
	if (! empty($large_files)) : ?>

	<div id="wpstg-large-files">
			<h4 style="margin-top: 0px;">
				<?php echo __('We have detected the following large files which could neeed some investigation. Often this files are backup files or other temporary files which must not necessarily copied for creating a staging site:','wpstg');?>
			</h4>
				
		<?php foreach ($large_files as $file) : ?>
			<div class="wpstg-large-file"><li><?php echo $file; ?></li></div>
		<?php endforeach; ?>
	</div> <!-- #wpstg-large-file -->

	<?php endif;
}

//Check cloneID
function wpstg_check_clone() {
	global $wpstg_clone_details;
	$wpstg_clone_details = wpstg_get_options();

	$cur_clone = preg_replace('/[^A-Za-z0-9]/', '', $_POST['cloneID']);
	$existing_clones = get_option('wpstg_existing_clones', array());
	wp_die(!in_array($cur_clone, $existing_clones));
}
add_action('wp_ajax_check_clone', 'wpstg_check_clone');

//3rd step: Cloning ////////////////////////////////////////////////////////////////////////////////////////////////////
function wpstg_cloning() {
	global $wpstg_clone_details;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	$clone = preg_replace('/[^A-Za-z0-9]/', '', $_POST['cloneID']);
	$wpstg_clone_details['current_clone'] = isset($wpstg_clone_details['current_clone']) ? $wpstg_clone_details['current_clone'] : $clone;

	$wpstg_clone_details['cloned_tables'] = isset($wpstg_clone_details['cloned_tables']) ? $wpstg_clone_details['cloned_tables'] : array();
	if (isset($_POST['uncheckedTables']))
		$wpstg_clone_details['cloned_tables'] = array_merge($wpstg_clone_details['cloned_tables'], $_POST['uncheckedTables']);
	if (isset($_POST['excludedFolders'])) {
		$path = wpstg_get_upload_dir() . '/remaining_files.json';
		$all_files = json_decode(file_get_contents($path), true);

		$excluded_files = array();
		foreach ($_POST['excludedFolders'] as $folder) {
			$tmp_array = array();
			$excluded_files = array_merge($excluded_files, wpstg_get_files($folder, $tmp_array, $wpstg_clone_details['total_size']));    
		}
		$remaining_files = array_diff($all_files, $excluded_files);
		file_put_contents($path, json_encode(array_values($remaining_files)));
	}

	$wpstg_clone_details['db_progress'] = isset($wpstg_clone_details['db_progress']) ? $wpstg_clone_details['db_progress'] : 0;
	$wpstg_clone_details['files_progress'] = isset($wpstg_clone_details['files_progress']) ? $wpstg_clone_details['files_progress'] : 0;
	$wpstg_clone_details['links_progress'] = isset($wpstg_clone_details['links_progress']) ? $wpstg_clone_details['links_progress'] : 0;

	wpstg_save_options();
	do_action('wpstg_start_cloning', $clone);
	?>
	<div class="wpstg-cloning-section"> <?php echo __('Copy DB tables', 'wpstg');?>
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-db-progress" style="width: <?php echo 100 * $wpstg_clone_details['db_progress']; ?>%;"></div>
		</div>
	</div>
	<div class="wpstg-cloning-section"><?php echo __('Copy files', 'wpstg');?>
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-files-progress" style="width: <?php echo 100 * $wpstg_clone_details['files_progress']; ?>%;"></div>
		</div>
	</div>
	<div class="wpstg-cloning-section"><?php echo __('Replace Links', 'wpstg');?>
		<div class="wpstg-progress-bar">
			<div class="wpstg-progress" id="wpstg-links-progress" style="width: <?php echo 100 * $wpstg_clone_details['links_progress']; ?>%"></div>
		</div>
	</div>
	<span id="wpstg-cloning-result"></span>
	<a href="<?php echo get_home_url();?>" id="wpstg-clone-url" target="_blank"></a>
	<a href="#" id="wpstg-cancel-cloning" class="wpstg-link-btn"><?php echo __('Cancel', 'wpstg');?></a>
	<a href="#" id="wpstg-home-link" class="wpstg-link-btn"><?php echo __('Home', 'wpstg');?></a>
	<a href="#" id="wpstg-try-again" class="wpstg-link-btn"><?php echo __('Try Again', 'wpstg');?></a>
	<?php
	wp_die();
}
add_action('wp_ajax_cloning', 'wpstg_cloning');

function wpstg_clone_db() {
	global $wpdb, $wpstg_clone_details;
	$wpstg_clone_details = wpstg_get_options();

	$db_helper = apply_filters('wpstg_db_helper', $wpdb, $wpstg_clone_details['current_clone']);
	if ($db_helper->dbname != $wpdb->dbname)
		wpstg_clone_db_slow($db_helper);
	else
		wpstg_clone_db_fast();
}
add_action('wp_ajax_wpstg_clone_db', 'wpstg_clone_db');

function wpstg_clone_db_fast() {
	global $wpdb, $wpstg_clone_details, $wpstg_options;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	$progress = isset($wpstg_clone_details['db_progress']) ? $wpstg_clone_details['db_progress'] : 0;
	if ($progress >= 1)
		wp_die(1);

	$limit = isset($wpstg_options['wpstg_query_limit']) ? $wpstg_options['wpstg_query_limit'] : 1000;
	$rows_count = 0;

	while (true) {
		$table = isset($wpstg_clone_details['current_table']) ? $wpstg_clone_details['current_table'] : null;
		$is_new = false;

		if ($table === null) {
			$tables = $wpstg_clone_details['all_tables'];
			$cloned_tables = !empty($wpstg_clone_details['cloned_tables']) ? $wpstg_clone_details['cloned_tables'] : array(); //already cloned tables
			$tables = array_diff($tables, $cloned_tables);
			if (empty($tables)) { //exit condition
				$wpstg_clone_details['db_progress'] = 1;
				wpstg_save_options();
				WPSTG()->logger->info('DB has been cloned successfully');
				wp_die(1);
			}
			$table = reset($tables);
			$is_new = true;
		}

		$new_table = $wpstg_clone_details['current_clone'] . '_' . $table;
		$offset = isset($wpstg_clone_details['offsets'][$table]) ? $wpstg_clone_details['offsets'][$table] : 0;
		$is_cloned = true;

		if ($is_new) {
			$existing_table = $wpdb->get_var(
				$wpdb->prepare(
					'show tables like %s',
					$new_table
				)
			);
			if ($existing_table == $new_table)
				$wpdb->query("drop table $new_table");

			$is_cloned = $wpdb->query("create table $new_table like $table");
			$wpstg_clone_details['current_table'] = $table;
		}
		if ($is_cloned) {
			$limit -= $rows_count;
			if ($limit < 1)
				break;
			$inserted_rows = $wpdb->query(
				"insert $new_table select * from $table limit $offset, $limit"
			);
			if ($inserted_rows !== false) {
				$wpstg_clone_details['offsets'][$table] = $offset + $inserted_rows;
				$rows_count += $inserted_rows;
				if ($inserted_rows < $limit) {
					$wpstg_clone_details['cloned_tables'][] = $table;
					$all_tables_count = count($wpstg_clone_details['all_tables']);
					$cloned_tables_count = count($wpstg_clone_details['cloned_tables']);
					$wpstg_clone_details['db_progress'] = $cloned_tables_count / $all_tables_count;
					unset($wpstg_clone_details['current_table']);
					wpstg_save_options();
				}
				if ($rows_count > $limit) {
					$all_tables_count = count($wpstg_clone_details['all_tables']);
					$cloned_tables_count = count($wpstg_clone_details['cloned_tables']);
					$wpstg_clone_details['db_progress'] = $cloned_tables_count / $all_tables_count;
					wpstg_save_options();
					WPSTG()->logger->info('Query limit is exceeded. Current Table: ' . $table);
					wp_die($wpstg_clone_details['db_progress']);
				}
			} else {
				WPSTG()->logger->info('Table ' . $new_table . ' has been created, BUT inserting rows failed. Offset: ' . $offset);
				wpstg_save_options();
				wp_die(-1);
			}
		} else {
			WPSTG()->logger->info('Creating table ' . $table . ' has been failed.');
			wpstg_save_options();
			wp_die(-1);
		}
	} //end while
	wpstg_save_options();
	wp_die($progress);
}

function wpstg_clone_db_slow($db_helper) {
	global $wpdb, $wpstg_clone_details, $wpstg_options;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$one = 1;

	$progress = isset($wpstg_clone_details['db_progress']) ? $wpstg_clone_details['db_progress'] : 0;
	if ($progress >= 1)
		wp_die(1);

	$limit = isset($wpstg_options['wpstg_query_limit']) ? $wpstg_options['wpstg_query_limit'] : 1000;
	$rows_count = 0;

	while (true) {
		$table = isset($wpstg_clone_details['current_table']) ? $wpstg_clone_details['current_table'] : null;
		$is_new = false;

		if ($table === null) {
			$tables = $wpstg_clone_details['all_tables'];
			$cloned_tables = !empty($wpstg_clone_details['cloned_tables']) ? $wpstg_clone_details['cloned_tables'] : array(); //already cloned tables
			$tables = array_diff($tables, $cloned_tables);
			if (empty($tables)) { //exit condition
				$wpstg_clone_details['db_progress'] = 1;
				wpstg_save_options();
				WPSTG()->logger->info('DB has been cloned successfully');
				wp_die(1);
			}
			$table = reset($tables);
			$is_new = true;
		}

		$new_table = $wpstg_clone_details['current_clone'] . '_' . $table;
		$offset = isset($wpstg_clone_details['offsets'][$table]) ? $wpstg_clone_details['offsets'][$table] : 0;
		$is_cloned = true;

		if ($is_new) {
			$existing_table = $db_helper->get_var(
				$db_helper->prepare(
					'show tables like %s',
					$new_table
				)
			);
			if ($existing_table == $new_table)
				$db_helper->query("drop table $new_table");

			$tmp_result = $wpdb->get_row("show create table $table", ARRAY_N);
			$create_sql = str_replace($tmp_result[0], $new_table, $tmp_result[1], $one);
			$is_cloned = $db_helper->query($create_sql);
			$wpstg_clone_details['current_table'] = $table;
		}
		if ($is_cloned) {
			$limit -= $rows_count;
			if ($limit < 1)
				break;

			$selected_rows = $wpdb->get_results("select * from $table limit $offset, $limit", ARRAY_N);
			$inserted_rows = 0;
			foreach ($selected_rows as $row) {
				$row = esc_sql($row);
				$values = implode("', '", $row);
				$query = "insert into $new_table values('$values')";
				$inserted_rows += $db_helper->query($query);
			}

			if ($inserted_rows !== false) {
				$wpstg_clone_details['offsets'][$table] = $offset + $inserted_rows;
				$rows_count += $inserted_rows;
				if ($inserted_rows < $limit) {
					$wpstg_clone_details['cloned_tables'][] = $table;
					$all_tables_count = count($wpstg_clone_details['all_tables']);
					$cloned_tables_count = count($wpstg_clone_details['cloned_tables']);
					$wpstg_clone_details['db_progress'] = $cloned_tables_count / $all_tables_count;
					unset($wpstg_clone_details['current_table']);
					wpstg_save_options();
				}
				if ($rows_count > $limit) {
					$all_tables_count = count($wpstg_clone_details['all_tables']);
					$cloned_tables_count = count($wpstg_clone_details['cloned_tables']);
					$wpstg_clone_details['db_progress'] = $cloned_tables_count / $all_tables_count;
					wpstg_save_options();
					WPSTG()->logger->info('Query limit is exceeded. Current Table: ' . $table);
					wp_die($wpstg_clone_details['db_progress']);
				}
			} else {
				WPSTG()->logger->info('Table ' . $new_table . ' has been created, BUT inserting rows failed. Offset: ' . $offset);
				wpstg_save_options();
				wp_die(-1);
			}
		} else {
			WPSTG()->logger->info('Creating table ' . $table . ' has been failed.');
			wpstg_save_options();
			wp_die(-1);
		}
	} //end while
	wpstg_save_options();
	wp_die($progress);
}

/**
 * Copy all wordpress files into staging subfolder
 * 
 * @global $wpstg_clone_details
 * @global $wpstg_options
 * @global $batch
 */
function wpstg_copy_files() {
	global $wpstg_clone_details, $wpstg_options, $batch;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	$clone = get_home_path() . $wpstg_clone_details['current_clone'];
	$path = wpstg_get_upload_dir() . '/remaining_files.json';
	$files = json_decode(file_get_contents($path), true);
	$start_index = isset($wpstg_clone_details['file_index']) ? $wpstg_clone_details['file_index'] : 0;
	$wpstg_clone_details['files_progress'] = isset($wpstg_clone_details['files_progress']) ? $wpstg_clone_details['files_progress'] : 0;
	$batch_size = isset($wpstg_options['wpstg_batch_size']) ? $wpstg_options['wpstg_batch_size'] : 20;
	$batch_size *= 1024*1024;
	$batch = 0;

	if (!is_dir($clone))
		mkdir($clone);

	for ($i = $start_index; $i < count($files); $i++) {
		$new_file = wpstg_create_directories($files[$i], get_home_path(), $clone);
		$size = filesize($files[$i]);
		if ($size > $batch_size) {
			if (wpstg_copy_large_file($files[$i], $new_file, $batch_size)) {
				WPSTG()->logger->info('Copy LARGE file: ' . $files[$i] . '. Batch size: ' . wpstg_short_size($batch + $size) . ' (' . ($batch + $size) . ' bytes)');
				$wpstg_clone_details['file_index'] = $i + 1;
				$part = ($batch + $size) / $wpstg_clone_details['total_size'];
				$wpstg_clone_details['files_progress'] += $part;
				wpstg_save_options();
				wp_die($wpstg_clone_details['files_progress']);
			} else {
				WPSTG()->logger->info('Copying LARGE file has been failed: ' . $files[$i]);
				$wpstg_clone_details['file_index'] = $i;
				$part = $batch / $wpstg_clone_details['total_size'];
				$wpstg_clone_details['files_progress'] += $part;
				wpstg_save_options();
				wp_die(-1);
			}
		}
		if ($batch_size > $batch + $size) {
			if (copy($files[$i], $new_file)) {
				$batch += $size;
			} else {
				WPSTG()->logger->info('Copying file has been failed: ' . $files[$i]);
				$wpstg_clone_details['file_index'] = $i;
				$part = $batch / $wpstg_clone_details['total_size'];
				$wpstg_clone_details['files_progress'] += $part;
				wpstg_save_options();
				wp_die(-1);
			}
		} else {
			WPSTG()->logger->info('Batch size: ' . wpstg_short_size($batch) . ' (' . $batch . ' bytes)' . '. Current File: ' . $files[$i]);
			$wpstg_clone_details['file_index'] = $i;
			$part = $batch / $wpstg_clone_details['total_size'];
			$wpstg_clone_details['files_progress'] += $part;
			wpstg_save_options();
			wp_die($wpstg_clone_details['files_progress']);
		}
	}

	$wpstg_clone_details['files_progress'] = 1;
	wpstg_save_options();
	wp_die(1);
}
add_action('wp_ajax_copy_files', 'wpstg_copy_files');


/**
 * Create target directory for copy process and returns path to the copied file
 * 
 * @param string $file Source file including full path e.g. var/www/htdocs/mainsite/index.php
 * @param string $home Root path of the main wordpress installation e.g. var/www/htdocs/mainsite/
 * @param string $clone path of the clone e.g. var/www/htdocs/mainsite/myclone/
 * 
 * @return string full target path e.g. var/www/htdocs/mainsite/myclone/wp-content/
 * 
 * 
 */
function wpstg_create_directories($file, $home, $clone) {
	$path = substr($file, strlen($home));
	$folders = explode('/', $path);
	array_pop($folders);
	$new_folder = $clone;

	foreach ($folders as $folder) {
		$new_folder .= '/' . $folder;
		if (!is_dir($new_folder))
			mkdir($new_folder);
	}

	return "$clone/$path";
}

function wpstg_copy_large_file($src, $dst, $batch) {
	$fin = fopen($src, 'rb');
	$fout = fopen($dst, 'w');
	while (! feof($fin))
		if (false === fwrite($fout, fread($fin, $batch)))
			return false;
	fclose($fin);
	fclose($fout);

	return true;
}

/**
 * Replace all urls in data
 * 
 * @global type $wpdb
 * @global type $wpstg_clone_details
 */

function wpstg_replace_links() {
	global $wpdb, $wpstg_clone_details;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	$db_helper = apply_filters('wpstg_db_helper', $wpdb, $wpstg_clone_details['current_clone']);
	$new_prefix = $wpstg_clone_details['current_clone'] . '_' . $wpdb->prefix;
	$wpstg_clone_details['links_progress'] = isset($wpstg_clone_details['links_progress']) ? $wpstg_clone_details['links_progress'] : 0;
	//replace site url in options
	if ($wpstg_clone_details['links_progress'] < .1) {
		$result = $db_helper->query(
			$db_helper->prepare(
				'update ' . $new_prefix . 'options set option_value = %s where option_name = \'siteurl\' or option_name = \'home\'',
				get_home_url() . '/' . $wpstg_clone_details['current_clone']
			)
		);
		if (!$result) {
			WPSTG()->logger->info('Replacing site url has been failed.');
			wp_die(-1);
		} else {
			$wpstg_clone_details['links_progress'] = .33;
			wpstg_save_options();
		}
	}

	//replace table prefix in meta keys
	if ($wpstg_clone_details['links_progress'] < .5) {
		$result_options = $db_helper->query(
			$db_helper->prepare(
				'update ' . $new_prefix . 'usermeta set meta_key = replace(meta_key, %s, %s) where meta_key like %s',
				$wpdb->prefix,
				$new_prefix,
				$wpdb->prefix . '_%'
			)
		);
		$result_usermeta = $db_helper->query(
			$db_helper->prepare(
				'update ' . $new_prefix . 'options set option_name = replace(option_name, %s, %s) where option_name like %s',
				$wpdb->prefix,
				$new_prefix,
				$wpdb->prefix . '_%'
			)
		);
		if (!$result_options || !$result_usermeta) {
			WPSTG()->logger->info('Replacing table prefix has been failed.');
			wp_die(.33);
		} else {
			$wpstg_clone_details['links_progress'] = .66;
			wpstg_save_options();
		}
	}

	//replace $table_prefix in wp-config.php
	if ($wpstg_clone_details['links_progress'] < 1) {
		$path = get_home_path() . '/' . $wpstg_clone_details['current_clone'] . '/wp-config.php';
		$content = file_get_contents($path);
		if ($content) {
			$content = str_replace('$table_prefix', '$table_prefix = \'' . $new_prefix . '\';//', $content);
			if ($db_helper->dbname != $wpdb->dbname) {
				$content = str_replace('define(\'DB_NAME\'', 'define(\'DB_NAME\', \'' . $db_helper->dbname . '\');//', $content);
				$content = str_replace('define(\'DB_USER\'', 'define(\'DB_USER\', \'' . $db_helper->dbuser . '\');//', $content);
				$content = str_replace('define(\'DB_PASSWORD\'', 'define(\'DB_PASSWORD\', \'' . $db_helper->dbpassword . '\');//', $content);
				$content = str_replace('define(\'DB_HOST\'', 'define(\'DB_HOST\', \'' . $db_helper->dbhost . '\');//', $content);
			}
			if (FALSE === file_put_contents($path, $content)) {
				WPSTG()->logger->info($path . 'wp-config.php is not writable');
				wp_die(.66);
			}
		} else {
			WPSTG()->logger->info($path . 'is not readable.');
			wp_die(.66);
		}
	}

	$existing_clones = get_option('wpstg_existing_clones', array());
	$existing_clones[] = $wpstg_clone_details['current_clone'];
	update_option('wpstg_existing_clones', $existing_clones);

	wpstg_clear_options();


	wp_die(1);
}
add_action('wp_ajax_replace_links', 'wpstg_replace_links');

/**
 * Clear all task related data in *.json files
 * 
 * @return void
 */
function wpstg_clear_options() {
	$path = wpstg_get_upload_dir() . '/clone_details.json';
		if (wp_is_writable($path)) {
			file_put_contents($path, '');
		}
		WPSTG()->logger->info(wpstg_get_upload_dir() . '/clone_details.json has been purged successfully');
		
		$path = wpstg_get_upload_dir() . '/remaining_files.json';
		if (wp_is_writable($path)) {
			file_put_contents($path, '');
		}
		WPSTG()->logger->info(wpstg_get_upload_dir() . '/remaining_files.json has been purged successfully');
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////
/** Check the files before removing
 * 
 * @global type $wpdb
 */
function wpstg_preremove_clone() {
	global $wpdb;

	$clone = $_POST['cloneID'];
	$prefix = $clone . '_' . $wpdb->prefix;
	$db_helper = apply_filters('wpstg_db_helper', $wpdb, $clone);
	$tables = $db_helper->get_results("show table status like '" . $prefix . "_%'");

	$path = get_home_path() . $clone;
	$folders[$clone] = wpstg_check_removing_files($path);
	?>
	<h4 class="wpstg-notice-alert"><?php echo __('Attention: Check carefully if this DB tables and files are safe to delete for the staging site <span style="background-color:#575757;color:#fff;">','wpstg') . $clone . '</span> Usually the preselected data can be deleted without any risk, but in case something goes wrong you better check it.'; ?> 
		</h4>
	<div class="wpstg-tabs-wrapper">
		<a href="#" class="wpstg-tab-header active" data-id="#wpstg-scanning-db"><?php echo __('DB tables to remove', 'wpstg');?></a>
		<a href="#" class="wpstg-tab-header" data-id="#wpstg-scanning-files"><?php echo __('Files to remove', 'wpstg');?></a>
		<div class="wpstg-tab-section" id="wpstg-scanning-db">
			<?php
				echo '<h4 style="margin:0px;">' . __('Unselect the tables for not beeing copied:', 'wpstg') . '<h4>';
				wpstg_show_tables($tables);
			?>
		</div> <!-- #wpstg-scanning-db -->
		<div class="wpstg-tab-section" id="wpstg-scanning-files">
			<?php
				echo '<h4 style="margin:0px;">' . __('Unselect the folders to exclude them from removing. You can click on a folder name to expand it:', 'wpstg') . '<h4>';
				wpstg_directory_structure($folders, null, false, true); 
			?>
		</div> <!-- #wpstg-scanning-files -->
	</div>
	<a href="#" class="wpstg-link-btn" id="wpstg-cancel-removing">Cancel</a>
	<a href="#" class="wpstg-link-btn" id="wpstg-remove-clone" data-clone="<?php echo $clone; ?>"><?php echo __('Remove', 'wpstg');?></a>
	<?php
	wp_die();
}
add_action('wp_ajax_preremove', 'wpstg_preremove_clone');

/**
 * Check the folders for removing
 * 
 * @param string $path
 * @param array $folders
 * @return type array of folder for removing
 */
function wpstg_check_removing_files($path, &$folders = array()) {
	if (is_dir($path)) {
		$dirsize = 0;
		$dir = dir($path);
		while (false !== $entry = $dir->read()) {
			if ($entry == '.' || $entry == '..')
				continue;
			if (is_file("$path/$entry")) {
				$dirsize += filesize("$path/$entry");
				continue;
			}

			$tmp = wpstg_check_removing_files("$path/$entry", $folders[$entry]);
			$dirsize += $tmp['size'];
		}
		$folders['size'] = $dirsize;
	}
	return $folders;
}

/**
 * Removes current clone
 * 
 * @global type $wpdb
 * @global type $wpstg_clone_details
 * @param string $isAjax
 */
function wpstg_remove_clone($isAjax = true) {
	global $wpdb, $wpstg_clone_details;
	check_ajax_referer( 'wpstg_ajax_nonce', 'nonce' );
	$wpstg_clone_details = wpstg_get_options();

	$clone = $_POST['cloneID'];

	if (empty ($clone) || $clone === '' ) {
		WPSTG()->logger->info('cloneID does not exist or is empty');
		wp_die(-1);
	}

	//drop clone tables
	$db_helper = apply_filters('wpstg_db_helper', $wpdb, $clone);
	$tables = $db_helper->get_col( $wpdb->prepare('show tables like %s', $clone . '_%'));
	$unchecked_tables = isset($_POST['uncheckedTables']) ? $_POST['uncheckedTables'] : array();
	$tables = array_diff($tables, $unchecked_tables);
	foreach ($tables as $table) {
		if (! wpstg_is_root_table($table, $wpdb->prefix))
			$result = $db_helper->query("drop table $table");
		if (! $result) {
			WPSTG()->logger->info('Droping table ' . $table . ' has been failed.');
			wp_die(-1);
		}
			WPSTG()->logger->info('Droping table ' . $table . ' was successfull');
	}

	//remove clone folder
	$excluded_folders = isset($_POST['excludedFolders']) ? $_POST['excludedFolders'] : array();
	$result = deleteDirectory(get_home_path() . $clone, $excluded_folders);
	if (! $result) {
		WPSTG()->logger->info('Removing clone folder '.get_home_path() . $clone.' has been failed.');
		wp_die(-1);
	}
	WPSTG()->logger->info('Clone folder '.get_home_path() . $clone.' has been removed successfully.');
	$existing_clones = get_option('wpstg_existing_clones', array());
	$key = array_search($clone, $existing_clones);
		
	if ($key !== false && $key !== NULL) {
		unset($existing_clones[$key]);
		update_option('wpstg_existing_clones', $existing_clones);
	}

	do_action('wpstg_remove_clone', $clone);
	if ($isAjax) {
		WPSTG()->logger->info('Clone( ' . $clone . ' ) has been removed successfully.');
		wp_die(0);
	}
}
add_action('wp_ajax_remove_clone', 'wpstg_remove_clone');



/**
 * Delete a specific directory
 * 
 * @param array $dir
 * @param array $excluded_dirs
 * @return boolean
 */
function deleteDirectory($dir, $excluded_dirs) {
	if (!file_exists($dir))
		return true;

	if (!is_dir($dir))
		return unlink($dir);

	foreach (scandir($dir) as $item) {
		if ($item == '.' || $item == '..' || in_array("$dir/$item", $excluded_dirs))
			continue;

		if (!deleteDirectory("$dir/$item", $excluded_dirs))
			return false;
	}

	$need_rm = true;
	foreach ($excluded_dirs as $ex_dir)
		if (strpos($ex_dir, $dir) !== false) {
			$need_rm = false;
			break;
		}

	return $need_rm ? rmdir($dir) : true;
}

/* Cancel cloning process
 * Remove current clone and purge json files containing task related data
 * 
 * @return void
 */

function wpstg_cancel_cloning() {
	wpstg_remove_clone(false);
	wpstg_clear_options();
	wp_die(0);
}
add_action('wp_ajax_cancel_cloning', 'wpstg_cancel_cloning');


/**
 * Check if table name starts with prefix which belongs to the wordpress root table
 * 
 * @param string $haystack
 * @param string $needle
 * @return bool true if table name starts with prefix of the root table
 */
function wpstg_is_root_table($haystack, $needle) {
	return strpos($haystack, $needle) === 0;
}

/* Get global clone details options
 * 
 * @return string JSON that includes the cloning relevant data
 */
function wpstg_get_options() {
	$path = wpstg_get_upload_dir() . '/clone_details.json';
	$content = file_get_contents($path);
	return json_decode($content, true);

}

/* Save global clone details options
 * 
 * @return void
 */
function wpstg_save_options() {
	global $wpstg_clone_details;
	$path = wpstg_get_upload_dir() . '/clone_details.json';
	if (wp_is_writable($path)) {
		file_put_contents($path, json_encode($wpstg_clone_details));
	}
}

function wpstg_error_processing() {
	$msg = sanitize_text_field($_POST['wpstg_error_msg']);
	if (! empty($msg))
		WPSTG()->logger->info($msg);
	wp_die();
}
add_action('wp_ajax_error_processing', 'wpstg_error_processing');

function process_chunk( $chunk ) {
	// prepare db
	global $wpdb;
	$this->set_time_limit();
	$queries = array_filter( explode( ";\n", $chunk ) );
	array_unshift( $queries, "SET sql_mode='NO_AUTO_VALUE_ON_ZERO';" );
	ob_start();
	$wpdb->show_errors();
	if( empty( $wpdb->charset ) ) {
		$charset = ( defined( 'DB_CHARSET' ) ? DB_CHARSET : 'utf8' );
		$wpdb->charset = $charset;
		$wpdb->set_charset( $wpdb->dbh, $wpdb->charset );
	}
	foreach( $queries as $query ) {
		if( false === $wpdb->query( $query ) ) {
			$return = ob_get_clean();
			$result = $this->end_ajax( $return );
			return $result;
		}
	}
	return true;
}
