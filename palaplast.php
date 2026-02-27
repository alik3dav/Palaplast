<?php
/**
 * Plugin Name: Palaplast
 * Description: Displays a clean, compact variation matrix (SKU + attributes + price) above the product tabs for variable WooCommerce products.
 * Version: 1.8.0
 * Author: Palaplast
 * License: GPL-2.0-or-later
 * Text Domain: palaplast
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PALAPLAST_VERSION', '1.8.0' );
define( 'PALAPLAST_PLUGIN_FILE', __FILE__ );
define( 'PALAPLAST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PALAPLAST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

add_action( 'before_woocommerce_init', 'palaplast_declare_woocommerce_compatibility' );
add_action( 'plugins_loaded', 'palaplast_init' );
add_action( 'init', 'palaplast_maybe_flush_rewrite_rules', 20 );

register_activation_hook( PALAPLAST_PLUGIN_FILE, 'palaplast_activate' );
register_deactivation_hook( PALAPLAST_PLUGIN_FILE, 'palaplast_deactivate' );

function palaplast_declare_woocommerce_compatibility() {
	if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
}

function palaplast_init() {
	require_once PALAPLAST_PLUGIN_DIR . 'includes/events.php';

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	require_once PALAPLAST_PLUGIN_DIR . 'includes/helpers.php';
	require_once PALAPLAST_PLUGIN_DIR . 'includes/taxonomy-fields.php';
	require_once PALAPLAST_PLUGIN_DIR . 'includes/assets.php';

	if ( is_admin() ) {
		require_once PALAPLAST_PLUGIN_DIR . 'admin/technical-sheets-page.php';
		require_once PALAPLAST_PLUGIN_DIR . 'admin/pricelists-page.php';
		require_once PALAPLAST_PLUGIN_DIR . 'admin/admin-init.php';
		return;
	}

	require_once PALAPLAST_PLUGIN_DIR . 'public/product-pdf-buttons.php';
	require_once PALAPLAST_PLUGIN_DIR . 'public/public-init.php';
}

function palaplast_activate() {
	require_once PALAPLAST_PLUGIN_DIR . 'includes/events.php';
	palaplast_register_event_post_type();
	flush_rewrite_rules();
	update_option( 'palaplast_version', PALAPLAST_VERSION );
}

function palaplast_deactivate() {
	flush_rewrite_rules();
}

function palaplast_maybe_flush_rewrite_rules() {
	$installed_version = get_option( 'palaplast_version' );

	if ( PALAPLAST_VERSION === $installed_version ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'palaplast_version', PALAPLAST_VERSION );
}
