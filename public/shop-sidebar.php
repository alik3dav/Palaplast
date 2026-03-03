<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'palaplast_maybe_hide_shop_sidebar', 20 );
add_filter( 'body_class', 'palaplast_add_no_shop_sidebar_body_class' );
add_action( 'wp_enqueue_scripts', 'palaplast_enqueue_shop_sidebar_fallback_styles' );

function palaplast_should_hide_shop_sidebar() {
	static $should_hide = null;

	if ( null !== $should_hide ) {
		return $should_hide;
	}

	$should_hide = false;

	if ( is_admin() || ! is_tax( 'product_cat' ) ) {
		return $should_hide;
	}

	$current_term = get_queried_object();
	if ( ! $current_term instanceof WP_Term || 'product_cat' !== $current_term->taxonomy ) {
		return $should_hide;
	}

	$child_term_ids = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => (int) $current_term->term_id,
			'number'     => 1,
			'fields'     => 'ids',
			'hide_empty' => false,
		)
	);

	if ( is_wp_error( $child_term_ids ) || empty( $child_term_ids ) ) {
		return $should_hide;
	}

	$should_hide = true;
	return $should_hide;
}

function palaplast_maybe_hide_shop_sidebar() {
	if ( ! palaplast_should_hide_shop_sidebar() ) {
		return;
	}

	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
}

function palaplast_add_no_shop_sidebar_body_class( $classes ) {
	if ( palaplast_should_hide_shop_sidebar() ) {
		$classes[] = 'myplugin-no-shop-sidebar';
	}

	return $classes;
}

function palaplast_enqueue_shop_sidebar_fallback_styles() {
	if ( ! palaplast_should_hide_shop_sidebar() ) {
		return;
	}

	wp_enqueue_style(
		'palaplast-shop-sidebar',
		PALAPLAST_PLUGIN_URL . 'public/css/shop-sidebar.css',
		array(),
		PALAPLAST_VERSION
	);
}
