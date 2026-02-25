<?php
/**
 * Plugin Name: HydroMax Variation Matrix
 * Description: Displays a clean, compact variation matrix (SKU + attributes + price) above the product tabs for variable WooCommerce products.
 * Version: 1.6.0
 * Author: HydroMax
 * License: GPL-2.0-or-later
 * Text Domain: hydromax-variation-matrix
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'HydroMax_Variation_Matrix' ) ) {
	/**
	 * Render a variation matrix for variable products.
	 */
	final class HydroMax_Variation_Matrix {
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

			wp_register_style( 'hydromax-variation-matrix', false, array(), '1.6.0' );
			wp_enqueue_style( 'hydromax-variation-matrix' );
			wp_add_inline_style( 'hydromax-variation-matrix', $this->get_styles() );
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
			<div class="hydromax-matrix">
				<h4 class="hydromax-title"><?php esc_html_e( 'Product Variations', 'hydromax-variation-matrix' ); ?></h4>
				<div class="hydromax-table-wrap">
					<table class="hydromax-table" aria-label="<?php esc_attr_e( 'Product variation matrix', 'hydromax-variation-matrix' ); ?>">
						<thead>
							<tr>
								<th scope="col" class="col-sku"><?php esc_html_e( 'SKU', 'hydromax-variation-matrix' ); ?></th>
								<?php foreach ( $attributes as $attr_name ) : ?>
									<th scope="col" class="col-attr"><?php echo esc_html( wc_attribute_label( $attr_name ) ); ?></th>
								<?php endforeach; ?>
								<th scope="col" class="col-price"><?php esc_html_e( 'Price', 'hydromax-variation-matrix' ); ?></th>
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
										$value_slug    = isset( $variation['attributes'][ $attribute_key ] ) ? $variation['attributes'][ $attribute_key ] : '';
										$value         = $this->get_attribute_value( $attr_name, $value_slug );
										?>
										<td class="col-attr"><?php echo esc_html( $value ); ?></td>
									<?php endforeach; ?>
									<td class="col-price"><?php echo wp_kses_post( $variation_obj->get_price_html() ); ?></td>
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
		 * @param string $attr_name  Attribute taxonomy key.
		 * @param string $value_slug Stored value from variation data.
		 *
		 * @return string
		 */
		private function get_attribute_value( $attr_name, $value_slug ) {
			if ( '' === $value_slug ) {
				return '—';
			}

			$taxonomy = wc_attribute_taxonomy_name( $attr_name );

			if ( taxonomy_exists( $taxonomy ) ) {
				$term = get_term_by( 'slug', $value_slug, $taxonomy );

				if ( $term instanceof WP_Term ) {
					return $term->name;
				}
			}

			return wc_clean( $value_slug );
		}

		/**
		 * Plugin styles.
		 *
		 * @return string
		 */
		private function get_styles() {
			return '.hydromax-matrix{margin-top:10px;margin-bottom:30px}.hydromax-title{font-size:14px;font-weight:500;margin-bottom:10px;color:#222}.hydromax-table-wrap{overflow-x:auto}.hydromax-table{width:100%;border-collapse:collapse;font-size:13px;line-height:1.4}.hydromax-table th,.hydromax-table td{border-bottom:1px solid #eee;padding:6px 10px;vertical-align:middle;white-space:nowrap}.hydromax-table .col-sku{text-align:left}.hydromax-table .col-attr{text-align:center}.hydromax-table .col-price{text-align:right}.hydromax-table th{font-weight:600;color:#333;background:#fafafa}.hydromax-table tr:last-child td{border-bottom:none}@media (max-width:768px){.hydromax-table{font-size:12px}.hydromax-table th,.hydromax-table td{padding:5px 6px}}';
		}
	}

	new HydroMax_Variation_Matrix();
}
