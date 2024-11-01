<?php
/*
    Main Plugin Class 0.0.1
    
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


if ( ! defined( 'ABSPATH' ) ) // exit if is accessed directly
	exit;


class Urlink_Main {
	
	 var $slug, $options, $textdomain;
	 
	/**
	 * Class constructor
	 * 
	 * @since 0.0.1
	 */		
	function __construct() {
		$this->slug = URLINK_SLUG;
		$this->textdomain = URLINK_SLUG;
		$this->options = get_option( $this->slug );
		
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
		add_action( 'template_redirect', array( &$this, 'do_redirect' ) );
		add_filter( 'post_type_link', array( &$this, 'post_type_link' ), 1, 4 );
		
		add_shortcode( 'urlink',  array( &$this, 'shortcode' ) );		
		add_shortcode( 'urlink-widget',  array( &$this, 'widget_shortcode' ) );		
	}
	
	
	/**
	 * Add shortcode based on link id
	 * 
	 * @param $atts (array)
	 * @since 0.0.1
	 */	
	function shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => false,
		), $atts, 'urlink' );
		
		$html = '';
		
		$meta = get_post_meta( (int) $atts['id'], '_urlink', true );

		if( isset( $meta['redirect'] ) || ! empty( $meta['redirect'] ) )
			$html .= '<a class="urlink-link" href="'. $meta['url'] .'">'. get_the_title( $atts['id'] ) .'</a>';
		else
			$html .= sprintf( __( 'Shortcode [urlink id="%s"] invalid!', $this->textdomain ), $atts['id'] );
		
		return $html;
	}
	
	
	/**
	 * Add widget shortcode based on widget id
	 * 
	 * @param $atts (array)
	 * @since 0.0.1
	 */	
	function widget_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'id' => false,
		), $atts, 'urlink' );
		
		$html = '';		

		$options = get_option( 'widget_urlink' );
		$widget_id = $atts['id'];
		$args = $options[$widget_id];		
		
		$instance = wp_parse_args( (array) $args, urlink_default_args() );
			
		if ( $instance['title'] )
			echo apply_filters( 'widget_title',  $instance['title'], $instance, URLINK_SLUG );

		if ( $instance['intro'] )
			echo '<div class="'. $this->id . '-intro-text intro-text">' . $instance['intro'] . '</div>';

		echo urlink_query( $instance );
		
		if ( $instance['outro'] )
			echo '<div class="'. $this->id . '-outro-text outro-text">' . $instance['outro'] . '</div>';
		
		
		return $html;
	}

	
	/**
	 * Enqueue plugin style and script files
	 * 
	 * @since 0.0.1
	 */
	function enqueue_scripts() { 
		wp_enqueue_style( URLINK_SLUG, URLINK_URL . 'css/urlink.css' );
	}
	
	
	/**
	 * Modify the post link for the edit page
	 * 
	 * @param string  $post_link The post's permalink.
	 * @param WP_Post $post      The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 * @param bool    $sample    Is it a sample permalink.
	 * @since 0.0.1
	 */
	function post_type_link( $post_link, $post, $leavename, $sample ) { 
				
		if ( 'link' != $post->post_type )
			return $post_link;
		
		if ( ! $meta = get_post_meta( $post->ID, '_urlink', true ) )
			return $post_link;

		if( ! isset( $meta['redirect'] ) || empty( $meta['redirect'] ) )
			$post_link = $meta['url'];
		
		return $post_link;
	}
	
	
	/**
	 * Redirect to specific URL
	 * 
	 * @since 0.0.1
	 */
	function do_redirect() {
		
		if ( is_single() ) { // only for single post view

			global $post;
			$meta = get_post_meta( $post->ID, '_urlink', true );

			// Check if URL is set and not empty
			if ( isset( $meta['url'] ) && $meta['url'] ) {				
				
				// Update clicks counter, but not for admin
				if ( ! current_user_can( 'manage_options' ) ) { 			
					$clicks = get_post_meta( $post->ID, '_urlink_clicks', true );
					$clicks++;
					update_post_meta( $post->ID, '_urlink_clicks', $clicks );	
				}
				
				/**
				 * Action hook ran before doing redirect
				 * @since 0.0.1
				 */
				do_action( 'urlink_redirect' );
				
				wp_redirect( $meta['url'] );
				exit();
			}
		}
	}

} new Urlink_Main();


/**
* Returns the countdown templates name
* @return array
* @since 2.0.0
*/
function urlink_default_args() {
	$textdomain = URLINK_TEXTDOMAIN;
	$options = get_option( 'urlink' );

	// Set up the default form values
	$defaults = array(
		'id'				=> null,
		'title'				=> esc_attr__( 'Links', $textdomain ),
		'width'		=> 90,
		'height'	=> 90,
		'wrapper_class'		=> '',
		'empty_thumbnail'	=> '',
		'template'			=> '',
		'template_options'	=> '',
		'toggle_active'		=> 0,
		'posts_per_page'	=> 10,
		'offset'			=> '',
		'order'				=> 'DESC',
		'orderby'			=> 'date',
		'meta_key'			=> '',
		'tax_query'			=> array(),
		'tax_relation'		=> 'AND',
		'intro'		=> '',
		'outro'		=> '',
		'header'	=> '',
		'footer'	=> ''
	);
	
	return $defaults;
}


/**
* The main query
* https://codex.wordpress.org/Class_Reference/WP_Query
* @param $args (array) 
* @return HTML
* @since 0.0.1
*/
function urlink_query( $args ) {
	$textdomain = URLINK_TEXTDOMAIN;
	$args = wp_parse_args( (array) $args, urlink_default_args() ); // merge the user-selected arguments with the defaults.

	$cur_id = get_the_ID();
	$html = $list = '';
	
	$q = $tax_query = array();
	$q['post_type'] = 'link';
	$q['posts_per_page'] = $args['posts_per_page'];
	
	if( $args['offset'] )
		$q['offset'] = $args['offset'];
	
	$q['order'] = $args['order'];
	$q['orderby'] = $args['orderby'];
	
	// Custom order by meta
	if( $args['meta_key'] )
		$q['meta_key'] = $args['meta_key'];

	// Taxonomy query
	if( $args['tax_query'] ) { 
		
		foreach( $args['tax_query'] as $tax => $value ) {
			if( isset( $value['terms'] ) ) {
				$tax_query[] = array(
					'taxonomy' => $tax,
					'field' => 'id',
					'terms' => implode( ",", $value['terms'] ),
					'operator' => $value['operator']
				);
			}
		}
		
		if ( count( $tax_query ) > 1 ) // do not use with a single inner taxonomy array. 
			$tax_query['relation'] = $args['tax_relation'];
		
		if ( $tax_query )
			$q['tax_query'] = $tax_query;
	}
	
	$query = new WP_Query( apply_filters( 'urlink_query', $q, $args ) );
	
	if ( $query->have_posts() ) :
		
		do_action( 'urlink_template_'. $args['template'], $query, $args );
		do_action( 'urlink_template', $query, $args );
		
		wp_reset_postdata(); // reset the post globals as this query will shakes the party
	
	else:	
		if ( current_user_can( 'manage_options' ) )
			echo __( 'There is no results for the current settings.', $textdomain );
	endif;
}


/**
* Data sanitazion based on input type
* @param $args (array) 
* @return array
* @since 0.0.1
*/
function urlink_sanitize( $args ) {
	$arr = array();
	
	foreach ( $args as $k => $val ) {
		switch ( $args['type'] ) {
			case 'number':
			case 'thumbnail':
				$arr[$k] = (int) $val;
				break;

			case 'textarea':
				$arr[$k] = esc_textarea( $val );
				break;

			case 'color':
			case 'text':
			default:
				$arr[$k] = esc_attr( $val );
				break;
		}
	}		
		
	return $arr;
}


/**
* The main function
* https://codex.wordpress.org/Class_Reference/WP_Query
* 
* @param $args (array) 
* @return HTML
* @since 0.0.1
*/
function urlink( $args ) {
	$textdomain = URLINK_TEXTDOMAIN;
	$args = wp_parse_args( (array) $args, urlink_default_args() ); // merge the user-selected arguments with the defaults.
}


/**
 * Return the URL based on user option using redirect or direct
 * 
 * @params $post (object) current post object
 * @since 0.0.1
 */
function urlink_url( $post ) {		
	$meta = get_post_meta( $post->ID, '_urlink', true );

	if( ! isset( $meta['redirect'] ) || empty( $meta['redirect'] ) )
		$post_link = $meta['url'];
	else
		$post_link = get_the_permalink( $post );
	
	return $post_link;	
}


/**
 * Function to generate new image size if not available
 * 
 * @params $image_id, the attachment image ID
 * 		   $size, image size name 
 * 		   $width and $height, crop new image with the sizes
 * @since 0.0.1
 */
function urlink_generate_thumbnail( $image_id, $width, $height ) {
	if ( ! is_array( $imagedata = wp_get_attachment_metadata( $image_id ) ) )
		return false;
	
	$size = "$width-$height";
	
	if ( ! isset( $imagedata['sizes'][$size] ) ) {		
		global $_wp_additional_image_sizes;
		$_wp_additional_image_sizes["$width-$height"] = array( 'width' => $width, 'height' => $height, 'crop' => true );
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$fullsizepath = get_attached_file( $image_id );
		$metadata = wp_generate_attachment_metadata( $image_id, $fullsizepath );
		wp_update_attachment_metadata( $image_id, $metadata );
	}
	
	$img = wp_get_attachment_image_src( $image_id, $size, true );
	return apply_filters( 'urlink_generate_thumbnail', $img[0], $image_id, $width, $height );	
}


/**
 * Function to create the title shorter
 * 
 * @param $args (array) widget or shortcode settings
 * @return (string) HTML
 * @since 0.0.1
 */
function urlink_get_thumbnail( $args ) {
	extract( $args );
	
	if( 0 == $height || 0 == $width )
		return;			
	
	$img = '';
	
	if ( has_post_thumbnail() ) {
		$image_id = get_post_thumbnail_id();
		$img = urlink_generate_thumbnail( $image_id, $width, $height );			
	} else {
		if ( $empty_thumbnail )
			$img = urlink_generate_thumbnail( $empty_thumbnail, $width, $height );
	}
	
	return $img;
}


/**
 * Function to create the title shorter
 * 
 * @param $args (array) widget or shortcode settings
 * @return (string) HTML
 * @since 0.0.1
 */
function urlink_thumbnail( $args ) {
	extract( $args );
	
	if( $img = urlink_get_thumbnail($args) )	{
		$edit = current_user_can( 'edit_posts' ) ? sprintf( '<a class="edit" href="%1$s">%2$s</a>', get_edit_post_link(), __( 'edit', URLINK_TEXTDOMAIN ) ) : '';
		
		return sprintf( '<div class="urlink-thumbnail"><a href="%1$s"><img src="%2$s" alt="%3$s" /></a>%4$s</div>', 
			get_the_permalink(), $img, get_the_title(), $edit );
	}
}



/**
 * Get the excerpt with
 * @param $length the total spaces ' ' in the excerpt
 * @param $more additional text after the excerpt
 * @param $link additional text for link to the post
 * @param $trim 'words' or 'characters'
*  @return excerpt
 * @since 0.0.1
 */
function urlink_content( $args ) {
	
	$args = wp_parse_args( (array) $args, array(
		'content_type' 		=> 'excerpt',
		'read_more' 		=> '', // read more text after the content
		'ellipsis'			=> '', // elipses
		'content_length' 	=> '',
		'trimmer' 			=> '',
		'content_meta'		=> '', // meta key
	));	
	
	global $post;

	$html = '';
	
	switch ( $args['content_type'] ):
		
		case 'excerpt':
			$read_more = $args['read_more'] ? " <a class='urlink-more' href='" . get_permalink(). "'>{$args['read_more']}</a>" : '';
			
			if ( has_excerpt() ) {
				return '<p class="urlink-content">'. $post->post_excerpt . $args['ellipsis'] . $read_more .'</p>';
			} else {
				
				$text = $post->post_content;		// Strips HTML tags
				$text = wp_strip_all_tags( $text );	// Strips shortcodes
				
				if ( 'characters' == $args['trimmer'] ) {
					$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
					preg_match_all( '/./u', $text, $words_array );
					$words_array = array_slice( $words_array[0], 0, $args['content_length'] + 1 );
					$sep = '';
				} else {
					$words_array = preg_split( "/[\n\r\t ]+/", $text, $args['content_length'] + 1, PREG_SPLIT_NO_EMPTY );
					$sep = ' ';
				}	

				if ( count( $words_array ) > $args['content_length'] ) {
					array_pop( $words_array ); 
					$text = implode( $sep, $words_array ); 
					$text = $text . $args['ellipsis'];
				} else {
					$text = implode( $sep, $words_array );
				}
							
				return '<p class="urlink-content">'. $text . $read_more .'</p>';
			}
			break;

		case 'content':
			// Filter the post content. Using the same approach as the_content function
			$content = get_the_content( $args['read_more'] );
			$content = apply_filters( 'the_content', $content );
			$content = str_replace( ']]>', ']]&gt;', $content );			
			return '<p class="urlink-content">' . $content . '</p>';
			break;

		case 'meta':
			return '<p class="urlink-content">' . get_post_meta( get_the_ID(), $args['content_meta'], true ) . '</p>';
			break;
			
		// Following the get_the_content() function at \wp-includes\post-template.php
		// Will return full content if <!--more--> is not available
		case 'more':
			global $post;
			$content = $post->post_content;
			$read_more = $args['read_more'] ? " <a class='urlink-more' href='" . get_permalink(). "'>{$args['read_more']}</a>" : '';
			if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {				
				$content = explode( $matches[0], $content, 2 );
				$content = $content[0] . $read_more;				
			}
			
			return $content;
			break;
			
		default:
	endswitch;
}