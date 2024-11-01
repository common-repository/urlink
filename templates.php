<?php
/*
    Urlink Template Class
    @since 0.0.1
    @copyright 2016 zourbuth.com  (email : zourbuth@gmail.com)

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

class Urlink_Template {

	// Setup class variables
	var $slug;
	var $templates;
	var $textdomain;
	var $name;
	var $options;
	
	
	/**
	 * Class constructor
	 * @since 0.0.1
	 */
	function __construct() {

		$this->slug = URLINK_SLUG;
		$this->textdomain = URLINK_TEXTDOMAIN;
		$this->name = URLINK_NAME;
		
		$this->templates = array(
			'basic' 	=> __( 'Basic', $this->textdomain ),
			'default' 	=> __( 'Default', $this->textdomain ),
			'grid' 		=> __( 'Grids', $this->textdomain ),
		);		
		
		add_filter( 'urlink_templates', array( &$this, 'register_templates' ) );
		add_action( 'urlink_template_options', array( &$this, 'register_options') );
		add_action( 'urlink_template', array( &$this, 'template'), 1, 2 );
	}

	
	/**
	 * Register the template options
	 * @param $args (array) instance value
	 * @since 0.0.1
	 */
	function register_templates() {
		return $this->templates;
	}	

	
	/**
	 * Register the template options
	 * @param $args (array) instance value
	 * @since 0.0.1
	 */
	function register_options( $args ) {
		$options = array();
		
		switch ( $args['template'] ) :
			case "basic":
				break;
				
			case "default":
				$options['content_type'] = array(
					'type' => 'select',
					'options' => array(
						'' 			=> __( 'None', $this->textdomain ),
						'content' 	=> __( 'Full Content', $this->textdomain ),
						'excerpt' 	=> __( 'Excerpt', $this->textdomain ),
						'more' 		=> __( '<!--more--> Separator', $this->textdomain ),
						'meta' 		=> __( 'Meta', $this->textdomain ),
					),
					'default' => 'excerpt',
					'label' => __( 'Content Type', $this->textdomain ),
					'description' => __( 'The content type for each links.', $this->textdomain ),
				);
				
				$options['trimmer'] = array(
					'type' => 'select',
					'options' => array(
						'words'			=> __( 'words', $this->textdomain  ), 
						'characters'	=> __( 'characters', $this->textdomain  )
					),
					'default' => 'words',
					'label' => __( 'Content Trimmer', $this->textdomain ),
					'description' => __( 'Trim the content by using words or characters.', $this->textdomain ),
				);
				
				$options['content_meta'] = array(
					'type' => 'select',
					'options' => array_merge( array( '' ), (array) get_meta_keys() ),
					'default' => '',
					'label' => __( 'Content Meta', $this->textdomain ),
					'description' => __( 'Select the meta key for the content.', $this->textdomain ),
				);
				
				$options['content_length'] = array(
					'type' => 'number',					
					'default' => 25,
					'label' => __( 'Content Length', $this->textdomain ),
					'description' => __( 'The total words (explode by spacing) or characters to trim.', $this->textdomain ),
				);
				
				$options['ellipsis'] = array(
					'type' => 'text',					
					'default' => '...',
					'label' => __( 'Ellipsis', $this->textdomain ),
					'description' => __( 'The text to add after content', $this->textdomain ),
				);		
				
				$options['read_more'] = array(
					'type' => 'text',					
					'class' => 'widefat',					
					'default' =>  __( 'more', $this->textdomain ),
					'label' => __( 'More Link Text', $this->textdomain ),
					'description' => __( 'The text for the read more link', $this->textdomain ),
				);								
				break;

			case "grid":
				$options['margin'] = array(
					'type' => 'number',
					'default' => 10,
					'label' => __( 'Margin', $this->textdomain ),
					'description' => __( 'Margin for each list in pixels.', $this->textdomain ),
				);
				break;
		endswitch;

		return $options;
	}
	
	
	/**
	 * Template functiona
	 * @param $query (object) WP Query
	 * 		  $args (array) widget/shortcode parameters
	 * @since 0.0.1
	 */
	function template( $query, $args ) { 
		if ( ! array_key_exists( $args['template'], $this->templates ) )
			return;
		
		$style = '';
		$wrapper_class = $args['wrapper_class'];
		$rtl_class = is_rtl() ? "rtl" : "";
				
		if ( 'grid' == $args['template'] ) { // for grid only
			$margin = $args['template_options']['margin'];
			$style = "margin-left: -{$margin}px;";
		}
		
		$ops = $args['template_options'];
		
		$html = "<ul class='urlink urlink-{$args['template']} $wrapper_class $rtl_class' style='$style'>";

		while ( $query->have_posts() ) :
			$query->the_post();
			
			$meta = get_post_meta( get_the_ID(), '_urlink', true );
			$link_class = "urlink-link {$meta['anchor_class']}";
			$li_class = $meta['wrapper_class'];
			$permalink = get_the_permalink();
			$title = get_the_title();
			
			switch ( $args['template'] ) :
				case "basic":
					$html .= "<li class='$li_class'><a class='$link_class' href='$permalink'>$title</a></li>";									
					break;
					
				case "default":
					$pad = $args['width'] + 10;
					$padding = is_rtl() ? "padding-right: {$pad}px;" : "padding-left: {$pad}px;";										
					$minheight = "min-height: {$args['width']}px;";
					
					$html .= "<li class='$li_class' style='$padding $minheight'>";									
						$html .= "<a class='thumbnail' href='$permalink'>". urlink_thumbnail( $args ) ."</a>";							
						$html .= "<a class='$link_class' href='$permalink'>$title</a>";														

						$html .= urlink_content( $args['template_options'] );
					
					$html .= "</li>";
					break;

				case "grid":
					$margin = $args['template_options']['margin'];
					$style = "margin-left: {$margin}px; margin-bottom: {$margin}px;";
					$html .= "<li class='$li_class' style='$style'>";
						$html .= urlink_thumbnail( $args );
					$html .= "</li>";
					break;
			endswitch;
		endwhile;

		$html .= '</ul>';
		echo $html;				
	}

} new Urlink_Template();
?>