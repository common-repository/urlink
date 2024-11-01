<?php
/*
    Class: Bee Dialog 0.0.1
    
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


class Bee_Dialog {
	
	var $active_tab;
	var $version;
	var $prefix, $tabs, $fields;
	
	
	/**
	 * Class constructor
	 * This class does not include nonce for security checking
	 * 
	 * @param	$tabs (array) the dialog tabs
	 *			$fields (array) form inputs with value
	 * 			$values (array) the values for each input field
	 * 			$prefix (string) input name
	 * @since 0.0.1
	 */		
	function __construct( $tabs = array(), $fields = array(), $prefix = 'bee', $active_tab = null ) {
		$this->version = '0.0.1';
		$this->prefix = $prefix;
		$this->tabs = $tabs;
		$this->fields = $fields;
		$this->active_tab = $active_tab;
		$this->create_dialog();
	}
	
	
	/**
	 * Return dialog with tab and pane
	 * 
	 * @since 0.0.1
	 */		
	function create_dialog() {
		
		if ( ! $this->tabs ) // only if tabs available
			return;
		?>
		<div class="bee-dialog total-options tabbable tabs-left">
			<?php echo $this->create_tabs(); ?>
			<?php echo $this->create_pane(); ?>
		</div>
		<?php
	}
	
	
	/**
	 * Create the dialog tabs
	 * 
	 * @since 0.0.1
	 */	
	function create_tabs() {
		$output = '';			
		
		$output .= '<ul class="nav nav-tabs">';
			foreach ( $this->tabs as $k => $tab ) {
				$class = $this->active_tab == $k ? 'active' : '';
				$output .= "<li data-tab='$k' class='$class'>$tab</li>";
			}
		$output .= '</ul>';		
		
		return $output;
	}
	
	
	/**
	 * Create dialog input based on input type
	 * @since 0.0.1
	 */	
	function create_pane() {
		$output = '';
		$output .= '<ul class="tab-content">';			
			foreach ( $this->tabs as $k => $tab ) {
				$class = $this->active_tab == $k ? 'active' : '';
				$output .= '<li class="tab-pane ' . $class . '">';
					$output .= '<ul>';						
						foreach ( $this->fields as $key => $option ) {
							if ( $option['tab'] == $k ) {
								$option['id'] = $key;
								$class = 'hidden' == $option['type'] ? 'hidden' : '';
								$output .= "<li class='$class'>". $this->create_input( $option, $this->prefix ) ."</li>";					
							}
						}
					$output .= '</ul>';
				$output .= '</li>';
			}
		$output .= '</ul>';
		$output .= '<script type="text/javascript">
						
					</script>';
		
		return $output;
	}
	
	
	/**
	 * Create dialog input based on input type
	 * 
	 * @since 0.0.1
	 */	
	public static function create_input( $option, $prefix = '' ) {
		
		$output = ''; // hold the output HTML
		
		$default = array(
			'tab' 			=> '',
			'type' 			=> '',
			'id' 			=> '', // unique
			'class'			=> '',
			'label'			=> '',
			'value'			=> '',
			'options'		=> array(),
			'default'		=> '',
			'description'	=> '',
			'attributes'	=> array(),
		);
		
		$option = wp_parse_args( (array) $option, $default );
		
		extract( $option, EXTR_SKIP );
		
		$name = "{$prefix}[$id]";	
		$id = "$prefix-$id";		

		if ( $label )
			$output .= "<label for='$id'>$label</label>";
		if ( $description )
			$output .= "<p class='description'>$description</p>";
		
		switch ( $type ) {
			case 'text':
			case 'number':
			case 'tel':
			case 'hidden':
			default:
				$output .= "<input class='$class' id='$id' name='$name' type='$type' value='$value' />";									
				break;
			
			case 'textarea':
				$output .= "<textarea rows='4' class='widefat $class' id='$id' name='$name' >$value</textarea>";	
				break;
		}
		
		return $output;
	}

}