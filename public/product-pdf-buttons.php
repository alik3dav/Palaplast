<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_render_technical_sheet_button() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$sheet = palaplast_get_product_technical_sheet( $product->get_id() );
	if ( empty( $sheet['file_url'] ) ) {
		return;
	}
	?>
	<p class="palaplast-technical-sheet">
		<a class="button palaplast-technical-sheet-button" href="<?php echo esc_url( $sheet['file_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Download Technical Sheet', 'palaplast' ); ?></a>
	</p>
	<?php
}

function palaplast_render_pricelist_button() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$pricelist = palaplast_get_product_pricelist( $product->get_id() );
	if ( empty( $pricelist['file_url'] ) ) {
		return;
	}
	?>
	<p class="palaplast-pricelist">
		<a class="button palaplast-pricelist-button" href="<?php echo esc_url( $pricelist['file_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Download Pricelist', 'palaplast' ); ?></a>
	</p>
	<?php
}
