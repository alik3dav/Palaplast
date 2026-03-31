<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function &palaplast_get_rendered_pdf_buttons() {
	static $rendered = array(
		'technical_sheet' => array(),
		'pricelist'       => array(),
	);

	return $rendered;
}

function palaplast_pdf_button_already_rendered( $type, $product_id ) {
	$rendered = &palaplast_get_rendered_pdf_buttons();

	$product_id = (int) $product_id;
	if ( ! $product_id || empty( $rendered[ $type ] ) ) {
		return false;
	}

	return ! empty( $rendered[ $type ][ $product_id ] );
}

function palaplast_mark_pdf_button_rendered( $type, $product_id ) {
	$rendered = &palaplast_get_rendered_pdf_buttons();

	$product_id = (int) $product_id;
	if ( ! $product_id || ! isset( $rendered[ $type ] ) ) {
		return;
	}

	$rendered[ $type ][ $product_id ] = true;
}

function palaplast_render_technical_sheet_button() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	palaplast_render_technical_sheet_button_for_product();
}

function palaplast_render_technical_sheet_button_for_product( $product_id = 0, $return_html = false ) {
	$product = palaplast_get_product_for_pdf_output( $product_id );

	if ( ! $product instanceof WC_Product ) {
		return $return_html ? '' : null;
	}

	$product_id = $product->get_id();

	if ( function_exists( 'palaplast_get_current_language_product_id' ) ) {
		$product_id = palaplast_get_current_language_product_id( $product_id );
	}

	if ( ! $return_html && palaplast_pdf_button_already_rendered( 'technical_sheet', $product_id ) ) {
		return null;
	}

	$sheet = palaplast_get_product_technical_sheet( $product_id );
	if ( empty( $sheet['file_url'] ) ) {
		return $return_html ? '' : null;
	}

	ob_start();
	?>
	<p class="palaplast-technical-sheet">
		<a class="button palaplast-technical-sheet-button" href="<?php echo esc_url( $sheet['file_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Technical Sheet', 'palaplast' ); ?></a>
	</p>
	<?php

	$output = ob_get_clean();

	if ( $return_html ) {
		return $output;
	}

	palaplast_mark_pdf_button_rendered( 'technical_sheet', $product_id );
	echo $output;

	return null;
}

function palaplast_render_pricelist_button() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	palaplast_render_pricelist_button_for_product();
}

function palaplast_render_pricelist_button_for_product( $product_id = 0, $return_html = false ) {
	$product = palaplast_get_product_for_pdf_output( $product_id );

	if ( ! $product instanceof WC_Product ) {
		return $return_html ? '' : null;
	}

	$product_id = $product->get_id();

	if ( function_exists( 'palaplast_get_current_language_product_id' ) ) {
		$product_id = palaplast_get_current_language_product_id( $product_id );
	}

	if ( ! $return_html && palaplast_pdf_button_already_rendered( 'pricelist', $product_id ) ) {
		return null;
	}

	$pricelist = palaplast_get_product_pricelist( $product_id );
	if ( empty( $pricelist['file_url'] ) ) {
		return $return_html ? '' : null;
	}

	ob_start();
	?>
	<p class="palaplast-pricelist">
		<a class="button palaplast-pricelist-button" href="<?php echo esc_url( $pricelist['file_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Pricelist', 'palaplast' ); ?></a>
	</p>
	<?php

	$output = ob_get_clean();

	if ( $return_html ) {
		return $output;
	}

	palaplast_mark_pdf_button_rendered( 'pricelist', $product_id );
	echo $output;

	return null;
}

function palaplast_technical_sheet_button_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'product_id' => 0,
		),
		$atts,
		'palaplast_technical_sheet'
	);

	return palaplast_render_technical_sheet_button_for_product( (int) $atts['product_id'], true );
}

function palaplast_pricelist_button_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'product_id' => 0,
		),
		$atts,
		'palaplast_pricelist_pdf'
	);

	return palaplast_render_pricelist_button_for_product( (int) $atts['product_id'], true );
}

function palaplast_technical_sheets_list_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'      => __( 'Technical Sheets', 'palaplast' ),
			'show_title' => 'yes',
		),
		$atts,
		'palaplast_technical_sheets_list'
	);

	return palaplast_render_pdf_list_shortcode(
		palaplast_get_technical_sheets(),
		$atts,
		'palaplast-technical-sheets-list',
		'palaplast-technical-sheets-list-title'
	);
}

function palaplast_pricelists_list_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'      => __( 'Pricelists', 'palaplast' ),
			'show_title' => 'yes',
		),
		$atts,
		'palaplast_pricelists_list'
	);

	return palaplast_render_pdf_list_shortcode(
		palaplast_get_pricelists(),
		$atts,
		'palaplast-pricelists-list',
		'palaplast-pricelists-list-title'
	);
}

function palaplast_render_pdf_list_shortcode( $items, $atts, $wrapper_class, $title_class ) {
	if ( empty( $items ) || ! is_array( $items ) ) {
		return '';
	}

	$show_title  = isset( $atts['show_title'] ) ? wp_validate_boolean( $atts['show_title'] ) : true;
	$title       = isset( $atts['title'] ) ? sanitize_text_field( (string) $atts['title'] ) : '';
	$valid_items = array();

	foreach ( $items as $item ) {
		$item_name     = isset( $item['name'] ) ? (string) $item['name'] : '';
		$attachment_id = isset( $item['attachment_id'] ) ? (int) $item['attachment_id'] : 0;
		$file_url      = $attachment_id ? wp_get_attachment_url( $attachment_id ) : '';

		if ( '' === $item_name || ! $file_url ) {
			continue;
		}

		$valid_items[] = array(
			'name'     => $item_name,
			'file_url' => $file_url,
		);
	}

	if ( empty( $valid_items ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr( $wrapper_class ); ?>">
		<?php if ( $show_title && '' !== $title ) : ?>
			<h3 class="<?php echo esc_attr( $title_class ); ?>"><?php echo esc_html( $title ); ?></h3>
		<?php endif; ?>
		<ul class="palaplast-pdf-list" role="list">
			<?php foreach ( $valid_items as $item ) : ?>
				<li class="palaplast-pdf-list-item">
					<div class="palaplast-pdf-list-item__title"><?php echo esc_html( $item['name'] ); ?></div>
					<a class="palaplast-pdf-list-item__action" href="<?php echo esc_url( $item['file_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open PDF', 'palaplast' ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php

	return ob_get_clean();
}

function palaplast_get_product_for_pdf_output( $product_id = 0 ) {
	$product_id = (int) $product_id;

	if ( $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product instanceof WC_Product ) {
			return $product;
		}
	}

	global $product;
	if ( $product instanceof WC_Product ) {
		return $product;
	}

	$current_post_id = get_the_ID();
	if ( ! $current_post_id ) {
		return null;
	}

	$product = wc_get_product( $current_post_id );

	return $product instanceof WC_Product ? $product : null;
}
