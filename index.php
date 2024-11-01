<?php
/*
    Plugin Name: Urlink
    Plugin URI: http://zourbuth.com
    Description: A complete plugin for your links
    Version: 0.0.1
    Author: zourbuth
    Author URI: http://zourbuth.com
    License: GPL2
    
	Copyright 2016 zourbuth.com (email: zourbuth@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/**
 * Exit if accessed directly
 * @since 0.0.1
 */
if ( ! defined( 'ABSPATH' ) )
	exit;


// Set constant
define( 'URLINK', true );
define( 'URLINK_VERSION', '0.0.1' );
define( 'URLINK_PATH', plugin_dir_path( __FILE__ ) );
define( 'URLINK_URL', plugin_dir_url( __FILE__ ) );
define( 'URLINK_NAME', 'Urlink' );
define( 'URLINK_SLUG', 'urlink' );
define( 'URLINK_TEXTDOMAIN', 'urlink' );


// Launch the plugin
register_activation_hook( __FILE__, 'urlink_activation_hook' );
add_action( 'plugins_loaded', 'urlink_plugin_loaded', 9 );


/**
 * Save plugin version on activation
 * @since 0.0.1
 */
function urlink_activation_hook() {
	add_option( 'urlink_version', URLINK_VERSION );
}


/**
 * Initializes the plugin and it's features
 * Load necessary plugin files and add action to widget init
 * @since 0.0.1
 */
function urlink_plugin_loaded() {
	require_once( URLINK_PATH . 'main.php' );
	require_once( URLINK_PATH . 'settings.php' );
	require_once( URLINK_PATH . 'post-type.php' );
	require_once( URLINK_PATH . 'meta.php' );
	require_once( URLINK_PATH . 'admin.php' );
	require_once( URLINK_PATH . 'dialog.php' );	
	require_once( URLINK_PATH . 'templates.php' );
	
	add_action( 'widgets_init', 'urlink_widgets_init' );
}


/**
 * Load widget files and register
 * @since 0.0.1
 */
function urlink_widgets_init() {
	require_once( URLINK_PATH . 'widget.php' );
	register_widget( 'Urlink_Widget' );
}
?>