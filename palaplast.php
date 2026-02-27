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

function palaplast_declare_woocommerce_compatibility() {
	if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
}

function palaplast_init() {
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
