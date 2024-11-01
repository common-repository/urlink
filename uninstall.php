<?php
/**
 * Uninstall function
 *
 * @since 0.0.1
 */

 
// Exit if this file is not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

// Delete plugin options from options table
delete_option( URLINK_SLUG );
delete_option( 'urlink_skip_notice' );

// Delete all post meta by key from postmeta table
delete_post_meta_by_key( '_urlink' );
delete_post_meta_by_key( '_urlink_clicks' );
?>