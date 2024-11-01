<?php
/*
    Widget Class
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

class Urlink_Widget extends WP_Widget {

	// Setup class variables
	var $slug;
	var $version;
	var $url;
	var $textdomain;
	var $name;
	var $transient;
	
	
	/**
	 * Set up the widget's unique name, ID, class, description, and other options.
	 * @since 0.0.1
	 */
	function __construct() {

		$this->slug = URLINK_SLUG;
		$this->version = URLINK_VERSION;
		$this->textdomain = URLINK_TEXTDOMAIN;
		$this->url = URLINK_URL;
		$this->name = URLINK_NAME;
		
		// Set up the widget options
		$widget_options = array(
			'classname' => "{$this->slug}-widget",
			'description' => __( 'Links Widget.', $this->textdomain )
		);

		// Set up the widget control options
		$control_options = array(
			'width' => 460,
			'height' => 350,
			'id_base' => $this->slug
		);

		// Create the widget
		parent::__construct( $this->slug, esc_attr__( $this->name, $this->textdomain ), $widget_options, $control_options );
		
		// Load the widget stylesheet for the widgets admin screen
		add_action( 'load-widgets.php', array(&$this, 'load_widgets') );
		add_action( 'admin_print_styles-widgets.php', array(&$this, 'admin_print_styles') );						
		
		if ( is_active_widget( false, false, $this->id_base, false ) && ! is_admin() ) {
			add_action( 'wp_head', array( &$this, 'print_header') );
			add_action( 'wp_footer', array( &$this, 'print_footer') );			
		}		
	}
	
	
	/**
	 * Print the widget template custom scripts
	 * @since 0.0.1
	 */	
	function print_header() {
		$settings = $this->get_settings();
		
		foreach ( $settings as $k => $setting )
			if ( ! empty( $setting['header'] ) ) 
				echo $setting['header']. "\n";
	}
	
		
	/**
	 * Print the widget template styles
	 * @since 0.0.1
	 */	
	function print_footer() {
		$settings = $this->get_settings();
		
		foreach ( $settings as $k => $setting )
			if ( ! empty( $setting['footer'] ) ) 
				echo $setting['footer']. "\n";	
	}

	
	/**
	 * Push the widget stylesheet widget.css into widget admin page
	 * @since 0.0.1
	 */	
	function load_widgets() {		
		wp_enqueue_media();
		wp_enqueue_script( "link-dialog", plugin_dir_url( __FILE__ ) . 'js/jquery.dialog.js', array( 'jquery' ), URLINK_VERSION, false );
	}
	
	
	/**
	 * Push the widget stylesheet widget.css into widget admin page
	 * @since 0.0.1
	 */	
	function admin_print_styles() { 
	
	}

	
	/**
	 * Outputs the widget based on the arguments input through the widget controls.
	 * @since 0.0.1
	 */
	function widget( $args, $instance ) {
		$transient = "{$this->option_name}_{$this->number}";
		
		if ( $html = get_transient( $transient ) ) {
			echo $html;
			return;
		}
				
		extract( $args );
		
		$instance = wp_parse_args( (array) $instance, urlink_default_args() );
		
		$html = $before_widget; // output the theme's widget wrapper
				
		if ( $instance['title'] )
			$html .= $before_title . apply_filters( 'widget_title',  $instance['title'], $instance, $this->id_base ) . $after_title;

		if ( $instance['intro'] )
			$html .= '<div class="'. $this->id . '-intro-text intro-text">' . $instance['intro'] . '</div>';
		 		
		ob_start();
		urlink_query( $instance );
		$html .= ob_get_contents();
		ob_end_clean();		

		if ( $instance['outro'] )
			$html .= '<div class="'. $this->id . '-outro-text outro-text">' . $instance['outro'] . '</div>';

		$html .= $after_widget; // close the theme's widget wrapper
		
		set_transient( $transient, $html, 0 );
		
		echo $html;
	}

	
	/**
	 * Updates the widget control options for the particular instance of the widget.
	 * @since 0.0.1
	 */
	function update( $new_instance, $old_instance ) {
		
		// Delete widget cache transtion if is changed
		delete_transient( "{$this->option_name}_{$this->number}" ); 	
	
		$instance = $new_instance; // set the instance to the new instance.

		$instance['title']			= strip_tags( $new_instance['title'] );
		$instance['template']		= $new_instance['template'];
		$instance['toggle_active']	= $new_instance['toggle_active'];
		$instance['intro']	= $new_instance['intro'];
		$instance['outro']	= $new_instance['outro'];
		$instance['header']	= $new_instance['header'];
		$instance['footer']	= $new_instance['footer'];

		$template_options = apply_filters( 'urlink_template_options', $instance );
		
		$template_defaults = array();
		foreach( $template_options as $k => $opt )
			$template_defaults[$k] = $opt['default'];
			
		// If new template is chosen, reset the template options
		if ( $instance['template'] !== $old_instance['template'] && '' !== $old_instance['template'] )
			$instance['template_options'] = $template_defaults; // templates options array, do data sanitazion before saving to database
		else
			$instance['template_options'] = urlink_sanitize( $new_instance['template_options'] );
		
		return $instance;
	}

	
	/**
	 * Displays the widget control options in the Widgets admin screen.
	 * @since 0.0.1
	 */
	function form( $instance ) {		
		
		// Merge the user-selected arguments with the defaults.
		$instance = wp_parse_args( (array) $instance, urlink_default_args() );
		$instance['id'] = $this->number;
		
		$tabs = array( 
			__( 'General', $this->textdomain ),  
			__( 'Query', $this->textdomain ),
			__( 'Template', $this->textdomain ),
			__( 'Advanced', $this->textdomain ),
			__( 'Supports', $this->textdomain )
		);			
		$taxonomies = get_object_taxonomies( 'link', 'objects' );
		$tax_relations = array(
			'AND'	=> esc_attr__( 'And', $this->textdomain ), 
			'OR'	=> esc_attr__( 'Or', $this->textdomain )
		);
		$meta_keys = array_merge( array( '' ), (array) get_meta_keys() );
		$orderbys = array(
			'date', 'none', 'ID', 'author', 'title', 'name',
			'date', 'modified', 'parent', 'rand', 'comment_count', 
			'menu_order', 'meta_value', 'meta_value_num', 'post__in'
		);
		$orders = array(
			'ASC'	=> esc_attr__( 'Ascending', $this->textdomain ), 
			'DESC'	=> esc_attr__( 'Descending', $this->textdomain )
		);
		//print_r( $instance );
		?>

		<div class="pluginName"><?php echo $this->name; ?><span class="pluginVersion"><?php echo $this->version; ?></span></div>
		<div id="tcp-<?php echo $this->id ; ?>" class="bee-dialog total-options tabbable tabs-left">
			<ul class="nav nav-tabs">
				<?php foreach ($tabs as $key => $tab ) : ?>
					<li class="<?php echo $key == $instance['toggle_active'] ? 'active' : '' ; ?>"><?php echo $tab; ?></li>
				<?php endforeach; ?>							
			</ul>
			<input type="hidden" name="<?php echo $this->get_field_name( 'toggle_active' ); ?>" value="<?php echo $instance['toggle_active']; ?>" />
			
			<ul class="tab-content">
			
				<li class="tab-pane <?php if ( 0 == $instance['toggle_active'] ) : ?>active<?php endif; ?>">
					<ul>
						<li>
							<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', $this->textdomain ); ?></label>
							<span class="description"><?php _e( 'Give this widget a title, or leave empty for no title.', $this->textdomain ); ?></span>	
							<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" />												
						</li>						
						<li>
							<label for="<?php echo $this->get_field_id( 'empty_thumbnail' ); ?>"><?php _e( 'Default Thumbnail', $this->textdomain ); ?></label>
							<span class="description"><?php _e( 'This image will be used if it does not have featured image and with show thumbnail enable.', $this->textdomain ); ?></span>
							<img alt="" class="optionImage" src="<?php echo wp_get_attachment_thumb_url( $instance['empty_thumbnail'] ); ?>">
							<a href="#" class="add-thumbnail button"><?php _e( 'Add Thumbnail', $this->textdomain ); ?></a>
							<a class="<?php if ( empty($instance['empty_thumbnail'] ) ) : ?>hidden <?php endif; ?>remove-image button" href="#"><?php _e('Remove', $this->textdomain); ?></a>
							<input type="hidden" id="<?php echo $this->get_field_id( 'empty_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'empty_thumbnail' ); ?>" value="<?php echo $instance['empty_thumbnail']; ?>" />
						</li>
						<li>
							<label for="<?php echo $this->get_field_id('wrapper_class'); ?>"><?php _e( 'Wrapper Class', $this->textdomain ); ?> </label>
							<span class="description"><?php _e( 'Add custom CSS class selector for the widget.', $this->textdomain ); ?></span>
							<input id="<?php echo $this->get_field_id('wrapper_class'); ?>" name="<?php echo $this->get_field_name('wrapper_class'); ?>" type="text" value="<?php echo $instance['wrapper_class']; ?>" />
						</li>						
					</ul>
				</li>
				
				<li class="tab-pane <?php if ( 1 == $instance['toggle_active'] ) : ?>active<?php endif; ?>">
					<ul>
						<li>
							<label for="<?php echo $this->get_field_id('posts_per_page'); ?>"><?php _e( 'Post Number', $this->textdomain ); ?> </label>
							<span class="description"><?php _e( 'The total post to display in a widget.', $this->textdomain ); ?></span>
							<input class="smallfat" id="<?php echo $this->get_field_id('posts_per_page'); ?>" name="<?php echo $this->get_field_name('posts_per_page'); ?>" type="text" value="<?php echo (int) $instance['posts_per_page']; ?>" />
						</li>
						<li>
							<label for="<?php echo $this->get_field_id('offset'); ?>"><?php _e( 'Post Offset', $this->textdomain ); ?> </label>
							<span class="description"><?php _e( 'number of post to displace or pass over.', $this->textdomain ); ?></span>
							<input class="smallfat" id="<?php echo $this->get_field_id('offset'); ?>" name="<?php echo $this->get_field_name('offset'); ?>" type="text" value="<?php echo esc_attr($instance['offset']); ?>" />
						</li>						
						<li>
							<label for="<?php echo $this->get_field_id( 'order' ); ?>"><?php _e( 'Sort Order', $this->textdomain ); ?></label> 
							<span class="description"><?php _e( 'The posts order in ascending or descending ordering and the order by predifined key below.', $this->textdomain ); ?></span>
							<select id="<?php echo $this->get_field_id( 'order' ); ?>" name="<?php echo $this->get_field_name( 'order' ); ?>">
								<?php foreach ( $orders as $key => $value ) { ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $instance['order'], $key ); ?>><?php echo esc_html( $value ); ?></option>
								<?php } ?>
							</select>
							<select  onchange="wpWidgets.save(jQuery(this).closest('div.widget'),0,1,0);" id="<?php echo $this->get_field_id( 'orderby' ); ?>" name="<?php echo $this->get_field_name( 'orderby' ); ?>">
								<?php foreach ( $orderbys as $value ) { ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $instance['orderby'], $value ); ?>><?php echo esc_html( $value ); ?></option>
								<?php } ?>
							</select>								
						</li>

						<?php if ( preg_match('/meta_value/', $instance['orderby']) ) : ?>																
							<li>
								<label for="<?php echo $this->get_field_id( 'meta_key' ); ?>"><?php _e( 'Meta Key', $this->textdomain ); ?></label> 
								<span class="description"><?php _e( 'Select the meta key for ordering.', $this->textdomain ); ?></span>
								<select id="<?php echo $this->get_field_id( 'meta_key' ); ?>" name="<?php echo $this->get_field_name( 'meta_key' ); ?>">
									<?php foreach ( $meta_keys as $value ) { ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $instance['meta_key'], $value ); ?>><?php echo $value; ?></option>
									<?php } ?>
								</select>
							</li>
						<?php endif; ?>							
						
						<?php foreach ( $taxonomies as $taxonomy ) : ?>
							<?php
								//print_r( $taxonomy);
								$option = get_option( 'super_post' );
								$args = isset( $option['show_empty_term'] ) ? array( 'hide_empty' => false ) : array();
								$terms = get_terms( $taxonomy->name, $args );
								$taxonomy_operators = array(
									'IN' 		=> __( 'In', $this->textdomain ),
									'NOT IN' 	=> __( 'Not In', $this->textdomain ),
									'AND' 		=> __( 'And', $this->textdomain )
								);
							?>						
							<li>
								<label for="<?php $this->get_field_id( 'tax_query' ) . $taxonomy->name; ?>"><?php echo $taxonomy->label; ?></label>
								<?php if ( ! empty ( $terms ) ) { ?>
									<span class="description"><?php _e( 'Select the post type or custom query for post.', $this->textdomain ); ?></span>
									<select class="widefat" id="<?php echo $this->get_field_id( 'tax_query' ) . $taxonomy->name ; ?>" name="<?php echo $this->get_field_name( 'tax_query' ); ?>[<?php echo $taxonomy->name; ?>][terms][]" size="4" multiple="multiple">
										<?php foreach ( $terms as $term ) { ?>
											<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php echo ( isset( $instance['tax_query'][$taxonomy->name]['terms'] ) && in_array( $term->term_id, (array) $instance['tax_query'][$taxonomy->name]['terms'] ) ? 'selected="selected"' : '' ); ?>><?php echo esc_html( $term->name ); ?></option>
										<?php } ?>
									</select>
								<?php } else { ?>
									<span class="description"><?php _e( 'There is no terms for this taxonomy, please create it first.', $this->textdomain ); ?></span>
								<?php } ?>
								
								<p>
									<span class="description"><?php _e( sprintf('%s Operator', $taxonomy->label ), $this->textdomain ); ?></span>																				
									<select id="<?php echo $this->get_field_id( 'tax_query' ) . $taxonomy->name . '_operator'; ?>" name="<?php echo $this->get_field_name( 'tax_query' ); ?>[<?php echo $taxonomy->name; ?>][operator]">
										<?php $opr = isset( $instance['tax_query'][$taxonomy->name]['operator'] ) ? $instance['tax_query'][$taxonomy->name]['operator'] : 'IN'; ?>
										<?php foreach ( $taxonomy_operators as $k => $v ) { ?>												
											<option value="<?php echo $k; ?>" <?php selected( $opr, $k ); ?>><?php echo $v; ?></option>
										<?php } ?>
									</select>																	
								</p>															
							</li>
						<?php endforeach; ?>
						<li>
							<label for="<?php echo $this->get_field_id( 'tax_relation' ); ?>"><?php _e( 'Taxonomy Relation', $this->textdomain ); ?></label>
							<span class="description"><?php _e( 'Relation between each selected terms taxonomy above.', $this->textdomain ); ?></span>
							<select id="<?php echo $this->get_field_id( 'tax_relation' ); ?>" name="<?php echo $this->get_field_name( 'tax_relation' ); ?>">
								<?php foreach ( $tax_relations as $k => $v ) { ?>
									<option value="<?php echo $k; ?>" <?php selected( $instance['tax_relation'], $k ); ?>><?php echo $v; ?></option>
								<?php } ?>
							</select>
						</li>			
					</ul>
				</li>
				
				<li class="tab-pane <?php if ( 2 == $instance['toggle_active'] ) : ?>active<?php endif; ?>">
					<ul>
						<li>
							<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template', $this->textdomain ); ?></label> 
							<span class="description"><?php _e( 'Select the countdown template', $this->textdomain ); ?></span>
							<select onchange="wpWidgets.save(jQuery(this).closest('div.widget'),0,1,0);" class="smallfat" id="<?php echo $this->get_field_id( 'template' ); ?>" name="<?php echo $this->get_field_name( 'template' ); ?>">
								<?php foreach ( apply_filters( 'urlink_templates' ) as $t => $template ) { ?>
									<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $instance['template'], $t ); ?>><?php echo esc_html( $template ); ?></option>
								<?php } ?>
							</select>
						</li>
						<li>
							<label for="<?php echo $this->get_field_id( 'height' ); ?>"><?php _e( 'Thumbnail Height & Width', $this->textdomain ); ?></label>
							<span class="description"><?php _e( 'The featured image or thumbnail height and width in pixels unit.', $this->textdomain ); ?></span>
							<input class="smallfat" type="text" id="<?php echo $this->get_field_id( 'height' ); ?>" name="<?php echo $this->get_field_name( 'height' ); ?>" value="<?php echo esc_attr( $instance['height'] ); ?>" />
							<input class="smallfat" type="text" id="<?php echo $this->get_field_id( 'width' ); ?>" name="<?php echo $this->get_field_name( 'width' ); ?>" value="<?php echo esc_attr( $instance['width'] ); ?>" />
						</li>						
						<?php
							$template_options = apply_filters( 'urlink_template_options', $instance );
							urlink_template_dialog( $template_options, $instance['template_options'], $this->get_field_name( 'template_options' ), $this->get_field_id('template_options') );
						?>
					</ul>
				</li>
				<li class="tab-pane <?php if ( 3 == $instance['toggle_active'] ) : ?>active<?php endif; ?>">
					<ul>
						<li>
							<label><?php _e( 'Shortcode & Function', $this->textdomain ) ; ?></label>
							<span class="description">								
								<?php _e( '<strong>Note</strong>: Drag this widget to the "Inactive Widgets" at the bottom of this page if you want to use this as a shortcode to your content or PHP function in your template with the codes above.', $this->textdomain ); ?>
								<span class="shortcode">
									<?php _e( 'Widget Shortcode: ', $this->textdomain ); ?><?php echo '[urlink-widget id="' . $this->number . '"]'; ?><br />
									<?php _e( 'PHP Function: ', $this->textdomain ); ?><?php echo '&lt;?php echo do_shortcode(\'[urlink-widget id="' . $this->number . '"]\'); ?&gt;'; ?>						
								</span>
							</span>
						</li>
						<li>
							<label for="<?php echo $this->get_field_id('intro'); ?>"><?php _e( 'Intro Text', $this->textdomain ); ?></label>
							<span class="description"><?php _e( 'This option will display addtional text before the widget content and HTML supports.', $this->textdomain ); ?></span>
							<textarea name="<?php echo $this->get_field_name( 'intro' ); ?>" id="<?php echo $this->get_field_id( 'intro' ); ?>" rows="2" class="widefat"><?php echo esc_textarea($instance['intro']); ?></textarea>
						</li>
						<li>
							<label for="<?php echo $this->get_field_id('outro'); ?>"><?php _e( 'Outro Text', $this->textdomain ); ?></label>
							<span class="description"><?php _e( 'This option will display addtional text after widget and HTML supports.', $this->textdomain ); ?></span>
							<textarea name="<?php echo $this->get_field_name( 'outro' ); ?>" id="<?php echo $this->get_field_id( 'outro' ); ?>" rows="2" class="widefat"><?php echo esc_textarea($instance['outro']); ?></textarea>							
						</li>				
						<li>
							<label for="<?php echo $this->get_field_id('header'); ?>"><?php _e( 'Custom Header', $this->textdomain ) ; ?></label>
							<span class="description"><?php _e( 'Will be printed inside the header tag. Current widget selector: ', $this->textdomain ); ?><?php echo '<tt>#' . $this->id . '</tt>'; ?></span>
							<textarea name="<?php echo $this->get_field_name( 'header' ); ?>" id="<?php echo $this->get_field_id( 'header' ); ?>" rows="3" class="widefat code"><?php echo htmlentities($instance['header']); ?></textarea>
						</li>		
						<li>
							<label for="<?php echo $this->get_field_id('footer'); ?>"><?php _e( 'Custom Footer', $this->textdomain ) ; ?></label>
							<span class="description"><?php _e( 'Will be printed after the body tag. Current widget selector: ', $this->textdomain ); ?><?php echo '<tt>#' . $this->id . '</tt>'; ?></span>
							<textarea name="<?php echo $this->get_field_name( 'footer' ); ?>" id="<?php echo $this->get_field_id( 'footer' ); ?>" rows="3" class="widefat code"><?php echo htmlentities($instance['footer']); ?></textarea>
						</li>
					</ul>
				</li>
				<li class="tab-pane <?php if ( 4 == $instance['toggle_active'] ) : ?>active<?php endif; ?>">
					<ul>
						<li>
							<h3><?php _e( 'Support and Contribute', $this->textdomain ); ?></h3>
							<p><?php _e( 'Please ask us for supports or discussing new features for the next updates.', $this->textdomain ); ?><p>
							<ul>
								<li>
									<p style="margin-bottom: 5px;"><a href="javascript: void(0)"><strong><?php _e( 'Tweet to Get Supports', $this->textdomain ); ?></strong></a></p>
									<a href="https://twitter.com/intent/tweet?screen_name=zourbuth" class="twitter-mention-button" data-related="zourbuth">Tweet to @zourbuth</a>
									<a href="https://twitter.com/zourbuth" class="twitter-follow-button" data-show-count="false">Follow @zourbuth</a>									
								</li>
								<li>
									<span class="description"><?php _e( 'Help us to share this plugin.', $this->textdomain ); ?></span>
									
									<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
									<script>if( typeof(twttr) !== 'undefined' ) twttr.widgets.load()</script>								
								</li>
							</ul>
						</li>
					</ul>
				</li>					
			</ul>
		</div>
	<?php
	}
}
?>