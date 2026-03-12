<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'body_class', 'palaplast_add_product_cat_filter_state_body_class' );
add_action( 'wp_enqueue_scripts', 'palaplast_enqueue_product_cat_filter_state_assets' );

function palaplast_should_hide_product_cat_filters() {
	static $should_hide = null;

	if ( null !== $should_hide ) {
		return $should_hide;
	}

	$should_hide = false;

	if ( is_admin() || ! is_tax( 'product_cat' ) ) {
		return $should_hide;
	}

	$current_term = get_queried_object();
	if ( ! ( $current_term instanceof WP_Term ) || 'product_cat' !== $current_term->taxonomy ) {
		return $should_hide;
	}

	$child_terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => (int) $current_term->term_id,
			'number'     => 1,
			'fields'     => 'ids',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $child_terms ) || empty( $child_terms ) ) {
		return $should_hide;
	}

	$should_hide = true;

	return $should_hide;
}

function palaplast_add_product_cat_filter_state_body_class( $classes ) {
	if ( palaplast_should_hide_product_cat_filters() ) {
		$classes[] = 'palaplast-hide-product-cat-filters';
	}

	return $classes;
}

function palaplast_enqueue_product_cat_filter_state_assets() {
	if ( ! palaplast_should_hide_product_cat_filters() ) {
		return;
	}

	wp_enqueue_style(
		'palaplast-shop-sidebar',
		PALAPLAST_PLUGIN_URL . 'public/css/shop-sidebar.css',
		array(),
		PALAPLAST_VERSION
	);

	wp_enqueue_script(
		'palaplast-shop-sidebar',
		PALAPLAST_PLUGIN_URL . 'public/js/shop-sidebar.js',
		array(),
		PALAPLAST_VERSION,
		true
	);
}
