<?php
/**
 * Admin Options Page
 *
 * @package     WPSTG
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2015, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* Returns list elements for jQuery tab navigation 
 * based on header callback
 * 
 * @since 0.9.0
 * @todo Use sprintf to sanitize  $field['id'] instead using str_replace() Should be faster? 
 * @return string
 */

function wpstg_getTabHeader($page, $section){
    global $wpstg_options;
    global $wp_settings_fields;
    
    if (!isset($wp_settings_fields[$page][$section]))
        return;
    
    echo '<ul>';
    foreach ((array) $wp_settings_fields[$page][$section] as $field) {  
    $sanitizedID = str_replace('[', '', $field['id'] );
    $sanitizedID = str_replace(']', '', $sanitizedID );     
     if (strpos($field['callback'],'header') !== false) { 
         echo '<li class="wpstg-tabs"><a href="#' . $sanitizedID . '">' . $field['title'] .'</a></li>';
     }      
    }
    echo '</ul>';
}


/**
 * Print out the settings fields for a particular settings section
 *
 * Part of the Settings API. Use this in a settings page to output
 * a specific section. Should normally be called by do_settings_sections()
 * rather than directly.
 *
 * @global $wp_settings_fields Storage array of settings fields and their pages/sections
 * @return string
 *
 * @since 2.1.2
 *
 * @param string $page Slug title of the admin page who's settings fields you want to show.
 * @param section $section Slug title of the settings section who's fields you want to show.
 * 
 * Copied from WP Core 4.0 /wp-admin/includes/template.php do_settings_fields()
 * We use our own function to be able to create jQuery tabs with easytabs()
 * 
*  We dont use tables here any longer. Are we stuck in the nineties?
 * @todo Use sprintf to sanitize  $field['id'] instead using str_replace() Should be faster?
 * @todo some media queries for better responisbility
 */
function wpstg_do_settings_fields($page, $section) {
    global $wp_settings_fields;
    $header = false;
    $firstHeader = false;
    
    if (!isset($wp_settings_fields[$page][$section]))
        return;
    
    // Check first if any callback header registered
    foreach ((array) $wp_settings_fields[$page][$section] as $field) {
       strpos($field['callback'],'header') !== false ? $header = true : $header = false; 
       
       if ($header === true)
               break;
    }
    
    foreach ((array) $wp_settings_fields[$page][$section] as $field) {
        
       $sanitizedID = str_replace('[', '', $field['id'] );
       $sanitizedID = str_replace(']', '', $sanitizedID );
       
       // Check if header has been created previously
       if (strpos($field['callback'],'header') !== false && $firstHeader === false) { 
           echo '<div id="' . $sanitizedID . '">'; 
           echo '<table class="form-table"><tbody>';
           $firstHeader = true;
       } elseif (strpos($field['callback'],'header') !== false && $firstHeader === true) { 
       // Header has been created previously so we have to close the first opened div
           echo '</table></div><div id="' . $sanitizedID . '">'; 
           echo '<table class="form-table"><tbody>';
           
       }  
        echo '<tr class="row"><th class="row th">';
        //echo "<pre>";
        //var_dump($field);
        if (!empty($field['args']['label_for']))
            echo '<label for="' . esc_attr($field['args']['label_for']) . '">' . $field['title'] . '</label>';
        else
            echo '<div class="col-title">' . $field['title'] . '<span class="description">' . $field['args']['desc'] . '</span></div>';
        echo '</th>';
        echo '<td>';
        call_user_func($field['callback'], $field['args']);
        echo '</td></tr>';
        
        
    }
    echo '</tbody></table>';
    if ($header === true){
    echo '</div>';
    }
}

/**
 * Options Page
 *
 * Renders the options page contents.
 *
 * @since 1.0
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_options_page() {
	global $wpstg_options;

	$active_tab = isset( $_GET[ 'tab' ] ) && array_key_exists( $_GET['tab'], wpstg_get_settings_tabs() ) ? $_GET[ 'tab' ] : 'general';

	ob_start();
	?>
	<div class="wpstg_admin">
             <span class="wp-staginglogo"><img src="<?php echo WPSTG_PLUGIN_URL . 'assets/images/logo_clean_small_212_25.png';?>">&nbsp;<span class="wpstg-version"><?php echo WPSTG_VERSION . ' / beta'; ?></span></span>
			<div class="wpstg-header">
				<?php echo __('Thank you for using WP Staging', 'wpstg');?>
				<br>
				<?php echo __('WP Staging is ready to create a staging site!', 'wpstg'); ?>
				<br>
				<iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwordpress.org%2Fplugins%2Fwp-staging%2F&amp;width=100&amp;layout=standard&amp;action=like&amp;show_faces=false&amp;share=true&amp;height=35&amp;appId=449277011881884" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:91px; height:20px;" allowTransparency="true"></iframe>
				<a class="twitter-follow-button" href="https://twitter.com/wp_staging" data-size="small" id="twitter-wjs" style="display: none;">Follow @wp_staging</a>
                                <a class="twitter-share-button"  href="https://twitter.com/intent/tweet?text=Check%20this%20WordPress%20Staging%20plugin%20&url=https://wordpress.org/plugins/wp-staging&hashtags=wpstaging&via=wp_staging">Tweet</a>
			</div>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach( wpstg_get_settings_tabs() as $tab_id => $tab_name ) {

				$tab_url = esc_url(add_query_arg( array(
					'settings-updated' => false,
					'tab' => $tab_id
				) ));

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';

				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">';
					echo esc_html( $tab_name );
				echo '</a>';
			}
			?>
		</h2>
		<div id="tab_container" class="tab_container">
                        <?php //wpstg_getTabHeader( 'wpstg_settings_' . $active_tab, 'wpstg_settings_' . $active_tab ); ?>   
                    <div class="panel-container"> <!-- new //-->
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpstg_settings' );
				wpstg_do_settings_fields( 'wpstg_settings_' . $active_tab, 'wpstg_settings_' . $active_tab );
				?>
				<!--</table>-->
                                
				<?php 
                                // do not show save button on add-on page
                                if ($active_tab !== 'addons')
                                    submit_button(); 
                                ?>
			</form>
                    </div> <!-- new //-->
		</div><!-- #tab_container-->
	</div><!-- .wrap -->
	<?php
	echo ob_get_clean();
}
