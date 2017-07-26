<?php

namespace WPStaging\Backend\Pluginmeta;

/*
 *  Admin Plugins Meta Data
 */

// No Direct Access
if( !defined( "WPINC" ) ) {
    die;
}

use WPStaging\WPStaging;


class Pluginmeta {
    
    public function __construct() {
        $this->defineHooks();
    }

    /**
     * Define Hooks
     */
    public function defineHooks() {
        add_filter( 'plugin_row_meta', array($this, 'rowMeta'), 10, 2 );
        add_filter( 'plugin_action_links', array($this,'actionLinks'), 10, 2 );

    }

    /**
     * Plugins row action links
     *
     * @author Michael Cannon <mc@aihr.us>
     * @since 0.9.0
     * @param array $links already defined action links
     * @param string $file plugin file path and name being processed
     * @return array $links
     */
    public function actionLinks( $links, $file ) {
        $settings_link = '<a href="' . admin_url( 'admin.php?page=wpstg-settings' ) . '">' . esc_html__( 'Settings', 'wpstg' ) . '</a>';
        if( $file == 'wp-staging/wp-staging.php' || $file == 'wp-staging-pro/wp-staging-pro.php')
            array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * Plugin row meta links
     *
     * @author Michael Cannon <mc@aihr.us>
     * @since 2.0
     * @param array $input already defined meta links
     * @param string $file plugin file path and name being processed
     * @return array $input
     */
    public function rowMeta( $input, $file ) {
        if( $file != 'wp-staging/wp-staging.php' && $file != 'wp-staging-pro/wp-staging-pro.php'){
            return $input;
        }

        $links = array(
            '<a href="' . admin_url( 'admin.php?page=wpstg_clone' ) . '">' . esc_html__( 'Start Now', 'wpstg' ) . '</a>',
        );
        $input = array_merge( $input, $links );
        return $input;
    }

}
