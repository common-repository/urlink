<?php
/*
    Class: Meta
    
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

class Urlink_Admin {
	
	var $textdomain;
	
	function __construct() {
		$this->post_type = 'link';
		$this->textdomain = URLINK_SLUG;
		add_filter( 'manage_link_posts_columns', array( &$this, 'posts_columns' ) );
		
		add_action( 'admin_notices', array( &$this, 'admin_notice' ) );
		add_action( 'admin_footer', array( &$this, 'admin_footer' ) );
		add_action( 'wp_ajax_urlink_admin', array( &$this, 'admin_ajax' ) );
		add_action( 'manage_posts_custom_column' , array( &$this, 'custom_columns' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}
	
	
	/**
	 * Check if Link Manager is activated
	 * @since 0.0.1
	 */	
	function admin_notice() {
		if ( get_option( 'urlink_skip_notice' ) )
			return;
		
		global $menu;
		$link_manager = false;
		
		foreach ( $menu as $prop )
			if ( 'link-manager.php' == $prop[2] )
				$link_manager = true;

		$class = 'notice notice-warning is-dismissible';
		$message = '<strong>Urlink</strong>: ' . 
			__( sprintf( 'It seems the Link Manger is enable. To disable it please read this %s.', '<a href="https://codex.wordpress.org/Links_Manager#Removing_the_Link_Manager" target="_blank">documentation</a>'), $this->textdomain );		

		if ( $link_manager )	
			printf( '<div id="urlink-notice" class="%1$s"><p>%2$s</p></div>', $class, $message ); 
	}

	/**
	 * Admin AJAX
	 * @since 0.0.1
	 */
	function admin_ajax() {
		check_ajax_referer( URLINK_SLUG, nonce );
		update_option( 'urlink_skip_notice', true );
		exit;
	}	
	
	
	function admin_footer() {
		$nonce = wp_create_nonce( URLINK_SLUG );
		echo "<script>
            (function( $ ) {
                var _nonce = '$nonce';
				console.log( $( '#urlink-notice' ) );
				$( 'button, #urlink-notice ' ).on( 'click', function() {
					$.post( ajaxurl, { action: 'urlink_admin', nonce: _nonce }, function( data ){
						$( '#urlink-notice' ).slideUp();
					});
                });
            })( jQuery );
        </script>";
	}

	
	function posts_columns( $columns ) {
		$columns["clicks"] = __( 'Clicks', $this->textdomain );
		$columns["url"] = __( 'Link', $this->textdomain );
		return $columns;
	}
	

	function custom_columns( $column, $post_id ) {		
		switch ( $column ) {
			case 'clicks':
				$clicks = get_post_meta( $post_id, '_urlink_clicks', true );
				echo $clicks;
				break;
				
			case 'url':
				$meta = get_post_meta( $post_id, '_urlink', true );
				$icon = $meta['redirect'] ? 'dashicons-randomize' : 'dashicons-external';
				echo "<a href='{$meta['url']}' target='_blank'><span class='dashicons $icon'></span>{$meta['url']}</a>";
				break;
		}
	}
	
	
	/**
	 * Enqueue admin scripts and styles for specific admin pages
	 * @since 0.0.1
	 */		
	function enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post-new.php', 'post.php', 'widgets.php' ) ) )
			return;

		wp_enqueue_style( "link-dialog", plugin_dir_url( __FILE__ ) . 'css/dialog.css', null, URLINK_VERSION );
		wp_enqueue_script( "link-dialog", plugin_dir_url( __FILE__ ) . 'js/jquery.dialog.js', array( 'jquery' ), URLINK_VERSION, false );
	}

} new Urlink_Admin();


/**
 * Enqueue admin scripts and styles for specific admin pages
 * @since 0.0.1
 */	
function urlink_template_dialog( $fields, $values, $field_name, $field_id ) {
	$html = '';
	
	foreach( $fields as $k => $field ) {
		$name = $field_name."[$k]";
		$id = "$field_id-$k";
		
		$field = wp_parse_args( (array) $field, array(
			'type' => 'text',
			'options' => array(),
			'default' => '',
			'label' => '',
			'description' => '',
		));		
		extract( $field ); // extract
		
		$html .= "<li>";
		
		if ( $label )
			$html .= "<label for='$id'>$label</label>";
		
		if ( $description )
			$html .= "<span class='description'>$description</span>";

		$value = isset( $values[$k] ) ? $values[$k] : $default;
		
		switch ( $type ) :
			case 'number':
			case 'text':
			default:				
				$class = isset( $class ) ? $class : 'smallfat';
				$html .= "<input class='$class' id='$id' name='$name' type='$type' value='$value' />";
				break;
				
			case 'select':
				$html .= "<select id='$id' name='$name'>";
					foreach ( $options as $key => $option ) {
						$selected = selected( $value, $key, false );
						$html .= "<option value='$key' $selected>$option</option>";
					}
				$html .= "</select>";				
				break;
				
			case 'checkbox':
				$checked = checked( $value, true, false );
				$html .= "<input class='checkbox' type='checkbox' $checked id='$id' name='$name' />";				
				break;
				
		endswitch;	

		$html .= '</li>';
	}

	echo $html;
}