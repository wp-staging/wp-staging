<?php
/**
 * Register Settings
 *
 * @package     WPSTG
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2014, René Hermenau
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since 1.0.0
 * @return mixed
 */
function wpstg_get_option( $key = '', $default = false ) {
	global $wpstg_options;
	$value = ! empty( $wpstg_options[ $key ] ) ? $wpstg_options[ $key ] : $default;
	$value = apply_filters( 'wpstg_get_option', $value, $key, $default );
	return apply_filters( 'wpstg_get_option_' . $key, $value, $key, $default );
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 1.0
 * @return array WPSTG settings
 */
function wpstg_get_settings() {
	$settings = get_option( 'wpstg_settings' );
               
        
	if( empty( $settings ) ) {
		// Update old settings with new single option

		$general_settings = is_array( get_option( 'wpstg_settings_general' ) )    ? get_option( 'wpstg_settings_general' )  	: array();
                $visual_settings = is_array( get_option( 'wpstg_settings_visual' ) )   ? get_option( 'wpstg_settings_visual' )   : array();
                $networks = is_array( get_option( 'wpstg_settings_networks' ) )   ? get_option( 'wpstg_settings_networks' )   : array();
		$ext_settings     = is_array( get_option( 'wpstg_settings_extensions' ) ) ? get_option( 'wpstg_settings_extensions' )	: array();
		$license_settings = is_array( get_option( 'wpstg_settings_licenses' ) )   ? get_option( 'wpstg_settings_licenses' )   : array();
                $addons_settings = is_array( get_option( 'wpstg_settings_addons' ) )   ? get_option( 'wpstg_settings_addons' )   : array();
                
		$settings = array_merge( $general_settings, $visual_settings, $networks, $ext_settings, $license_settings, $addons_settings);

		update_option( 'wpstg_settings', $settings);
	}
	return apply_filters( 'wpstg_get_settings', $settings );
}

/**
 * Add all settings sections and fields
 *
 * @since 1.0
 * @return void
*/
function wpstg_register_settings() {

	if ( false == get_option( 'wpstg_settings' ) ) {
		add_option( 'wpstg_settings' );
	}

	foreach( wpstg_get_registered_settings() as $tab => $settings ) {

		add_settings_section(
			'wpstg_settings_' . $tab,
			__return_null(),
			'__return_false',
			'wpstg_settings_' . $tab
		);

		foreach ( $settings as $option ) {

			$name = isset( $option['name'] ) ? $option['name'] : '';

			add_settings_field(
				'wpstg_settings[' . $option['id'] . ']',
				$name,
				function_exists( 'wpstg_' . $option['type'] . '_callback' ) ? 'wpstg_' . $option['type'] . '_callback' : 'wpstg_missing_callback',
				'wpstg_settings_' . $tab,
				'wpstg_settings_' . $tab,
				array(
					'id'      => isset( $option['id'] ) ? $option['id'] : null,
					'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
					'name'    => isset( $option['name'] ) ? $option['name'] : null,
					'section' => $tab,
					'size'    => isset( $option['size'] ) ? $option['size'] : null,
					'options' => isset( $option['options'] ) ? $option['options'] : '',
					'std'     => isset( $option['std'] ) ? $option['std'] : '',
                                        'textarea_rows' => isset( $option['textarea_rows']) ? $option['textarea_rows'] : ''
				)
			);
		}

	}

	// Creates our settings in the options table
	register_setting( 'wpstg_settings', 'wpstg_settings', 'wpstg_settings_sanitize' );

}
add_action('admin_init', 'wpstg_register_settings');

/**
 * Retrieve the array of plugin settings
 *
 * @since 1.8
 * @return array
*/
function wpstg_get_registered_settings() {

	/**
	 * 'Whitelisted' WPSTG settings, filters are provided for each settings
	 * section to allow extensions and other plugins to add their own settings
	 */
	$wpstg_settings = array(
		/** General Settings */
		'general' => apply_filters( 'wpstg_settings_general',
			array(
                                'general_header' => array(
					'id' => 'general_header',
					'name' => '<strong>' . __( 'General settings', 'wpstg' ) . '</strong>',
					'desc' => __( ' ', 'wpstg' ),
					'type' => 'header'
				),
                                'wpstg_sharemethod' => array(
					'id' => 'wpstg_sharemethod',
					'name' =>  __( 'Share counts', 'wpstg' ),
					'desc' => __('<i>MashEngine</i> collects shares by calling directly social networks from your server. All shares are cached and stored in your database. <p> If you notice performance issues choose the classical <i>Sharedcount.com</i>. This needs an API key and is limited to 10.000 free requests daily but it is a little bit faster on requesting. After caching there is no performance advantage to MashEngine! <p> <strong>MashEngine collects: </strong> Facebook, Twitter, LinkedIn, Google+, Pinterest, Stumbleupon, Buffer, VK. <strong>Default:</strong> MashEngine', 'wpstg'),
					'type' => 'select',
					'options' => array(
                                            'mashengine' => 'MashEngine',
                                            'sharedcount' => 'Sharedcount.com'
                                        )
     
				),
				
				'wp-staging_apikey' => array(
					'id' => 'wp-staging_apikey',
					'name' => __( 'Sharedcount.com API Key', 'wpstg' ),
					'desc' => __( 'Get it at <a href="https://www.sharedcount.com" target="_blank">SharedCount.com</a> for 10.000 free daily requests.', 'wpstg' ),
					'type' => 'text',
					'size' => 'medium'
				),
				'wp-staging_sharecount_domain' => array(
					'id' => 'wp-staging_sharecount_domain',
					'name' => __( 'Sharedcount.com endpint', 'wpstg' ),
					'desc' => __( 'The SharedCount Domain your API key is configured to query. For example, free.sharedcount.com. This may update automatically if configured incorrectly.', 'wpstg' ),
					'type' => 'text',
					'size' => 'medium',
					'std'  => 'free.sharedcount.com'
				),
                                'wp-staging_cache' => array(
					'id' => 'wp-staging_cache',
					'name' =>  __( 'Cache expiration', 'wpstg' ),
					'desc' => __('Shares are counted for every post after this time. Notice that Sharedcount.com uses his own cache (30 - 60min) so share count does not update immediately. Make sure to increase this value especially when you use MashEngine! Otherwise it could happen that some networks block your requests due to hammering their rate limits. <p><strong>Default: </strong>5 min. <strong>Recommended: </strong>30min and more', 'wpstg'),
					'type' => 'select',
					'options' => wpstg_get_expiretimes()
				),
                                'disable_sharecount' => array(
					'id' => 'disable_sharecount',
					'name' => __( 'Disable Sharecount', 'wpstg' ),
					'desc' => __( 'Use this when curl() is not supported on your server or share counts should not counted. This mode does not call the database and no SQL queries are generated. (Only less performance advantage. All db requests are cached) Default: false', 'wpstg' ),
					'type' => 'checkbox'
				),
                                'hide_sharecount' => array(
					'id' => 'hide_sharecount',
					'name' => __( 'Hide Sharecount', 'wpstg' ),
					'desc' => __( '<strong>Optional:</strong> If you fill in any number here, the shares for a specific post are not shown until the share count of this number is reached.', 'wpstg' ),
					'type' => 'text',
                                        'size' => 'small'
				),
                                'excluded_from' => array(
					'id' => 'excluded_from',
					'name' => __( 'Exclude from', 'wpstg' ),
					'desc' => __( 'Exclude share buttons from a list of specific posts and pages. Put in the page id separated by a comma, e.g. 23, 63, 114 ', 'wpstg' ),
					'type' => 'text',
                                        'size' => 'medium'
				),
                                'execution_order' => array(
					'id' => 'execution_order',
					'name' => __( 'Execution Order', 'wpstg' ),
					'desc' => __( 'If you use other content plugins you can define here the execution order. Lower numbers mean earlier execution. E.g. Say "0" and WP-Staging is executed before any other plugin (When the other plugin is not overwriting our execution order). Default is "1000"', 'wpstg' ),
					'type' => 'text',
					'size' => 'small',
                                        'std'  => 1000
				),
                                'fake_count' => array(
					'id' => 'fake_count',
					'name' => __( 'Fake Share counts', 'wpstg' ),
					'desc' => __( 'This number will be aggregated to all your share counts and is multiplied with a post specific factor. (Number of post title words divided with 10).', 'wpstg' ),
					'type' => 'text',
                                        'size' => 'medium'
				),
                                'load_scripts_footer' => array(
					'id' => 'load_scripts_footer',
					'name' => __( 'JS Load Order', 'wpstg' ),
					'desc' => __( 'Enable this to load all *.js files into footer. Make sure your theme uses the wp_footer() template tag in the appropriate place. Default: Disabled', 'wpstg' ),
					'type' => 'checkbox'
				),
                                'facebook_count' => array(
					'id' => 'facebook_count_mode',
					'name' => __( 'Facebook Count', 'wpstg' ),
					'desc' => __( 'Get the Facebook total count including "likes" and "shares" or get only the pure share count', 'wpstg' ),
					'type' => 'select',
                                        'options' => array(
                                            'shares' => 'Shares',
                                            'likes' => 'Likes',
                                            'total' => 'Total: likes + shares + comments'
                                            
                                        )
				),
                                'uninstall_on_delete' => array(
					'id' => 'uninstall_on_delete',
					'name' => __( 'Remove Data on Uninstall?', 'wpstg' ),
					'desc' => __( 'Check this box if you would like WP-Staging to completely remove all of its data when the plugin is deleted.', 'wpstg' ),
					'type' => 'checkbox'
				),
                                'debug_header' => array(
					'id' => 'debug_header',
					'name' => '<strong>' . __( 'Debug', 'wpstg' ) . '</strong>',
					'desc' => __( ' ', 'wpstg' ),
					'type' => 'header'
				),
                                array(
					'id' => 'disable_cache',
					'name' => __( 'Disable Cache', 'wpstg' ),
					'desc' => __( '<strong>Note: </strong>Use this only for testing to see if shares are counted! Your page loading performance will drop. Works only when sharecount is enabled.<br>' . wpstg_cache_status(), 'wpstg' ),
					'type' => 'checkbox'
				),
                                'delete_cache_objects' => array(
					'id' => 'delete_cache_objects',
					'name' => __( 'Purge DB Cache', 'wpstg' ),
					'desc' => __( '<strong>Note: </strong>Use this with caution when you think your share counts are wrong. Checking this and using the save button will delete all stored wp-staging post_meta objects.<br>' . wpstg_delete_cache_objects(), 'wpstg' ),
					'type' => 'checkbox'
				),
                                
                                'debug_mode' => array(
					'id' => 'debug_mode',
					'name' => __( 'Debug mode', 'wpstg' ),
					'desc' => __( '<strong>Note: </strong> Check this box before you get in contact with our support team. This allows us to check publically hidden debug messages on your website. Do not forget to disable it thereafter! Enable this also to write daily sorted log files of requested share counts to folder <strong>/wp-content/plugins/wp-staging/logs</strong>. Please send us this files when you notice a wrong share count.' . wpstg_log_permissions(), 'wpstg' ),
					'type' => 'checkbox'
				)
                                
			)
		),
                'visual' => apply_filters('wpstg_settings_visual',
			array(
                            'style_header' => array(
					'id' => 'style_header',
					'name' => '<strong>' . __( 'Customize', 'wpstg' ) . '</strong>',
					'desc' => __( ' ', 'wpstg' ),
					'type' => 'header'
                                ),
				'wp-staging_round' => array(
					'id' => 'wp-staging_round',
					'name' => __( 'Round Shares', 'wpstg' ),
					'desc' => __( 'Share counts greater than 1.000 will be shown as 1k. Greater than 1 Million as 1M', 'wpstg' ),
					'type' => 'checkbox'
				),
                                'animate_shares' => array(
					'id' => 'animate_shares',
					'name' => __( 'Animate Shares', 'wpstg' ),
					'desc' => __( 'Count up the shares on page loading with a nice looking animation effect. This only works on singular pages and not with shortcodes generated buttons.', 'wpstg' ),
					'type' => 'checkbox'
				),
                                'sharecount_title' => array(
					'id' => 'sharecount_title',
					'name' => __( 'Share count title', 'wpstg' ),
					'desc' => __( 'Change the text of the Share count title. <strong>Default:</strong> SHARES', 'wpstg' ),
					'type' => 'text',
					'size' => 'medium',
                                        'std' => 'SHARES'
				),
				'wp-staging_hashtag' => array(
					'id' => 'wp-staging_hashtag',
					'name' => __( 'Twitter handle', 'wpstg' ),
					'desc' => __( '<strong>Optional:</strong> Using your twitter username, e.g. \'WP-Staging\' results in via @WP-Staging', 'wpstg' ),
					'type' => 'text',
					'size' => 'medium'
				),
                                /*'share_color' => array(
					'id' => 'share_color',
					'name' => __( 'Share count color', 'wpstg' ),
					'desc' => __( 'Choose color of the share number in hex format, e.g. #7FC04C: ', 'wpstg' ),
					'type' => 'text',
					'size' => 'medium',
                                        'std' => '#cccccc'
				),*/
                                'share_color' => array(
					'id' => 'share_color',
					'name' => __( 'Share count color', 'wpstg' ),
					'desc' => __( 'Choose color of the share number in hex format, e.g. #7FC04C: ', 'wpstg' ),
					'type' => 'text',
					'size' => 'medium',
                                        'std' => '#cccccc'
				),
                                'border_radius' => array(
					'id' => 'border_radius',
					'name' => __( 'Border Radius', 'wpstg' ),
					'desc' => __( 'Specify the border radius of all buttons in pixel. A border radius of 20px results in circle buttons. Default value is zero.', 'wpstg' ),
					'type' => 'select',
                                        'options' => array(
                                                0 => 0,
						1 => 1,
						2 => 2,
                                                3 => 3,
						4 => 4,
                                                5 => 5,
						6 => 6,
                                                7 => 7,
                                                8 => 8,
						9 => 9,
                                                10 => 10,
						11 => 11,
                                                12 => 12,
						13 => 13,
                                                14 => 14,
                                                15 => 15,
						16 => 16,
                                                17 => 17,
						18 => 18,
                                                19 => 19,
						20 => 20,
                                                'default' => 'default'
					),
                                        'std' => 'default'
					
				),
                                array(
                                        'id' => 'button_width',
                                        'name' => __( 'Button width', 'mashpv' ),
                                        'desc' => __( 'Minimum with of the large share buttons in pixels', 'mashpv' ),
                                        'type' => 'number',
                                        'size' => 'normal',
                                        'std' => '177'
                                ), 
                                'mash_style' => array(
					'id' => 'mash_style',
					'name' => __( 'Share button style', 'wpstg' ),
					'desc' => __( 'Change visual appearance of the share buttons.', 'wpstg' ),
					'type' => 'select',
                                        'options' => array(
						'shadow' => 'Shadowed buttons',
                                                'gradiant' => 'Gradient colored buttons',
                                                'default' => 'Clean buttons - no effects'
					),
                                        'std' => 'default'
					
				),
                                'small_buttons' => array(
					'id' => 'small_buttons',
					'name' => __( 'Use small buttons', 'wpstg' ),
					'desc' => __( 'All buttons will be shown as pure small icons without any text on desktop and mobile devices all the time.<br><strong>Note:</strong> Disable this when you use the <a href="https://www.wp-staging.net/downloads/wp-staging-responsive/" target="_blank">responsive Add-On</a>', 'wpstg' ),
					'type' => 'checkbox'
				),
                                'subscribe_behavior' => array(
					'id' => 'subscribe_behavior',
					'name' => __( 'Subscribe button', 'wpstg' ),
					'desc' => __( 'Specify if the subscribe button is opening a content box below the button or if the button is linked to the "subscribe url" below.', 'wpstg' ),
					'type' => 'select',
                                        'options' => array(
						'content' => 'Open content box',
                                                'link' => 'Open Subscribe Link'
					),
                                        'std' => 'content'
					
				),
                                'subscribe_link' => array(
					'id' => 'subscribe_link',
					'name' => __( 'Subscribe URL', 'wpstg' ),
					'desc' => __( 'Link the Subscribe button to this URL. This can be the url to your subscribe page, facebook fanpage, RSS feed etc. e.g. http://yoursite.com/subscribe', 'wpstg' ),
					'type' => 'text',
					'size' => 'regular',
                                        'std' => ''
				),
                                /*'subscribe_content' => array(
					'id' => 'subscribe_content',
					'name' => __( 'Subscribe content', 'wpstg' ),
					'desc' => __( '<br>Define the content of the opening toggle subscribe window here. Use formulars, like button, links or any other text. Shortcodes are supported, e.g.: [contact-form-7]', 'wpstg' ),
					'type' => 'textarea',
					'textarea_rows' => '3',
                                        'size' => 15
				),*/
                                'additional_content' => array(
					'id' => 'additional_content',
					'name' => __( 'Additional Content', 'wpstg' ),
					'desc' => __( '', 'wpstg' ),
					'type' => 'add_content',
                                        'options' => array(
                                            'box1' => array(
                                                'id' => 'content_above',
                                                'name' => __( 'Content Above', 'wpstg' ),
                                                'desc' => __( 'Content appearing above share buttons. Use HTML, formulars, like button, links or any other text. Shortcodes are supported, e.g.: [contact-form-7]', 'wpstg' ),
                                                'type' => 'textarea',
                                                'textarea_rows' => '3',
                                                'size' => 15
                                                ),
                                            'box2' => array(
                                                'id' => 'content_below',
                                                'name' => __( 'Content Below', 'wpstg' ),
                                                'desc' => __( 'Content appearing below share buttons.  Use HTML, formulars, like button, links or any other text. Shortcodes are supported, e.g.: [contact-form-7]', 'wpstg' ),
                                                'type' => 'textarea',
                                                'textarea_rows' => '3',
                                                'size' => 15
                                                ),
                                            'box3' => array(
                                                'id' => 'subscribe_content',
                                                'name' => __( 'Subscribe content', 'wpstg' ),
                                                'desc' => __( 'Define the content of the opening toggle subscribe window here. Use formulars, like button, links or any other text. Shortcodes are supported, e.g.: [contact-form-7]', 'wpstg' ),
                                                'type' => 'textarea',
                                                'textarea_rows' => '3',
                                                'size' => 15
                                                )
                                        )
				),                   
                                'custom_css' => array(
					'id' => 'custom_css',
					'name' => __( 'Custom CSS', 'wpstg' ),
					'desc' => __( '<br>Use WP-Staging custom styles here', 'wpstg' ),
					'type' => 'textarea',
					'size' => 15
                                        
				),
                                'location_header' => array(
					'id' => 'location_header',
					'name' => '<strong>' . __( 'Location & Position', 'wpstg' ) . '</strong>',
					'desc' => __( ' ', 'wpstg' ),
					'type' => 'header'
                                ),
                                'wp-staging_position' => array(
					'id' => 'wp-staging_position',
					'name' => __( 'Position', 'wpstg' ),
					'desc' => __( 'Location of Share Buttons. Set to <i>manual</i> if you do not want to use the automatic embeding. Use the shortcode function to place WP-Staging directly into your theme template files: <strong>&lt;?php echo do_shortcode("[wp-staging]"); ?&gt;</strong> or the content shortcode: [wp-staging] for posts and pages. See all <a href="https://www.wp-staging.net/faq/#Is_there_a_shortcode_for_pages_and_posts" target="_blank">available shortcodes</a> here.', 'wpstg' ),
					'type' => 'select',
                                        'options' => array(
						'before' => __( 'Top', 'wpstg' ),
						'after' => __( 'Bottom', 'wpstg' ),
                                                'both' => __( 'Top and Bottom', 'wpstg' ),
						'manual' => __( 'Manual', 'wpstg' )
					)
					
				),
                                'post_types' => array(
					'id' => 'post_types',
					'name' => __( 'Post Types', 'wpstg' ),
					'desc' => __( 'Select on which post_types the share buttons appear. This values will be ignored when position is specified "manual".', 'wpstg' ),
					'type' => 'posttypes'
				),
                                'singular' => array(
					'id' => 'singular',
					'name' => __( 'Categories', 'wpstg' ),
					'desc' => __('Enable this checkbox to enable WP-Staging on categories with multiple blogposts. <strong>Note: </strong> Post_types: "Post" must be enabled.','wpstg'),
					'type' => 'checkbox',
                                        'std' => '0'
				),
				'frontpage' => array(
					'id' => 'frontpage',
					'name' => __( 'Frontpage', 'wpstg' ),
					'desc' => __('Enable share buttons on frontpage','wpstg'),
					'type' => 'checkbox'
				),
                                /*'current_url' => array(
					'id' => 'current_url',
					'name' => __( 'Current Page URL', 'wpstg' ),
					'desc' => __('Force sharing the current page on non singular pages like categories with multiple blogposts','wpstg'),
					'type' => 'checkbox'
				),*/
                                'twitter_popup' => array(
					'id' => 'twitter_popup',
					'name' => __( 'Twitter Popup disable', 'wpstg' ),
					'desc' => __('Check this box if your twitter popup is openening twice. This happens when you are using any third party twitter instance on your website.','wpstg'),
					'type' => 'checkbox',
                                        'std' => '0'
                                    
				),
                                /*'wpstg_shortcode_info' => array(
					'id' => 'wpstg_shortcode_info',
					'name' => __( 'Note:', 'wpstg' ),
					'desc' => __('Using the shortcode <strong>[wp-staging]</strong> forces loading of dependacy scripts and styles on specific pages. It is overwriting any other location setting.','wpstg'),
					'type' => 'note',
                                        'label_for' => 'test'
                                    
				),*/
                                
                        )
		),
                 'networks' => apply_filters( 'wpstg_settings_networks',
                         array(
                                'services_header' => array(
					'id' => 'services_header',
					'name' => __( 'Select available networks', 'wpstg' ),
					'desc' => '',
					'type' => 'header'
				),
                                'visible_services' => array(
					'id' => 'visible_services',
					'name' => __( 'Large Buttons', 'wpstg' ),
					'desc' => __( 'Specify how many services and social networks are visible before the "Plus" Button is shown. This buttons turn into large prominent buttons.', 'wpstg' ),
					'type' => 'select',
                                        'options' => numberServices()
				),
                                'networks' => array(
					'id' => 'networks',
					'name' => '<strong>' . __( 'Services', 'wpstg' ) . '</strong>',
					'desc' => __('Drag and drop the Share Buttons to sort them and specify which ones should be enabled. If you enable more services than the specified value "Large Buttons", the plus sign is automatically added to the last visible big share button.<br><strong>No Share Services visible after update?</strong> Disable and enable the WP-Staging Plugin solves this. ','wpstg'),
					'type' => 'networks',
                                        'options' => wpstg_get_networks_list()
                                 )
                         )
                ),
		'licenses' => apply_filters('wpstg_settings_licenses',
			array('licenses_header' => array(
					'id' => 'licenses_header',
					'name' => __( 'Activate your Add-Ons', 'wpstg' ),
					'desc' => '',
					'type' => 'header'
				),)
		),
                'extensions' => apply_filters('wpstg_settings_extension',
			array()
		),
                'addons' => apply_filters('wpstg_settings_addons',
			array(
                                'addons' => array(
					'id' => 'addons',
					'name' => __( '', 'wpstg' ),
					'desc' => __( '', 'wpstg' ),
					'type' => 'addons'
				)
                            //wpstg_addons_callback()
                        )
		)
	);

	return $wpstg_settings;
}

/**
 * Settings Sanitization
 *
 * Adds a settings error (for the updated message)
 * At some point this will validate input
 *
 * @since 1.0.0
 *
 * @param array $input The value input in the field
 *
 * @return string $input Sanitized value
 */
function wpstg_settings_sanitize( $input = array() ) {

	global $wpstg_options;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = wpstg_get_registered_settings();
	$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';

	$input = $input ? $input : array();
	$input = apply_filters( 'wpstg_settings_' . $tab . '_sanitize', $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {

		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[$tab][$key]['type'] ) ? $settings[$tab][$key]['type'] : false;

		if ( $type ) {
			// Field type specific filter
			$input[$key] = apply_filters( 'wpstg_settings_sanitize_' . $type, $value, $key );
		}

		// General filter
		$input[$key] = apply_filters( 'wpstg_settings_sanitize', $value, $key );
	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	if ( ! empty( $settings[$tab] ) ) {
		foreach ( $settings[$tab] as $key => $value ) {

			// settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
			if ( is_numeric( $key ) ) {
				$key = $value['id'];
			}

			if ( empty( $input[$key] ) ) {
				unset( $wpstg_options[$key] );
			}

		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $wpstg_options, $input );

	add_settings_error( 'wpstg-notices', '', __( 'Settings updated.', 'wpstg' ), 'updated' );

	return $output;
}

/**
 * DEPRECATED Misc Settings Sanitization
 *
 * @since 1.0
 * @param array $input The value inputted in the field
 * @return string $input Sanitizied value
 */
/*function wpstg_settings_sanitize_misc( $input ) {

	global $wpstg_options;*/

	/*if( wpstg_get_file_download_method() != $input['download_method'] || ! wpstg_htaccess_exists() ) {
		// Force the .htaccess files to be updated if the Download method was changed.
		wpstg_create_protection_files( true, $input['download_method'] );
	}*/

	/*if( ! empty( $input['enable_sequential'] ) && ! wpstg_get_option( 'enable_sequential' ) ) {

		// Shows an admin notice about upgrading previous order numbers
		WPSTG()->session->set( 'upgrade_sequential', '1' );

	}*/

	/*return $input;
}
add_filter( 'wpstg_settings_misc_sanitize', 'wpstg_settings_sanitize_misc' );
         * */

/**
 * Sanitize text fields
 *
 * @since 1.8
 * @param array $input The field value
 * @return string $input Sanitizied value
 */
function wpstg_sanitize_text_field( $input ) {
	return trim( $input );
}
add_filter( 'wpstg_settings_sanitize_text', 'wpstg_sanitize_text_field' );

/**
 * Retrieve settings tabs
 *
 * @since 1.8
 * @param array $input The field value
 * @return string $input Sanitizied value
 */
function wpstg_get_settings_tabs() {

	$settings = wpstg_get_registered_settings();

	$tabs             = array();
	$tabs['general']  = __( 'General', 'wpstg' );

        if( ! empty( $settings['visual'] ) ) {
		$tabs['visual'] = __( 'Visual', 'wpstg' );
	} 
        
        if( ! empty( $settings['networks'] ) ) {
		$tabs['networks'] = __( 'Social Networks', 'wpstg' );
	}  
        
	if( ! empty( $settings['extensions'] ) ) {
		$tabs['extensions'] = __( 'Extensions', 'wpstg' );
	}
	
	if( ! empty( $settings['licenses'] ) ) {
		$tabs['licenses'] = __( 'Licenses', 'wpstg' );
	}
        $tabs['addons'] = __( 'Add-Ons', 'wpstg' );

	//$tabs['misc']      = __( 'Misc', 'wpstg' );

	return apply_filters( 'wpstg_settings_tabs', $tabs );
}

       /*
	* Retrieve a list of possible expire cache times
	*
	* @since  2.0.0
	* @change 
	*
	* @param  array  $methods  Array mit verfügbaren Arten
	*/

        function wpstg_get_expiretimes()
	{
		/* Defaults */
        $times = array(
        '300' => 'in 5 minutes',
        '600' => 'in 10 minutes',
        '1800' => 'in 30 minutes',
        '3600' => 'in 1 hour',
        '21600' => 'in 6 hours',
        '43200' => 'in 12 hours',
        '86400' => 'in 24 hours'
        );
            return $times;
	}
   

/**
 * Retrieve array of  social networks Facebook / Twitter / Subscribe
 *
 * @since 2.0.0
 * 
 * @return array Defined social networks
 */
function wpstg_get_networks_list() {

        $networks = get_option('wpstg_networks');
	return apply_filters( 'wpstg_get_networks_list', $networks );
}

/**
 * Header Callback
 *
 * Renders the header.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @return void
 */
function wpstg_header_callback( $args ) {
	//echo '<hr/>';
        echo '&nbsp';
}

/**
 * Checkbox Callback
 *
 * Renders checkboxes.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_checkbox_callback( $args ) {
	global $wpstg_options;

	$checked = isset( $wpstg_options[ $args[ 'id' ] ] ) ? checked( 1, $wpstg_options[ $args[ 'id' ] ], false ) : '';
	$html = '<input type="checkbox" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}


/**
 * Multicheck Callback
 *
 * Renders multiple checkboxes.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_multicheck_callback( $args ) {
	global $wpstg_options;

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ):
			if( isset( $wpstg_options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
			echo '<input name="wpstg_settings[' . $args['id'] . '][' . $key . ']" id="wpstg_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
			echo '<label for="wpstg_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;
		echo '<p class="description wpstg_hidden">' . $args['desc'] . '</p>';
	}
}

/**
 * Radio Callback
 *
 * Renders radio boxes.
 *
 * @since 1.3.3
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_radio_callback( $args ) {
	global $wpstg_options;

	foreach ( $args['options'] as $key => $option ) :
		$checked = false;

		if ( isset( $wpstg_options[ $args['id'] ] ) && $wpstg_options[ $args['id'] ] == $key )
			$checked = true;
		elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $wpstg_options[ $args['id'] ] ) )
			$checked = true;

		echo '<input name="wpstg_settings[' . $args['id'] . ']"" id="wpstg_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
		echo '<label for="wpstg_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
	endforeach;

	echo '<p class="description wpstg_hidden">' . $args['desc'] . '</p>';
}

/**
 * Gateways Callback
 *
 * Renders gateways fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_gateways_callback( $args ) {
	global $wpstg_options;

	foreach ( $args['options'] as $key => $option ) :
		if ( isset( $wpstg_options['gateways'][ $key ] ) )
			$enabled = '1';
		else
			$enabled = null;

		echo '<input name="wpstg_settings[' . $args['id'] . '][' . $key . ']"" id="wpstg_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="1" ' . checked('1', $enabled, false) . '/>&nbsp;';
		echo '<label for="wpstg_settings[' . $args['id'] . '][' . $key . ']">' . $option['admin_label'] . '</label><br/>';
	endforeach;
}

/**
 * Dropdown Callback (drop down)
 *
 * Renders gateways select menu
 *
 * @since 1.5
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_gateway_select_callback($args) {
	global $wpstg_options;

	echo '<select name="wpstg_settings[' . $args['id'] . ']"" id="wpstg_settings[' . $args['id'] . ']">';

	foreach ( $args['options'] as $key => $option ) :
		$selected = isset( $wpstg_options[ $args['id'] ] ) ? selected( $key, $wpstg_options[$args['id']], false ) : '';
		echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $option['admin_label'] ) . '</option>';
	endforeach;

	echo '</select>';
	echo '<label for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';
}

/**
 * Text Callback
 *
 * Renders text fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_text_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label class="wpstg_hidden" class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Number Callback
 *
 * Renders number fields.
 *
 * @since 1.9
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_number_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Textarea Callback
 *
 * Renders textarea fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_textarea_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : '40';
	$html = '<textarea class="large-text wpstg-textarea" cols="50" rows="' . $size . '" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Password Callback
 *
 * Renders password fields.
 *
 * @since 1.3
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_password_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . $size . '-text" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @since 1.3.1
 * @param array $args Arguments passed by the setting
 * @return void
 */
function wpstg_missing_callback($args) {
	printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'wpstg' ), $args['id'] );
}

/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_select_callback($args) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<select id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Color select Callback
 *
 * Renders color select fields.
 *
 * @since 2.1.2
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
/*function wpstg_color_select_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<select id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $color ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}*/

function wpstg_color_select_callback( $args ) {
	global $wpstg_options;
        
        if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<strong>#:</strong><input type="text" style="max-width:80px;border:1px solid #' . esc_attr( stripslashes( $value ) ) . ';border-right:20px solid #' . esc_attr( stripslashes( $value ) ) . ';" id="wpstg_settings[' . $args['id'] . ']" class="medium-text ' . $args['id'] . '" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';

	$html .= '</select>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Rich Editor Callback
 *
 * Renders rich editor fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @global $wp_version WordPress Version
 */
function wpstg_rich_editor_callback( $args ) {
	global $wpstg_options, $wp_version;
	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		ob_start();
		wp_editor( stripslashes( $value ), 'wpstg_settings_' . $args['id'], array( 'textarea_name' => 'wpstg_settings[' . $args['id'] . ']', 'textarea_rows' => $args['textarea_rows'] ) );
		$html = ob_get_clean();
	} else {
		$html = '<textarea class="large-text wpstg-richeditor" rows="10" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Upload Callback
 *
 * Renders upload fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_upload_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[$args['id']];
	else
		$value = isset($args['std']) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text wpstg_upload_field" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="wpstg_settings_upload_button button-secondary" value="' . __( 'Upload File', 'wpstg' ) . '"/></span>';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}


/**
 * Color picker Callback
 *
 * Renders color picker fields.
 *
 * @since 1.6
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
function wpstg_color_callback( $args ) {
	global $wpstg_options;

	if ( isset( $wpstg_options[ $args['id'] ] ) )
		$value = $wpstg_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="wpstg-color-picker" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}


/**
 * Registers the license field callback for Software Licensing
 *
 * @since 1.5
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */
if ( ! function_exists( 'wpstg_license_key_callback' ) ) {
	function wpstg_license_key_callback( $args ) {
		global $wpstg_options;

		if ( isset( $wpstg_options[ $args['id'] ] ) )
			$value = $wpstg_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';

		if ( 'valid' == get_option( $args['options']['is_valid_license_option'] ) ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License',  'wpstg' ) . '"/>';
                        $html .= '<span style="font-weight:bold;color:green;"> License key activated! </span> <p style="color:green;font-size:13px;"> You´ll get updates for this Add-On automatically!</p>';
                } else {
                    $html .= '<span style="color:red;"> License key not activated!</span style=""><p style="font-size:13px;font-weight:bold;">You´ll get no important security and feature updates for this Add-On!</p>';
                }
		$html .= '<label for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

                wp_nonce_field( $args['id'] . '-nonce', $args['id'] . '-nonce' );
                
		echo $html;
	}
}


/**
 * Networks Callback / Facebook, Twitter and Subscribe default
 *
 * Renders network order table. Uses separate option field 'wpstg_networks 
 *
 * @since 2.0.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the wpstg Options
 * @return void
 */

function wpstg_networks_callback( $args ) {
	global $wpstg_options;
       /* Array in $wpstg_option['networks']
        * 
        *                                   array(
                                                0 => array (
                                                    'status' => '1',
                                                    'name' => 'Share on Facebook',
                                                    'name2' => 'Share'
                                                ), 
                                                1 => array (
                                                    'status' => '1',
                                                    'name' => 'Tweet on Twitter',
                                                    'name2' => 'Twitter'
                                                ),
                                                2 => array (
                                                    'status' => '1',
                                                    'name' => 'Subscribe to us',
                                                    'name2' => 'Subscribe'
                                                )
                                            )
        */

       ob_start();
        ?>
        <p class="description"><?php echo $args['desc']; ?></p>
        <table id="wpstg_network_list" class="wp-list-table fixed posts">
		<thead>
			<tr>
				<th scope="col" style="padding: 15px 10px;"><?php _e( 'Social Networks', 'wpstg' ); ?></th>
                                <th scope="col" style="padding: 15px 10px;"><?php _e( 'Enable', 'wpstg' ); ?></th>
                                <th scope="col" style="padding: 15px 10px;"><?php _e( 'Custom name', 'wpstg' ); ?></th>
			</tr>
		</thead>        
        <?php

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ):
                        echo '<tr id="wpstg_list_' . $key . '" class="wpstg_list_item">';
			if( isset( $wpstg_options[$args['id']][$key]['status'] ) ) { $enabled = 1; } else { $enabled = NULL; }
                        if( isset( $wpstg_options[$args['id']][$key]['name'] ) ) { $name = $wpstg_options[$args['id']][$key]['name']; } else { $name = NULL; }

                        echo '<td class="mashicon-' . strtolower($option) . '"><span class="icon"></span><span class="text">' . $option . '</span></td>
                        <td><input type="hidden" name="wpstg_settings[' . $args['id'] . '][' . $key . '][id]" id="wpstg_settings[' . $args['id'] . '][' . $key . '][id]" value="' . strtolower($option) .'"><input name="wpstg_settings[' . $args['id'] . '][' . $key . '][status]" id="wpstg_settings[' . $args['id'] . '][' . $key . '][status]" type="checkbox" value="1" ' . checked(1, $enabled, false) . '/><td>
                        <input type="text" class="medium-text" id="wpstg_settings[' . $args['id'] . '][' . $key . '][name]" name="wpstg_settings[' . $args['id'] . '][' . $key . '][name]" value="' . $name .'"/>
                        </tr>';
                endforeach;
	}
        echo '</table>';
        echo ob_get_clean();
}

/**
 * Registers the Add-Ons field callback for WP-Staging Add-Ons
 *
 * @since 2.0.5
 * @param array $args Arguments passed by the setting
 * @return html
 */
function wpstg_addons_callback( $args ) {
	$html = wpstg_add_ons_page();
	echo $html;
}

/**
 * Registers the image upload field
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $wpstg_options Array of all the WPSTG Options
 * @return void
 */

	function wpstg_upload_image_callback( $args ) {
		global $wpstg_options;

		if ( isset( $wpstg_options[ $args['id'] ] ) )
			$value = $wpstg_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text ' . $args['id'] . '" id="wpstg_settings[' . $args['id'] . ']" name="wpstg_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	
		$html .= '<input type="submit" class="button-secondary wpstg_upload_image" name="' . $args['id'] . '_upload" value="' . __( 'Select Image',  'wpstg' ) . '"/>';
		
		$html .= '<label class="wpstg_hidden" for="wpstg_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}
        
        
/* 
 * Post Types Callback
 * 
 * Adds a multiple choice drop box
 * for selecting where WP-Staging should be enabled
 * 
 * @since 2.0.9
 * @param array $args Arguments passed by the setting
 * @return void
 * 
 */

function wpstg_posttypes_callback ($args){
  global $wpstg_options;
  $posttypes = get_post_types();

  //if ( ! empty( $args['options'] ) ) {
  if ( ! empty( $posttypes ) ) {
		//foreach( $args['options'] as $key => $option ):
                foreach( $posttypes as $key => $option ):
			if( isset( $wpstg_options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
			echo '<input name="wpstg_settings[' . $args['id'] . '][' . $key . ']" id="wpstg_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
			echo '<label for="wpstg_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;
		echo '<p class="description wpstg_hidden">' . $args['desc'] . '</p>';
	}
}

/* 
 * Note Callback
 * 
 * Show a note
 * 
 * @since 2.2.8
 * @param array $args Arguments passed by the setting
 * @return void
 * 
 */

function wpstg_note_callback ($args){
  global $wpstg_options;
  //$html = !empty($args['desc']) ? $args['desc'] : '';
  $html = '';
  echo $html;
}

/**
 * Additional content Callback 
 * Adds several content text boxes selectable via jQuery easytabs() 
 *
 * @param array $args
 * @return string $html
 * @scince 2.3.2
 */

function wpstg_add_content_callback($args){
    	global $wpstg_options;

        $html = '<div id="mashtabcontainer" class="tabcontent_container"><ul class="mashtabs" style="width:99%;max-width:500px;">';
            foreach ( $args['options'] as $option => $name ) :
                    $html .= '<li class="mashtab" style="float:left;margin-right:4px;"><a href="#'.$name['id'].'">'.$name['name'].'</a></li>';
            endforeach;
        $html .= '</ul>';
        $html .= '<div class="mashtab-container">';
            foreach ( $args['options'] as $option => $name ) :
                    $value = isset($wpstg_options[$name['id']]) ? $wpstg_options[ $name['id']] : '';
                    $textarea = '<textarea class="large-text wpstg-textarea" cols="50" rows="15" id="wpstg_settings['. $name['id'] .']" name="wpstg_settings['.$name['id'].']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
                    $html .= '<div id="'.$name['id'].'" style="max-width:500px;"><span style="padding-top:60px;display:block;">' . $name['desc'] . ':</span><br>' . $textarea . '</div>';
            endforeach;
        $html .= '</div>';
        $html .= '</div>';
	echo $html;
}

        
/**
 * Hook Callback
 *
 * Adds a do_action() hook in place of the field
 *
 * @since 1.0.8.2
 * @param array $args Arguments passed by the setting
 * @return void
 */
function wpstg_hook_callback( $args ) {
	do_action( 'wpstg_' . $args['id'] );
}

/**
 * Set manage_options as the cap required to save WPSTG settings pages
 *
 * @since 1.9
 * @return string capability required
 */
function wpstg_set_settings_cap() {
	return 'manage_options';
}
add_filter( 'option_page_capability_wpstg_settings', 'wpstg_set_settings_cap' );


/* returns array with amount of available services
 * @since 2.0
 * @return array
 */

function numberServices(){
    $number = 1;
    $array = array();
    while ($number <= count(wpstg_get_networks_list())){
        $array[] = $number++; 

    }
    $array['all'] = __('All Services');
    return apply_filters('wpstg_return_services', $array);
}

/* Purge the WP-Staging 
 * database WPSTG_TABLE
 * 
 * @since 2.0.4
 * @return string
 */

function wpstg_delete_cache_objects(){
    global $wpstg_options, $wpdb;
    if (isset($wpstg_options['delete_cache_objects'])){
        //$sql = "TRUNCATE TABLE " . WPSTG_TABLE;
        //require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        //$wpdb->query($sql);
        delete_post_meta_by_key( 'wpstg_timestamp' );
        delete_post_meta_by_key( 'wpstg_shares' ); 
        delete_post_meta_by_key( 'wpstg_jsonshares' );
        return ' <strong style="color:red;">' . __('DB cache deleted! Do not forget to uncheck this box for performance increase after doing the job.', 'wpstg') . '</strong> ';
    }
}

/* returns Cache Status if enabled or disabled
 *
 * @since 2.0.4
 * @return string
 */

function wpstg_cache_status(){
    global $wpstg_options;
    if (isset($wpstg_options['disable_cache'])){
        return ' <strong style="color:red;">' . __('Transient Cache disabled! Enable it for performance increase.' , 'wpstg') . '</strong> ';
    }
}

/* Permission check if logfile is writable
 *
 * @since 2.0.6
 * @return string
 */

function wpstg_log_permissions(){
    global $wpstg_options;
    if (!WPSTG()->logger->checkDir() ){
        return '<br><strong style="color:red;">' . __('Log file directory not writable! Set FTP permission to 755 or 777 for /wp-content/plugins/wp-staging/logs/', 'wpstg') . '</strong> <br> Read here more about <a href="http://codex.wordpress.org/Changing_File_Permissions" target="_blank">file permissions</a> ';
    }
}
