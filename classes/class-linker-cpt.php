<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Linker_CPT {
	
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Linker', 'linker' ),
			'singular_name'      => __( 'URL', 'linker' ),
			'add_new'            => __( 'Add New', 'linker' ),
			'add_new_item'       => __( 'Add New URL', 'linker' ),
			'edit'               => __( 'Edit', 'linker' ),
			'edit_item'          => __( 'Edit URL', 'linker' ),
			'new_item'           => __( 'New URL', 'linker' ),
			'view'               => __( 'View URL', 'linker' ),
			'view_item'          => __( 'View URL', 'linker' ),
			'search_items'       => __( 'Search URL', 'linker' ),
			'not_found'          => __( 'No URLs found', 'linker' ),
			'not_found_in_trash' => __( 'No URLs found in Trash', 'linker' ),
		);

		$args = array(
			'labels'          => $labels,
			'public'          => true,
			'query_var'       => true,
			'capability_type' => 'post',
			'has_archive'     => false,
			'hierarchical'    => false,
			'menu_position'   => 30,
			'supports'        => array( 'title' ),
			'rewrite'         => array(
				'slug'       => apply_filters( 'linker_prefix_slug', 'go' ),
				'with_front' => false
			),
		);
		
		register_post_type( 'linker',
			apply_filters( 'linker_register_post_type_args', $args )
		);
	}

	public function register_meta_box() {
		add_meta_box(
			'linker-url-information',
			__( 'URL Information', 'linker' ),
			array( &$this, 'render_meta_box' ),
			'linker',
			'normal',
			'high'
		);
	}

	public function render_meta_box( $post ) {
		wp_nonce_field( basename( __FILE__ ), '_linker_meta_box_nonce' );
		
		$field_id = '_linker_redirect';
		echo strtr( '<p><strong><label for="{name}">{label}</label></strong></p><p><input type="url" id="{name}" name="{name}" value="{value}" placeholder="{placeholder}" class="large-text" /></p>', array(
			'{label}' => __( 'Redirect URI:', 'linker' ),
			'{name}'  => $field_id,
			'{placeholder}' => __( 'http://example.com/your-link', 'linker' ),
			'{value}' => esc_attr( get_post_meta( $post->ID, $field_id, true ) ),
		) );

		$counter = absint( get_post_meta( $post->ID, '_linker_count', true ) );
		printf( '<p class="description">' . __( 'This URL has been accessed <strong>%d</strong> times.', 'linker' ) . '</p>', $counter );
	}

	public function save_post( $post_id ) {
		if ( ! isset( $_POST['_linker_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['_linker_meta_box_nonce'], basename( __FILE__ ) ) )
			return;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			return;

		if ( defined( 'DOING_CRON' ) && DOING_CRON )
			return;
		
		if ( isset( $_POST['_linker_redirect'] ) )
			update_post_meta( $post_id, '_linker_redirect', $_POST['_linker_redirect'] );
		else
			delete_post_meta( $post_id, '_linker_redirect' );
	}

	public function count_and_redirect() {
		if ( ! is_singular( 'linker' ) )
			return;

		$counter = absint( get_post_meta( get_the_ID(), '_linker_count', true ) );
		update_post_meta( get_the_ID(), '_linker_count', ++$counter );

		$redirect_url = esc_url_raw( get_post_meta( get_the_ID(), '_linker_redirect', true ) );
		
		if ( ! empty( $redirect_url ) )
			wp_redirect( $redirect_url, 301 );
		else
			wp_redirect( home_url(), 302 );
		
		die();
	}
	
	public function __construct() {
		// TODO: please add updated messages
		
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'admin_menu', array( &$this, 'register_meta_box' ) );
		add_action( 'save_post', array( &$this, 'save_post' ) );
		add_action( 'template_redirect', array( &$this, 'count_and_redirect' ) );
	}
	
}