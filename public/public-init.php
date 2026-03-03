<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_after_single_product_summary', 'palaplast_render_matrix_table', 4 );
add_action( 'woocommerce_single_product_summary', 'palaplast_render_technical_sheet_button', 35 );
add_action( 'woocommerce_single_product_summary', 'palaplast_render_pricelist_button', 36 );
add_action( 'wp_enqueue_scripts', 'palaplast_enqueue_styles' );
add_filter( 'woocommerce_hide_invisible_variations', 'palaplast_show_variations_without_price', 10, 3 );
add_filter( 'woocommerce_get_price_html', 'palaplast_hide_catalog_prices', 10, 2 );
add_filter( 'woocommerce_available_variation', 'palaplast_remove_variation_price_payload', 10, 3 );
add_filter( 'woocommerce_show_variation_price', '__return_false' );
add_filter( 'woocommerce_variation_option_name', 'palaplast_preserve_variation_option_name', 10, 4 );

function palaplast_render_matrix_table() {
	global $product;

	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return;
	}

	$available_variations = $product->get_available_variations();
	$attributes           = array_keys( $product->get_variation_attributes() );
	if ( empty( $available_variations ) ) {
		return;
	}
	?>
	<div class="palaplast-matrix">
		<h4 class="palaplast-title"><?php esc_html_e( 'Product Variations', 'palaplast' ); ?></h4>
		<div class="palaplast-table-wrap">
			<table class="palaplast-table" aria-label="<?php esc_attr_e( 'Product variation matrix', 'palaplast' ); ?>">
				<thead><tr><th scope="col" class="col-sku"><?php esc_html_e( 'SKU', 'palaplast' ); ?></th><?php foreach ( $attributes as $attr_name ) : ?><th scope="col" class="col-attr"><?php echo esc_html( wc_attribute_label( $attr_name ) ); ?></th><?php endforeach; ?></tr></thead>
				<tbody>
					<?php foreach ( $available_variations as $variation ) :
						$variation_id  = isset( $variation['variation_id'] ) ? (int) $variation['variation_id'] : 0;
						$variation_obj = wc_get_product( $variation_id );
						if ( ! $variation_obj instanceof WC_Product_Variation ) {
							continue;
						}
						?>
						<tr>
							<td class="col-sku"><?php echo esc_html( $variation_obj->get_sku() ? $variation_obj->get_sku() : '—' ); ?></td>
							<?php foreach ( $attributes as $attr_name ) : $attribute_key = 'attribute_' . sanitize_title( $attr_name ); $value_raw = isset( $variation['attributes'][ $attribute_key ] ) ? $variation['attributes'][ $attribute_key ] : ''; $value = palaplast_get_attribute_value( $product, $attr_name, $value_raw ); ?>
								<td class="col-attr"><?php echo esc_html( $value ); ?></td>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

function palaplast_get_attribute_value( $product, $attr_name, $value_raw ) {
	$value_raw = rawurldecode( wp_unslash( (string) $value_raw ) );
	if ( '' === $value_raw ) {
		return '—';
	}

	if ( taxonomy_exists( $attr_name ) ) {
		$term = get_term_by( 'slug', $value_raw, $attr_name );
		if ( ! $term instanceof WP_Term ) {
			$term = get_term_by( 'name', $value_raw, $attr_name );
		}
		if ( $term instanceof WP_Term ) {
			return $term->name;
		}
	}

	$resolved_custom_value = palaplast_resolve_custom_attribute_value( $product, $attr_name, $value_raw );
	if ( '' !== $resolved_custom_value ) {
		return $resolved_custom_value;
	}

	return wc_clean( $value_raw );
}

function palaplast_preserve_variation_option_name( $option_name, $option = null, $attribute = '', $product = null ) {
	if ( ! $product instanceof WC_Product ) {
		global $product;
		$product = $product instanceof WC_Product ? $product : null;
	}

	if ( ! is_scalar( $option ) ) {
		return $option_name;
	}

	$option_value = rawurldecode( wp_unslash( (string) $option ) );
	if ( taxonomy_exists( $attribute ) ) {
		$term = get_term_by( 'slug', $option_value, $attribute );
		if ( ! $term instanceof WP_Term ) {
			$term = get_term_by( 'name', $option_value, $attribute );
		}
		if ( $term instanceof WP_Term ) {
			return $term->name;
		}
	}

	if ( $product instanceof WC_Product ) {
		$resolved_custom_value = palaplast_resolve_custom_attribute_value( $product, $attribute, $option_value );
		if ( '' !== $resolved_custom_value ) {
			return $resolved_custom_value;
		}
	}

	return $option_name;
}

function palaplast_resolve_custom_attribute_value( $product, $attribute, $current_value ) {
	$attributes = $product->get_attributes();
	if ( ! isset( $attributes[ $attribute ] ) || ! is_a( $attributes[ $attribute ], 'WC_Product_Attribute' ) ) {
		return '';
	}

	$options = $attributes[ $attribute ]->get_options();
	if ( empty( $options ) ) {
		return '';
	}

	$normalized_current_value = palaplast_normalize_attribute_value( $current_value );
	foreach ( $options as $option ) {
		$option = rawurldecode( wp_unslash( (string) $option ) );
		if ( $option === $current_value || palaplast_normalize_attribute_value( $option ) === $normalized_current_value ) {
			return $option;
		}
	}

	return '';
}

function palaplast_normalize_attribute_value( $value ) {
	return sanitize_title( rawurldecode( wp_unslash( (string) $value ) ) );
}

function palaplast_show_variations_without_price( $hide, $product_id = 0, $variation = null ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $hide;
	}

	return false;
}

function palaplast_hide_catalog_prices( $price_html, $product ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $price_html;
	}

	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	return '';
}

function palaplast_remove_variation_price_payload( $variation_data, $product, $variation ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $variation_data;
	}

	$variation_data['price_html']            = '';
	$variation_data['display_price']         = 0;
	$variation_data['display_regular_price'] = 0;

	return $variation_data;
}
