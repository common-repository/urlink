<?php
/*
    Class: Post Type
    
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

class Urlink_Post_Type {
	
	var $textdomain;
	var $post_type;
	
	function __construct() {
		$this->post_type = 'link';
		$this->textdomain = URLINK_SLUG;
		$this->slug = URLINK_SLUG;
		$this->options = get_option( $this->slug );	
		
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'init', array( &$this, 'register_taxonomy' ) );
	}

	
	function register_post_type() {
		$labels = array(
			'name'               => _x( 'Links', 'post type general name', $this->textdomain ),
			'singular_name'      => _x( 'Link', 'post type singular name', $this->textdomain ),
			'menu_name'          => _x( 'Links', 'admin menu', $this->textdomain ),
			'name_admin_bar'     => _x( 'Link', 'add new on admin bar', $this->textdomain ),
			'add_new'            => _x( 'Add New', 'Link', $this->textdomain ),
			'add_new_item'       => __( 'Add New Link', $this->textdomain ),
			'new_item'           => __( 'New Link', $this->textdomain ),
			'edit_item'          => __( 'Edit Link', $this->textdomain ),
			'view_item'          => __( 'View Link', $this->textdomain ),
			'all_items'          => __( 'All Links', $this->textdomain ),
			'search_items'       => __( 'Search Links', $this->textdomain ),
			'parent_item_colon'  => __( 'Parent Links:', $this->textdomain ),
			'not_found'          => __( 'No Links found.', $this->textdomain ),
			'not_found_in_trash' => __( 'No Links found in Trash.', $this->textdomain )
		);

		$args = array(
			'labels'             => $labels,
			'description'        => __( 'Description.', $this->textdomain ),
			'public'             => true,
			'menu_icon'			 => 'dashicons-admin-links',
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => $this->options['redirect_slug'] ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' )
		);

		register_post_type( 'link', $args );
	}
	
		
	// create two taxonomies, genres and writers for the post type "book"
	function register_taxonomy() {
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Categories', $this->textdomain ),
			'singular_name'     => _x( 'Category', $this->textdomain ),
			'search_items'      => __( 'Search Categories', $this->textdomain ),
			'all_items'         => __( 'All Categories', $this->textdomain ),
			'parent_item'       => __( 'Parent Category', $this->textdomain ),
			'parent_item_colon' => __( 'Parent Category:', $this->textdomain ),
			'edit_item'         => __( 'Edit Category', $this->textdomain ),
			'update_item'       => __( 'Update Category', $this->textdomain ),
			'add_new_item'      => __( 'Add New Category', $this->textdomain ),
			'new_item_name'     => __( 'New Category Name', $this->textdomain ),
			'menu_name'         => __( 'Categories', $this->textdomain ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'link-category' ),
		);

		register_taxonomy( 'link_category', array( 'link' ), $args );

		
		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name'                       => _x( 'Tags', $this->textdomain ),
			'singular_name'              => _x( 'Tag', $this->textdomain ),
			'search_items'               => __( 'Search Tags', $this->textdomain ),
			'popular_items'              => __( 'Popular Tags', $this->textdomain ),
			'all_items'                  => __( 'All Tags', $this->textdomain ),
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => __( 'Edit Tag', $this->textdomain ),
			'update_item'                => __( 'Update Tag', $this->textdomain ),
			'add_new_item'               => __( 'Add New Tag', $this->textdomain ),
			'new_item_name'              => __( 'New Tag Name', $this->textdomain ),
			'separate_items_with_commas' => __( 'Separate writers with commas', $this->textdomain ),
			'add_or_remove_items'        => __( 'Add or remove writers', $this->textdomain ),
			'choose_from_most_used'      => __( 'Choose from the most used writers', $this->textdomain ),
			'not_found'                  => __( 'No writers found.', $this->textdomain ),
			'menu_name'                  => __( 'Tags', $this->textdomain ),
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'link-tag' ),
		);

		register_taxonomy( 'link_tag', 'link', $args );
	}
	
} new Urlink_Post_Type();