<?php
/**
 * Plugin Name: WordPress Development Kit Plugin
 * Plugin URI: https://wordpress.storelocatorplus.com/product/wordpress-development-kit-plugin/
 * Description: A plugin that works with my WP Dev Kit, plugins.json in particular, to render product and plugin metadata on a WordPress page or post.
 * Author: Store Locator Plus
 * Author URI: https://wordpress.storelocatorplus.com/
 * Requires at least: 4.4
 * Tested up to : 4.7
 * Version: 4.7.1
 *
 * Text Domain: wp-dev-kit
 * Domain Path: /languages/
 *
 */

// Check WP Version
//
global $wp_version;
if ( version_compare( $wp_version, '4.4' , '<' ) ) {
    add_action(
        'admin_notices',
        create_function(
            '',
            "echo '<div class=\"error\"><p>".
            __( 'WordPress Dev Kit requires WordPress 4.4 to function properly. ' , 'wp-dev-kit' ) ,
            __( 'This plugin has been deactivated.'                               , 'wp-dev-kit' ) .
            __( 'Please upgrade WordPress.'                                       , 'wp-dev-kit' ) .
            "</p></div>';"
        )
    );
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    deactivate_plugins( plugin_basename( __FILE__ ) );
    return;
}

define( 'WPDK__VERSION'     ,   '4.7.1'                       );
define( 'WPDK__PLUGIN_DIR'  ,   plugin_dir_path( __FILE__ ) );
define( 'WPDK__PLUGIN_FILE' ,   __FILE__                    );

require_once( WPDK__PLUGIN_DIR . 'include/class.wpdk.php' );

register_activation_hook( WPDK__PLUGIN_FILE , array( 'wpdkPlugin' , 'plugin_activation' ) );

add_action( 'init'                                  , array( 'wpdkPlugin'   , 'init'            ) );
add_action( 'wp_ajax_wpdk_download_file'            , array( 'wpdkPlugin'   , 'download_file'   ) );
add_action( 'wp_ajax_nopriv_wpdk_download_file'     , array( 'wpdkPlugin'   , 'download_file'   ) );
add_action( 'wp_ajax_wpdk_updater'                  , array( 'wpdkPlugin'   , 'updater'         ) );
add_action( 'wp_ajax_nopriv_wpdk_updater'           , array( 'wpdkPlugin'   , 'updater'         ) );

