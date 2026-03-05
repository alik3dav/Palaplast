<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_after_single_product_summary', 'palaplast_render_matrix_table', 4 );
add_action( 'woocommerce_single_product_summary', 'palaplast_render_technical_sheet_button', 35 );
add_action( 'woocommerce_single_product_summary', 'palaplast_render_pricelist_button', 36 );
add_action( 'wp_enqueue_scripts', 'palaplast_enqueue_styles' );

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

	$custom_rows      = palaplast_get_product_custom_variation_rows( $product->get_id() );
	$custom_rows      = array_values( $custom_rows );
	$custom_row_index = 0;
	$row_count        = 0;
	?>
	<div class="palaplast-matrix">
		<h4 class="palaplast-title"><?php esc_html_e( 'Product Variations', 'palaplast' ); ?></h4>
		<div class="palaplast-table-wrap">
			<table class="palaplast-table" aria-label="<?php esc_attr_e( 'Product variation matrix', 'palaplast' ); ?>">
				<thead><tr><th scope="col" class="col-sku"><?php esc_html_e( 'SKU', 'palaplast' ); ?></th><?php foreach ( $attributes as $attr_name ) : ?><th scope="col" class="col-attr"><?php echo wp_kses_post( palaplast_get_variation_header_html( wc_attribute_label( $attr_name ) ) ); ?></th><?php endforeach; ?></tr></thead>
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
						<?php
						$row_count++;
						while ( isset( $custom_rows[ $custom_row_index ] ) && (int) $custom_rows[ $custom_row_index ]['position'] === $row_count ) {
							palaplast_render_custom_variation_table_row( $custom_rows[ $custom_row_index ], count( $attributes ) + 1 );
							$custom_row_index++;
						}
						?>
					<?php endforeach; ?>
					<?php
					while ( isset( $custom_rows[ $custom_row_index ] ) ) {
						palaplast_render_custom_variation_table_row( $custom_rows[ $custom_row_index ], count( $attributes ) + 1 );
						$custom_row_index++;
					}
					?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}

function palaplast_get_product_custom_variation_rows( $product_id ) {
	$rows = get_post_meta( $product_id, '_palaplast_variation_table_custom_rows', true );
	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return array();
	}

	$clean_rows = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) || empty( $row['enabled'] ) ) {
			continue;
		}

		$text = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';
		if ( '' === $text ) {
			continue;
		}

		$style = isset( $row['style'] ) ? sanitize_key( (string) $row['style'] ) : 'info';
		if ( ! in_array( $style, array( 'info', 'warning', 'note' ), true ) ) {
			$style = 'info';
		}

		$clean_rows[] = array(
			'position' => max( 1, (int) $row['position'] ),
			'text'     => $text,
			'style'    => $style,
		);
	}

	return $clean_rows;
}

function palaplast_render_custom_variation_table_row( $custom_row, $colspan ) {
	if ( ! is_array( $custom_row ) ) {
		return;
	}

	$allowed_tags = array(
		'br'     => array(),
		'strong' => array(),
		'em'     => array(),
		'b'      => array(),
		'i'      => array(),
		'a'      => array(
			'href'   => array(),
			'target' => array(),
			'rel'    => array(),
		),
	);

	$style      = isset( $custom_row['style'] ) ? sanitize_key( (string) $custom_row['style'] ) : 'info';
	$text       = isset( $custom_row['text'] ) ? (string) $custom_row['text'] : '';
	$safe_text  = wp_kses( $text, $allowed_tags );
	$style      = in_array( $style, array( 'info', 'warning', 'note' ), true ) ? $style : 'info';
	$colspan    = max( 1, (int) $colspan );
	$row_class  = sprintf( 'vt-custom-row vt-custom-row--%s', $style );
	$content    = nl2br( $safe_text );

	echo '<tr class="' . esc_attr( $row_class ) . '"><td colspan="' . esc_attr( (string) $colspan ) . '"><div class="vt-custom-row__content">' . wp_kses( $content, $allowed_tags ) . '</div></td></tr>';
}

function palaplast_get_variation_header_html( $label ) {
	$label       = wp_strip_all_tags( (string) $label );
	$open_pos    = strpos( $label, '(' );
	$close_pos   = strrpos( $label, ')' );
	$parsed_html = esc_html( $label );

	if ( false === $open_pos || false === $close_pos || $close_pos <= $open_pos ) {
		return $parsed_html;
	}

	$title = trim( substr( $label, 0, $open_pos ) );
	$unit  = trim( substr( $label, $open_pos + 1, $close_pos - $open_pos - 1 ) );

	if ( '' === $unit ) {
		return $parsed_html;
	}

	if ( '' === $title ) {
		$title = trim( $label );
	}

	return sprintf(
		'<span class="spec-title">%1$s</span><span class="spec-unit">%2$s</span>',
		esc_html( $title ),
		esc_html( $unit )
	);
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
