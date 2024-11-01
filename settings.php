<?php
/*
    Class: Settings
    
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


class Urlink_Settings {
    
    private $options;
    var $tabs, $slug, $name, $section, $textdomain, $settings;

	
    /**
     * Start up
     */
    function __construct() {
		$this->slug = URLINK_SLUG;
		$this->title = 'Urlink Settings';
		$this->tabs = array( 
			'general'	=> 'General', 
			'apps'		=> 'Applications', 
			'advanced'	=> 'Advanced'
		);
		$this->section = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
			
		$this->settings = array(
			'redirect_slug' => array(
				'title' => __( 'Redirect Slug', $this->textdomain ),
				'tab'	=> 'general',
				'type' 	=> 'text',
				'default' => 'link',
				'description' => 'The URL redirect slug, ex: http://www.mysite.com/<strong>redirect</strong>/link-1. Go to permalink setting after changing this value.'
			),
		);		
		
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
		$this->options = get_option( $this->slug );	
    }

	
    /**
     * Add options page
	 * https://codex.wordpress.org/Function_Reference/add_submenu_page
	 * add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) 
     */
    function add_menu_page() {
		$page = add_submenu_page( 'edit.php?post_type=link', 'Urlink Settings', 'Settings', 'manage_options', 'setting', array( $this, 'settings_fields' ) );
		add_action( 'admin_print_scripts-' . $page, array( &$this, 'enqueue_script' ) );		
    }
	
	
	/**
     * Add options page
	 * add_theme_page( $page_title, $menu_title, $capability, $menu_slug, $function );
     */
    function enqueue_script() {		
		wp_enqueue_script( $this->slug, URLINK_URL . 'js/jquery.settings.js', array( 'jquery', 'jquery-ui-sortable' ), URLINK_VERSION );	
    }

	
    /**
     * Output nonce, action, and option_page fields for a settings page settings_fields( $option_group )
     */
    function settings_fields() {	
		?>
		<div class="wrap">
			<h2><?php echo $this->title; ?></h2><br />
			<h2 class="nav-tab-wrapper">
				<?php				
				foreach( $this->tabs as $tab => $name ) {
					$class = ( $tab == $this->section ) ? ' nav-tab-active' : '';
					echo "<a class='nav-tab$class' href='./edit.php?post_type=link&page=setting&tab=$tab'>$name</a>";
				}
				?>
			</h2>     
            <form method="post" action="options.php">
            <?php				
				settings_fields( $this->slug );   
				echo "<input name='{$this->slug}[section]' value='{$this->section}' type='hidden' />";	// handle section
				echo '<table class="form-table">';
				do_settings_fields( $this->slug, $this->section );
				echo '</table>';
				submit_button();
            ?>
            </form>
        </div>
        <?php
    }

	
    /**
     * Register and add settings
     * add_settings_field( $id, $title, $callback, $page, $section, $args );
	 * register_setting( $option_group, $option_name, $sanitize_callback );
	 * add_settings_section( $id, $title, $callback, $page );
     */
    function page_init() {
        
		register_setting( $this->slug, $this->slug, array( $this, 'sanitize_callback' ) );

		foreach( $this->tabs as $tab => $name )
			add_settings_section( $tab, '',  '', $this->slug );
		
		foreach( $this->settings as $k => $val ) {
			$val['id'] = $k; // add the id 
			add_settings_field( $k, $val['title'], array( $this, 'setting_field' ), $this->slug, $val['tab'], $val );
		}

		// Create initialize settings if this is the first install
		if( ! get_option( $this->slug ) ) {
			global $wp_settings_fields;
			$sections = $wp_settings_fields[$this->slug];
			$array = array();
			foreach( $sections as $fields ) {
				foreach( $fields as $k => $field ) {
					$array[$k] = isset( $field['args']['default'] ) ? $field['args']['default'] : '';
				}
			}
			
			update_option( $this->slug, $array );
		}		
    }
	
	
    /**
     * Sanitize each setting field as needed
     * @param array $input Contains all settings fields as array keys
     */
    function sanitize_callback( $inputs ) {
		// return if inputs are empty
		if( ! isset( $inputs['section'] ) )
			return $inputs;
			
		global $wp_settings_fields;
		$section = $wp_settings_fields[$this->slug][$inputs['section']];

		foreach( $inputs as $k => $input ) {
			$type = $section[$k]['args']['type'];
			
			if( 'text' == $type ) {
				$this->options[$k] = sanitize_text_field( $input );
			} elseif( 'number' == $type ) {
				$this->options[$k] = absint( $input );
			} elseif( 'url' == $type ) {
				$this->options[$k] = esc_url( $input );
			} else {
				$this->options[$k] = $input;
			}
		}
		
		// Special case for checkbox, we need to loop throught setting fields
		foreach( $section as $k => $field )
			if( 'checkbox' == $field['args']['type'] )
				if( ! isset( $inputs[$k] ) )
					$this->options[$k] = false;			
		
        return $this->options;
    }

	
    public function setting_field( $args ) {
		extract( $args );	
		
		switch ( $type ) {
			case 'textarea':
				printf( '<textarea id="%1$s" name="'.$this->slug.'[%1$s]" cols="50" rows="5" class="large-text">%2$s</textarea>',
					$id, isset( $this->options[$id] ) ? $this->options[$id] : '' );
				break;
				
			case 'checkbox':
				printf( '<label><input id="%1$s" name="'.$this->slug.'[%1$s]" type="checkbox" value="1" %2$s />%3$s</label>',
					$id, $this->options[$id] ? 'checked="checked"' : '', $detail );
				break;
				
			case 'text':
			case 'number':
			case 'url':
			default:
				printf( '<input id="%1$s" name="'.$this->slug.'[%1$s]" value="%2$s" class="regular-text" type="%3$s" />',
					$id, isset( $this->options[$id] ) ? $this->options[$id] : '', $type );
				break;
		}
		
		if ( $description )
			printf('<p class="description">%1$sn</p>', $description );		
    }
}

if( is_admin() )
    new Urlink_Settings();