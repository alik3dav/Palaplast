<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'palaplast_register_custom_post_types', 5 );

function palaplast_register_custom_post_types() {
	$certificate_labels = array(
		'name'               => __( 'Certificates', 'palaplast' ),
		'singular_name'      => __( 'Certificate', 'palaplast' ),
		'menu_name'          => __( 'Certificates', 'palaplast' ),
		'name_admin_bar'     => __( 'Certificate', 'palaplast' ),
		'add_new'            => __( 'Add New', 'palaplast' ),
		'add_new_item'       => __( 'Add New Certificate', 'palaplast' ),
		'new_item'           => __( 'New Certificate', 'palaplast' ),
		'edit_item'          => __( 'Edit Certificate', 'palaplast' ),
		'view_item'          => __( 'View Certificate', 'palaplast' ),
		'all_items'          => __( 'All Certificates', 'palaplast' ),
		'search_items'       => __( 'Search Certificates', 'palaplast' ),
		'not_found'          => __( 'No certificates found.', 'palaplast' ),
		'not_found_in_trash' => __( 'No certificates found in Trash.', 'palaplast' ),
	);

	register_post_type(
		'palaplast_certificate',
		array(
			'labels'             => $certificate_labels,
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => 'woocommerce',
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'exclude_from_search'=> true,
			'publicly_queryable' => false,
			'has_archive'        => false,
			'query_var'          => false,
			'rewrite'            => false,
			'map_meta_cap'       => true,
			'capability_type'    => 'post',
			'menu_icon'          => 'dashicons-awards',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
		)
	);
}
