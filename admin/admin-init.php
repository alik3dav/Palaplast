<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'palaplast_register_technical_sheets_menu' );
add_action( 'admin_menu', 'palaplast_register_pricelists_menu' );
add_action( 'admin_enqueue_scripts', 'palaplast_enqueue_admin_assets' );
add_action( 'admin_post_palaplast_save_sheet', 'palaplast_handle_save_sheet' );
add_action( 'admin_post_palaplast_delete_sheet', 'palaplast_handle_delete_sheet' );
add_action( 'admin_post_palaplast_save_pricelist', 'palaplast_handle_save_pricelist' );
add_action( 'admin_post_palaplast_delete_pricelist', 'palaplast_handle_delete_pricelist' );
add_action( 'product_cat_add_form_fields', 'palaplast_render_category_sheet_add_field' );
add_action( 'product_cat_edit_form_fields', 'palaplast_render_category_sheet_edit_field' );
add_action( 'product_cat_add_form_fields', 'palaplast_render_category_pricelist_add_field' );
add_action( 'product_cat_edit_form_fields', 'palaplast_render_category_pricelist_edit_field' );
add_action( 'created_product_cat', 'palaplast_save_category_sheet' );
add_action( 'edited_product_cat', 'palaplast_save_category_sheet' );
add_action( 'created_product_cat', 'palaplast_save_category_pricelist' );
add_action( 'edited_product_cat', 'palaplast_save_category_pricelist' );

function palaplast_register_technical_sheets_menu() {
	add_submenu_page( 'woocommerce', __( 'Technical Sheets', 'palaplast' ), __( 'Technical Sheets', 'palaplast' ), 'manage_woocommerce', 'palaplast-technical-sheets', 'palaplast_render_technical_sheets_page' );
}

function palaplast_register_pricelists_menu() {
	add_submenu_page( 'woocommerce', __( 'Pricelists', 'palaplast' ), __( 'Pricelists', 'palaplast' ), 'manage_woocommerce', 'palaplast-pricelists', 'palaplast_render_pricelists_page' );
}
