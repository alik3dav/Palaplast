<?php
/**
 * Plugin Name: Palaplast
 * Description: Displays a clean, compact variation matrix (SKU + attributes + price) above the product tabs for variable WooCommerce products.
 * Version: 1.6.2
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

add_action( 'before_woocommerce_init', 'palaplast_declare_woocommerce_compatibility' );

/**
 * Declare compatibility with WooCommerce features that trigger plugin compatibility checks.
 *
 * @return void
 */
function palaplast_declare_woocommerce_compatibility() {
	if ( ! class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		return;
	}

	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
}

if ( ! class_exists( 'Palaplast_Variation_Matrix' ) ) {
	/**
	 * Render a variation matrix for variable products.
	 */
	final class Palaplast_Variation_Matrix {
		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		/**
		 * Initialize plugin hooks once dependencies are ready.
		 *
		 * @return void
		 */
		public function init() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			add_action( 'woocommerce_after_single_product_summary', array( $this, 'render_matrix_table' ), 4 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_filter( 'woocommerce_hide_invisible_variations', array( $this, 'show_variations_without_price' ), 10, 3 );
			add_filter( 'woocommerce_get_price_html', array( $this, 'hide_catalog_prices' ), 10, 2 );
			add_filter( 'woocommerce_available_variation', array( $this, 'remove_variation_price_payload' ), 10, 3 );
			add_filter( 'woocommerce_show_variation_price', '__return_false' );
			add_filter( 'woocommerce_variation_option_name', array( $this, 'preserve_variation_option_name' ), 10, 4 );
		}

		/**
		 * Enqueue frontend styles only on single product pages.
		 *
		 * @return void
		 */
		public function enqueue_styles() {
			if ( ! function_exists( 'is_product' ) || ! is_product() ) {
				return;
			}

			wp_register_style( 'palaplast', false, array(), '1.6.2' );
			wp_enqueue_style( 'palaplast' );
			wp_add_inline_style( 'palaplast', $this->get_styles() );
		}

		/**
		 * Render the variation matrix table for variable products.
		 *
		 * @return void
		 */
		public function render_matrix_table() {
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
						<thead>
							<tr>
								<th scope="col" class="col-sku"><?php esc_html_e( 'SKU', 'palaplast' ); ?></th>
								<?php foreach ( $attributes as $attr_name ) : ?>
									<th scope="col" class="col-attr"><?php echo esc_html( wc_attribute_label( $attr_name ) ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $available_variations as $variation ) : ?>
								<?php
								$variation_id  = isset( $variation['variation_id'] ) ? (int) $variation['variation_id'] : 0;
								$variation_obj = wc_get_product( $variation_id );

								if ( ! $variation_obj instanceof WC_Product_Variation ) {
									continue;
								}
								?>
								<tr>
									<td class="col-sku"><?php echo esc_html( $variation_obj->get_sku() ? $variation_obj->get_sku() : '—' ); ?></td>
									<?php foreach ( $attributes as $attr_name ) : ?>
										<?php
										$attribute_key = 'attribute_' . sanitize_title( $attr_name );
										$value_raw = isset( $variation['attributes'][ $attribute_key ] ) ? $variation['attributes'][ $attribute_key ] : '';
										$value     = $this->get_attribute_value( $product, $attr_name, $value_raw );
										?>
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

		/**
		 * Resolve attribute labels (term name or raw custom value).
		 *
		 * @param WC_Product $product   Parent variable product.
		 * @param string     $attr_name Attribute taxonomy key.
		 * @param string     $value_raw Stored value from variation data.
		 *
		 * @return string
		 */
		private function get_attribute_value( $product, $attr_name, $value_raw ) {
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

			$resolved_custom_value = $this->resolve_custom_attribute_value( $product, $attr_name, $value_raw );

			if ( '' !== $resolved_custom_value ) {
				return $resolved_custom_value;
			}

			return wc_clean( $value_raw );
		}

		/**
		 * Return a human-readable option label in variation selectors.
		 *
		 * @param string              $option_name Existing option label.
		 * @param string|int|WP_Term  $option      Option value.
		 * @param string              $attribute   Attribute key.
		 * @param WC_Product|null     $product     Product context.
		 *
		 * @return string
		 */
		public function preserve_variation_option_name( $option_name, $option = null, $attribute = '', $product = null ) {
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
				$resolved_custom_value = $this->resolve_custom_attribute_value( $product, $attribute, $option_value );

				if ( '' !== $resolved_custom_value ) {
					return $resolved_custom_value;
				}
			}

			return $option_name;
		}

		/**
		 * Resolve a custom attribute option to its original value as entered in product settings.
		 *
		 * @param WC_Product $product      Product object.
		 * @param string     $attribute    Attribute key.
		 * @param string     $current_value Current stored value.
		 *
		 * @return string
		 */
		private function resolve_custom_attribute_value( $product, $attribute, $current_value ) {
			$attributes = $product->get_attributes();

			if ( ! isset( $attributes[ $attribute ] ) || ! is_a( $attributes[ $attribute ], 'WC_Product_Attribute' ) ) {
				return '';
			}

			$options = $attributes[ $attribute ]->get_options();

			if ( empty( $options ) ) {
				return '';
			}

			$normalized_current_value = $this->normalize_attribute_value( $current_value );

			foreach ( $options as $option ) {
				$option = rawurldecode( wp_unslash( (string) $option ) );

				if ( $option === $current_value || $this->normalize_attribute_value( $option ) === $normalized_current_value ) {
					return $option;
				}
			}

			return '';
		}

		/**
		 * Normalize an attribute value for comparisons without changing displayed text.
		 *
		 * @param string $value Attribute value.
		 *
		 * @return string
		 */
		private function normalize_attribute_value( $value ) {
			return sanitize_title( rawurldecode( wp_unslash( (string) $value ) ) );
		}

		/**
		 * Keep variations selectable even when no price is set.
		 *
		 * @param bool $hide Whether invisible variations should be hidden.
		 *
		 * @return bool
		 */
		public function show_variations_without_price( $hide, $product_id = 0, $variation = null ) {
			if ( is_admin() && ! wp_doing_ajax() ) {
				return $hide;
			}

			return false;
		}

		/**
		 * Remove frontend price HTML to keep catalog mode active.
		 *
		 * @param string     $price_html Existing price HTML.
		 * @param WC_Product $product    Product object.
		 *
		 * @return string
		 */
		public function hide_catalog_prices( $price_html, $product ) {
			if ( is_admin() && ! wp_doing_ajax() ) {
				return $price_html;
			}

			if ( ! $product instanceof WC_Product ) {
				return '';
			}

			return '';
		}

		/**
		 * Keep variation payload valid while removing price fragments in catalog mode.
		 *
		 * @param array                $variation_data Variation payload.
		 * @param WC_Product           $product        Parent product.
		 * @param WC_Product_Variation $variation      Variation product.
		 *
		 * @return array
		 */
		public function remove_variation_price_payload( $variation_data, $product, $variation ) {
			if ( is_admin() && ! wp_doing_ajax() ) {
				return $variation_data;
			}

			$variation_data['price_html']     = '';
			$variation_data['display_price']  = 0;
			$variation_data['display_regular_price'] = 0;

			return $variation_data;
		}

		/**
		 * Plugin styles.
		 *
		 * @return string
		 */
		private function get_styles() {
			return '.palaplast-matrix{margin-top:10px;margin-bottom:30px}.palaplast-title{font-size:14px;font-weight:500;margin-bottom:10px;color:#222}.palaplast-table-wrap{overflow-x:auto}.palaplast-table{width:100%;border-collapse:collapse;font-size:13px;line-height:1.4}.palaplast-table th,.palaplast-table td{border-bottom:1px solid #eee;padding:6px 10px;vertical-align:middle;white-space:nowrap}.palaplast-table .col-sku{text-align:left}.palaplast-table .col-attr{text-align:center}.palaplast-table th{font-weight:600;color:#333;background:#fafafa}.palaplast-table tr:last-child td{border-bottom:none}@media (max-width:768px){.palaplast-table{font-size:12px}.palaplast-table th,.palaplast-table td{padding:5px 6px}}';
		}
	}

	new Palaplast_Variation_Matrix();
}
