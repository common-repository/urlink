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

class Urlink_Meta {
	
	var $post_type, $textdomain, $metas;
	
	function __construct() {
		$this->post_type = 'link';
		$this->textdomain = URLINK_SLUG;
		$this->attributes = array(
			'url' => array(
				'type' 		=> 'url',
				'default' 	=> ''
			),
			'redirect' => array(
				'type' 		=> 'checkbox',
				'default' 	=> true
			),
			'target' => array(
				'type' 		=> 'radio',
				'default' 	=> '_blank',
				'options' 	=> array(
					'_blank' => __( 'Open in new tab', $this->textdomain ),
					'_self'	 => __( 'Open in current tab', $this->textdomain )
				)
			),
		);		
		
		$this->advances = array(
			'tab' => array(
				'tab' 			=> 'general',
				'type' 			=> 'hidden',
				'default'		=> 'general',
				'class'			=> 'widefat',
			),
			'rel' => array(
				'tab' 			=> 'general',
				'type' 			=> 'text',
				'label'			=> __( 'Relationship', $this->textdomain ),
				'default'		=> '',
				'class'			=> 'widefat',
				'description'	=> __( 'Specifies the relationship between the current document and the linked document, e.q. <strong>nofollow</strong>', $this->textdomain ),
			),
			'anchor_class' => array(
				'tab' 			=> 'general',
				'type' 			=> 'text',
				'label'			=> __( 'Anchor Class', $this->textdomain ),
				'default'		=> '',
				'class'			=> 'widefat',
				'description'	=> __( 'Add custom class to the anchor link', $this->textdomain ),
			),
			'wrapper_class' => array(
				'tab' 			=> 'general',
				'type' 			=> 'text',
				'label'			=> __( 'Wrapper Class', $this->textdomain ),
				'default'		=> '',
				'class'			=> 'widefat',
				'description'	=> __( 'Add custom class for the anchor wrapper', $this->textdomain ),
			),
			
			'parameters' => array(
				'tab' 			=> 'custom',
				'type' 			=> 'textarea',
				'label'			=> __( 'Additional Parameters', $this->textdomain ),
				'default'		=> '',
				'description'	=> __( 'Use this field to add additional parameters separated by new line. Example: <code>key=value</code>', $this->textdomain ),
			),
		);
							
		$this->popularities = array(
			'_urlink_clicks' => array(
				'type' 			=> 'number',
				'label'			=> __( 'Clicks', $this->textdomain ),
				'default'		=> 0,
			),
		);
				
		add_action( 'edit_form_after_title', array( &$this, 'edit_form_after_title' ) );
		add_action( 'edit_form_after_editor', array( &$this, 'metabox_rearrange' ) );
		
		add_action( 'save_post',  array( &$this, 'save_metabox' ) );
		add_action( 'add_meta_boxes',  array( &$this, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ), 10, 1 );
	}
	
	
	/**
	 * Add metaboxes to the post type
	 * https://developer.wordpress.org/reference/functions/add_meta_box/
	 * add_meta_box ( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
	 * @since 0.0.1
	 */	
	function add_meta_boxes() {
		add_meta_box( 'url-metabox', __( 'Link', $this->textdomain ), array( &$this, 'attributes_callback' ), 'link', 'advanced', 'default' );		
		add_meta_box( 'urlink-metabox', __( 'Advanced', $this->textdomain ), array( &$this, 'metabox_callback' ), 'link', 'normal', 'high' );
		add_meta_box( 'popularity-metabox', __( 'Popularity', $this->textdomain ), array( &$this, 'popularity_callback' ), 'link', 'side', 'default' );
	}

	
	/**
	 * Exit if accessed directly
	 * @since 0.0.1
	 */	
	function attributes_callback() {
		global $post;
		$meta = get_post_meta( $post->ID, '_urlink', true );
		$meta = $this->parse_default( $meta );
		
		?>
		<p><input placeholder="<?php _e( 'Put the link here', $this->textdomain ); ?>" type="url" class="widefat" value="<?php echo $meta['url']; ?>" name="urlink[url]" /></p>			
		<div class="link-options">
			<label style="margin-right: 50px;"><input name="urlink[redirect]" type="checkbox" value="1" <?php checked( $meta['redirect'], true, true ); ?>> Use redirect</label>
			
			<?php foreach ( $this->attributes['target']['options'] as $key => $text ) : ?>
				<label class="target"><input name="urlink[target]" type="radio" value="<?php echo $key; ?>" <?php checked( $meta['target'], $key, true ); ?>> <?php echo $text; ?></label>
			<?php endforeach; ?>

			<input class="shortcode" type="text" value='[urlink id="<?php echo $post->ID; ?>"]' onClick="this.select();" readonly />
		</div>		
		<?php		
		echo '<input type="hidden" name="urlink_nonce" value="' . wp_create_nonce( 'urlink_nonce' ) . '" />';		
		do_action( 'urlink_after_link' );
	}
	
	
	/**
	 * Exit if accessed directly
	 * @since 0.0.1
	 */	
	function parse_default( $args ) {
		$defaults = array();
		
		foreach( $this->attributes as $k => $meta )
			$defaults[$k] = isset ( $meta['default'] ) ? $meta['default'] : false;
			
		return wp_parse_args( $args, $defaults );
	}
		
	
	/**
	 * Exit if accessed directly
	 * @since 0.0.1
	 */	
	function popularity_callback() {
		global $post;
		
		echo '<table class="form-table"><tbody>';
	
		foreach( $this->popularities as $key => $popularity ) {
			$id = "pop-$key"; $name = "popularity[$key]";
			$meta = get_post_meta( $post->ID, $key, true );
			$value = $meta ? $meta : 0;
			echo "<tr>";
				echo "<td><label for='$id'>{$popularity['label']}</label></td>";
				echo "<td><input id='$id' name='$name' type='number' value='$value' /></td>";
			echo "</tr>";
		}
		
		echo '</tbody></table>';
	}	
	

	/**
	 * Exit if accessed directly
	 * @since 0.0.1
	 */	
	function metabox_callback() {
		global $post, $post_id;
		
		$tabs = array( 
			'general'	=> __( 'General', $this->textdomain ),
			'custom'	=> __( 'Custom', $this->textdomain )
		);
		
		$meta = get_post_meta( $post_id, '_urlink', true );
		
		$active_tab = isset( $meta['tab'] ) && ! empty( $meta['tab'] ) ? $meta['tab'] : 'general';				
		
		// Join fields with its value	
		$fields = array();
		foreach ( $this->advances as $k => $val ) {
			$fields[$k] = $val;
			$fields[$k]['value'] = isset( $meta[$k] ) ? $meta[$k] : '';
		}
		
		new Bee_Dialog( $tabs, $fields, 'urlink', $active_tab ); // Bee_Dialog( $tabs = array(), $fields = array(), $prefix = 'bee', $active_tab )
	}
	
	
	/**
	 * Exit if accessed directly
	 * @since 0.0.1
	 */		
	function edit_form_after_title( $post ) {
		global $wp_meta_boxes;
		$type = get_post_type( $post ); // post type
		
		do_meta_boxes( get_current_screen(), 'advanced', $post ); // print your meta boxes out there
		
		unset( $wp_meta_boxes[$type]['advanced'] ); // remove it so it doesn't appear twice.	
	}
	
	
	/**
	 * Move author and slug metabox to sidebar
	 * Only if not sorted
	 * @since 0.0.1
	 */		
	function metabox_rearrange( $post ) {
		$type = get_post_type( $post ); // post type
		
		if( get_user_option( "meta-box-order_$type" ) )
			return;
		
		global $wp_meta_boxes;

		// Move slug and author box to side
		$wp_meta_boxes[$type]['side']['low']['slugdiv'] = $wp_meta_boxes[$type]['normal']['core']['slugdiv'];
		$wp_meta_boxes[$type]['side']['low']['authordiv'] = $wp_meta_boxes[$type]['normal']['core']['authordiv'];
		
		// Unset from default	
		unset( $wp_meta_boxes[$type]['normal']['core']['slugdiv'] );
		unset( $wp_meta_boxes[$type]['normal']['core']['authordiv'] );
	}
	
	
	/**
	 * Exit if accessed directly
	 * @since 0.0.1
	 */	
	function save_metabox( $post_id ) {
		global $post, $wpdb;
		
		if ( ! isset( $post->post_type ) || 'link' != $post->post_type ) // return if a new created link post or only for link post type
			return;

		if ( wp_is_post_revision( $post_id ) || ! isset( $_POST['urlink'] ) ) // if this is just a revision, don't save
			return;

		// Attributes and advanced metabox
		$merge = array_merge( $this->attributes, $this->advances );
		$inputs = $this->sanitize_inputs( $_POST['urlink'], $merge );
		update_post_meta( $post_id, '_urlink', $inputs );		

		// Save popularities		
		foreach( $this->popularities as $key => $popularity ) {
			$num = isset( $_POST['popularity'][$key] ) ? (int) $_POST['popularity'][$key] : 0;
			update_post_meta( $post_id, $key, $num );	
		}		
		
		// Delete all transients data
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_widget_urlink_%'" );
		
		do_action( 'urlink_save_metabox', $post_id );
	}
	
	
	/**
	 * Sanitize inputs based on input type
	 * @param $inputs (array) the inputs to sanitize
	 * @since 0.0.1
	 */		
	function sanitize_inputs( $inputs = array(), $defaults = array() ) {
		
		$data = array();
		
		foreach ( $defaults as $key => $meta ) {
			
			if ( ! isset( $inputs[$key] ) ) // set if not set
				$inputs[$key] = $meta[$key]['default'];
			
			switch ( $meta[$key]['type'] ) {
				case 'url':
					$data[$key] = esc_url_raw( $inputs[$key] );
					break;					

				case 'text':
				default:
					$data[$key] = esc_attr( $inputs[$key] );
					break;
			}
		}
		
		return $data;
	}
	
	
	function admin_enqueue_scripts( $hook ) {
		global $post;

		if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'edit.php' ) {
			if ( 'link' === $post->post_type ) {     
				wp_enqueue_style( 'link-post', URLINK_URL . 'css/admin.css' );				
			}
		}
	}
	

} new Urlink_Meta();