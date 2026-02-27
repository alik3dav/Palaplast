<?php
/**
 * Plugin Name: Palaplast
 * Description: Displays a clean, compact variation matrix (SKU + attributes + price) above the product tabs for variable WooCommerce products.
 * Version: 1.7.0
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
			add_action( 'woocommerce_single_product_summary', array( $this, 'render_technical_sheet_button' ), 35 );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_filter( 'woocommerce_hide_invisible_variations', array( $this, 'show_variations_without_price' ), 10, 3 );
			add_filter( 'woocommerce_get_price_html', array( $this, 'hide_catalog_prices' ), 10, 2 );
			add_filter( 'woocommerce_available_variation', array( $this, 'remove_variation_price_payload' ), 10, 3 );
			add_filter( 'woocommerce_show_variation_price', '__return_false' );
			add_filter( 'woocommerce_variation_option_name', array( $this, 'preserve_variation_option_name' ), 10, 4 );

			if ( is_admin() ) {
				add_action( 'admin_menu', array( $this, 'register_technical_sheets_menu' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
				add_action( 'admin_post_palaplast_save_sheet', array( $this, 'handle_save_sheet' ) );
				add_action( 'admin_post_palaplast_delete_sheet', array( $this, 'handle_delete_sheet' ) );
				add_action( 'product_cat_add_form_fields', array( $this, 'render_category_sheet_add_field' ) );
				add_action( 'product_cat_edit_form_fields', array( $this, 'render_category_sheet_edit_field' ) );
				add_action( 'created_product_cat', array( $this, 'save_category_sheet' ) );
				add_action( 'edited_product_cat', array( $this, 'save_category_sheet' ) );
			}
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

			wp_register_style( 'palaplast', false, array(), '1.7.0' );
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
		 * Render "Download Technical Sheet" button on single product pages.
		 *
		 * @return void
		 */
		public function render_technical_sheet_button() {
			if ( ! function_exists( 'is_product' ) || ! is_product() ) {
				return;
			}

			global $product;

			if ( ! $product instanceof WC_Product ) {
				return;
			}

			$sheet = $this->get_product_technical_sheet( $product->get_id() );

			if ( empty( $sheet['file_url'] ) ) {
				return;
			}

			?>
			<p class="palaplast-technical-sheet">
				<a class="button palaplast-technical-sheet-button" href="<?php echo esc_url( $sheet['file_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Download Technical Sheet', 'palaplast' ); ?>
				</a>
			</p>
			<?php
		}

		/**
		 * Register the Technical Sheets admin menu.
		 *
		 * @return void
		 */
		public function register_technical_sheets_menu() {
			add_submenu_page(
				'woocommerce',
				__( 'Technical Sheets', 'palaplast' ),
				__( 'Technical Sheets', 'palaplast' ),
				'manage_woocommerce',
				'palaplast-technical-sheets',
				array( $this, 'render_technical_sheets_page' )
			);
		}

		/**
		 * Enqueue admin assets for the Technical Sheets page.
		 *
		 * @param string $hook_suffix Current admin page.
		 *
		 * @return void
		 */
		public function enqueue_admin_assets( $hook_suffix ) {
			if ( 'woocommerce_page_palaplast-technical-sheets' !== $hook_suffix ) {
				return;
			}

			wp_enqueue_media();
			wp_add_inline_script(
				'jquery-core',
				"jQuery(function($){var frame;$('.palaplast-select-pdf').on('click',function(e){e.preventDefault();if(frame){frame.open();return;}frame=wp.media({title:'" . esc_js( __( 'Select Technical Sheet PDF', 'palaplast' ) ) . "',button:{text:'" . esc_js( __( 'Use PDF', 'palaplast' ) ) . "'},library:{type:'application/pdf'},multiple:false});frame.on('select',function(){var attachment=frame.state().get('selection').first().toJSON();$('#palaplast_attachment_id').val(attachment.id);$('.palaplast-selected-file').text(attachment.filename || attachment.url);});frame.open();});$('.palaplast-remove-pdf').on('click',function(e){e.preventDefault();$('#palaplast_attachment_id').val('');$('.palaplast-selected-file').text('" . esc_js( __( 'No file selected.', 'palaplast' ) ) . "');});});"
			);
		}

		/**
		 * Render Technical Sheets admin page.
		 *
		 * @return void
		 */
		public function render_technical_sheets_page() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			$sheets  = $this->get_technical_sheets();
			$edit_id = isset( $_GET['edit_sheet'] ) ? absint( wp_unslash( $_GET['edit_sheet'] ) ) : 0;
			$sheet   = ( $edit_id && isset( $sheets[ $edit_id ] ) ) ? $sheets[ $edit_id ] : array();
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Technical Sheets', 'palaplast' ); ?></h1>

				<?php if ( isset( $_GET['sheet_updated'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Technical Sheet saved.', 'palaplast' ); ?></p></div>
				<?php endif; ?>

				<?php if ( isset( $_GET['sheet_deleted'] ) ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Technical Sheet deleted.', 'palaplast' ); ?></p></div>
				<?php endif; ?>

				<div class="card" style="max-width:780px;padding:20px;margin-top:20px;">
					<h2 style="margin-top:0;"><?php echo $edit_id ? esc_html__( 'Edit Technical Sheet', 'palaplast' ) : esc_html__( 'Add Technical Sheet', 'palaplast' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'palaplast_save_sheet' ); ?>
						<input type="hidden" name="action" value="palaplast_save_sheet" />
						<input type="hidden" name="sheet_id" value="<?php echo esc_attr( $edit_id ); ?>" />

						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label for="palaplast_sheet_name"><?php esc_html_e( 'Name', 'palaplast' ); ?></label></th>
								<td><input type="text" class="regular-text" id="palaplast_sheet_name" name="sheet_name" required value="<?php echo isset( $sheet['name'] ) ? esc_attr( $sheet['name'] ) : ''; ?>" /></td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'PDF File', 'palaplast' ); ?></th>
								<td>
									<?php $attachment_id = isset( $sheet['attachment_id'] ) ? (int) $sheet['attachment_id'] : 0; ?>
									<input type="hidden" id="palaplast_attachment_id" name="attachment_id" value="<?php echo esc_attr( $attachment_id ); ?>" />
									<button class="button palaplast-select-pdf"><?php esc_html_e( 'Select PDF', 'palaplast' ); ?></button>
									<button class="button palaplast-remove-pdf"><?php esc_html_e( 'Remove', 'palaplast' ); ?></button>
									<p class="description palaplast-selected-file">
										<?php
										if ( $attachment_id ) {
											echo esc_html( basename( (string) get_attached_file( $attachment_id ) ) );
										} else {
											esc_html_e( 'No file selected.', 'palaplast' );
										}
										?>
									</p>
								</td>
							</tr>
						</table>

						<?php submit_button( $edit_id ? __( 'Update Sheet', 'palaplast' ) : __( 'Add Sheet', 'palaplast' ) ); ?>
					</form>
				</div>

				<h2 style="margin-top:30px;"><?php esc_html_e( 'All Technical Sheets', 'palaplast' ); ?></h2>
				<table class="widefat striped" style="max-width:980px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'palaplast' ); ?></th>
							<th><?php esc_html_e( 'PDF File', 'palaplast' ); ?></th>
							<th><?php esc_html_e( 'Date', 'palaplast' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'palaplast' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $sheets ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'No technical sheets found.', 'palaplast' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $sheets as $sheet_id => $sheet_data ) : ?>
								<?php
								$file_url  = ! empty( $sheet_data['attachment_id'] ) ? wp_get_attachment_url( (int) $sheet_data['attachment_id'] ) : '';
								$file_name = ! empty( $sheet_data['attachment_id'] ) ? basename( (string) get_attached_file( (int) $sheet_data['attachment_id'] ) ) : '';
								$edit_url  = add_query_arg(
									array(
										'page'       => 'palaplast-technical-sheets',
										'edit_sheet' => $sheet_id,
									),
									admin_url( 'admin.php' )
								);
								$delete_url = wp_nonce_url(
									add_query_arg(
										array(
											'action'   => 'palaplast_delete_sheet',
											'sheet_id' => $sheet_id,
										),
										admin_url( 'admin-post.php' )
									),
									'palaplast_delete_sheet_' . $sheet_id
								);
								?>
								<tr>
									<td><?php echo esc_html( isset( $sheet_data['name'] ) ? $sheet_data['name'] : '' ); ?></td>
									<td>
										<?php if ( $file_url ) : ?>
											<a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $file_name ? $file_name : $file_url ); ?></a>
										<?php else : ?>
											<?php esc_html_e( 'No file', 'palaplast' ); ?>
										<?php endif; ?>
									</td>
									<td><?php echo ! empty( $sheet_data['created_at'] ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sheet_data['created_at'] ) ) ) : ''; ?></td>
									<td>
										<a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'palaplast' ); ?></a>
										<a class="button button-small" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this technical sheet?', 'palaplast' ) ); ?>');"><?php esc_html_e( 'Delete', 'palaplast' ); ?></a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Save a technical sheet.
		 *
		 * @return void
		 */
		public function handle_save_sheet() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'palaplast' ) );
			}

			check_admin_referer( 'palaplast_save_sheet' );

			$sheet_id      = isset( $_POST['sheet_id'] ) ? absint( wp_unslash( $_POST['sheet_id'] ) ) : 0;
			$sheet_name    = isset( $_POST['sheet_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sheet_name'] ) ) : '';
			$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;

			if ( '' === $sheet_name || ! $this->is_valid_pdf_attachment( $attachment_id ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=palaplast-technical-sheets' ) );
				exit;
			}

			$sheets = $this->get_technical_sheets();

			if ( $sheet_id && isset( $sheets[ $sheet_id ] ) ) {
				$sheets[ $sheet_id ]['name']          = $sheet_name;
				$sheets[ $sheet_id ]['attachment_id'] = $attachment_id;
			} else {
				$sheet_id             = time() + wp_rand( 1, 999 );
				$sheets[ $sheet_id ]  = array(
					'name'          => $sheet_name,
					'attachment_id' => $attachment_id,
					'created_at'    => current_time( 'mysql' ),
				);
			}

			update_option( 'palaplast_technical_sheets', $sheets, false );

			wp_safe_redirect( admin_url( 'admin.php?page=palaplast-technical-sheets&sheet_updated=1' ) );
			exit;
		}

		/**
		 * Delete a technical sheet and clear category references.
		 *
		 * @return void
		 */
		public function handle_delete_sheet() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'palaplast' ) );
			}

			$sheet_id = isset( $_GET['sheet_id'] ) ? absint( wp_unslash( $_GET['sheet_id'] ) ) : 0;

			if ( ! $sheet_id ) {
				wp_safe_redirect( admin_url( 'admin.php?page=palaplast-technical-sheets' ) );
				exit;
			}

			check_admin_referer( 'palaplast_delete_sheet_' . $sheet_id );

			$sheets = $this->get_technical_sheets();

			if ( isset( $sheets[ $sheet_id ] ) ) {
				unset( $sheets[ $sheet_id ] );
				update_option( 'palaplast_technical_sheets', $sheets, false );
				$this->clear_sheet_from_categories( $sheet_id );
			}

			wp_safe_redirect( admin_url( 'admin.php?page=palaplast-technical-sheets&sheet_deleted=1' ) );
			exit;
		}

		/**
		 * Render the technical sheet selector on category create form.
		 *
		 * @return void
		 */
		public function render_category_sheet_add_field() {
			?>
			<div class="form-field term-palaplast-sheet-wrap">
				<label for="palaplast_technical_sheet_id"><?php esc_html_e( 'Technical Sheet', 'palaplast' ); ?></label>
				<?php $this->render_category_sheet_dropdown( 0 ); ?>
				<p><?php esc_html_e( 'Select a technical sheet PDF for this category.', 'palaplast' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Render the technical sheet selector on category edit form.
		 *
		 * @param WP_Term $term Category term.
		 *
		 * @return void
		 */
		public function render_category_sheet_edit_field( $term ) {
			$sheet_id         = (int) get_term_meta( $term->term_id, 'palaplast_technical_sheet_id', true );
			$inherited_sheet  = $this->get_category_inherited_sheet( (int) $term->term_id );
			$inherited_name   = '';

			if ( ! empty( $inherited_sheet['name'] ) ) {
				$inherited_name = (string) $inherited_sheet['name'];
			}
			?>
			<tr class="form-field term-palaplast-sheet-wrap">
				<th scope="row"><label for="palaplast_technical_sheet_id"><?php esc_html_e( 'Technical Sheet', 'palaplast' ); ?></label></th>
				<td>
					<?php $this->render_category_sheet_dropdown( $sheet_id ); ?>
					<p class="description"><?php esc_html_e( 'Select a technical sheet PDF for this category.', 'palaplast' ); ?></p>
					<p class="description"><strong><?php esc_html_e( 'Selected Technical Sheet:', 'palaplast' ); ?></strong>
						<?php echo $sheet_id ? esc_html( $this->get_sheet_name_by_id( $sheet_id ) ) : esc_html__( 'None', 'palaplast' ); ?>
					</p>
					<p class="description"><strong><?php esc_html_e( 'Inherited Technical Sheet:', 'palaplast' ); ?></strong>
						<?php echo $inherited_name ? esc_html( $inherited_name ) : esc_html__( 'None', 'palaplast' ); ?>
					</p>
				</td>
			</tr>
			<?php
		}

		/**
		 * Save selected technical sheet in category term meta.
		 *
		 * @param int $term_id Category ID.
		 *
		 * @return void
		 */
		public function save_category_sheet( $term_id ) {
			if ( ! isset( $_POST['palaplast_technical_sheet_id'] ) ) {
				return;
			}

			$sheet_id = absint( wp_unslash( $_POST['palaplast_technical_sheet_id'] ) );
			$sheets   = $this->get_technical_sheets();

			if ( $sheet_id && ! isset( $sheets[ $sheet_id ] ) ) {
				$sheet_id = 0;
			}

			if ( $sheet_id ) {
				update_term_meta( $term_id, 'palaplast_technical_sheet_id', $sheet_id );
			} else {
				delete_term_meta( $term_id, 'palaplast_technical_sheet_id' );
			}
		}

		/**
		 * Render technical sheet dropdown helper.
		 *
		 * @param int $selected_id Selected sheet ID.
		 *
		 * @return void
		 */
		private function render_category_sheet_dropdown( $selected_id ) {
			$sheets = $this->get_technical_sheets();
			?>
			<select name="palaplast_technical_sheet_id" id="palaplast_technical_sheet_id">
				<option value="0"><?php esc_html_e( '— None —', 'palaplast' ); ?></option>
				<?php foreach ( $sheets as $sheet_id => $sheet ) : ?>
					<option value="<?php echo esc_attr( $sheet_id ); ?>" <?php selected( (int) $selected_id, (int) $sheet_id ); ?>>
						<?php echo esc_html( isset( $sheet['name'] ) ? $sheet['name'] : '' ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		/**
		 * Get technical sheet for a product based on assigned categories.
		 *
		 * @param int $product_id Product ID.
		 *
		 * @return array{name:string,file_url:string}
		 */
		private function get_product_technical_sheet( $product_id ) {
			$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return array();
			}

			sort( $terms, SORT_NUMERIC );

			$candidates = array();

			foreach ( $terms as $index => $term_id ) {
				$resolved_sheet = $this->resolve_category_sheet( (int) $term_id );

				if ( empty( $resolved_sheet['file_url'] ) ) {
					continue;
				}

				$candidates[] = array(
					'distance' => isset( $resolved_sheet['distance'] ) ? (int) $resolved_sheet['distance'] : PHP_INT_MAX,
					'order'    => (int) $index,
					'term_id'  => (int) $term_id,
					'sheet'    => array(
						'name'     => (string) $resolved_sheet['name'],
						'file_url' => (string) $resolved_sheet['file_url'],
					),
				);
			}

			if ( empty( $candidates ) ) {
				return array();
			}

			usort(
				$candidates,
				static function ( $a, $b ) {
					if ( $a['distance'] !== $b['distance'] ) {
						return $a['distance'] <=> $b['distance'];
					}

					if ( $a['order'] !== $b['order'] ) {
						return $a['order'] <=> $b['order'];
					}

					return $a['term_id'] <=> $b['term_id'];
				}
			);

			return $candidates[0]['sheet'];
		}

		/**
		 * Resolve category sheet using direct selection or nearest parent inheritance.
		 *
		 * @param int $term_id Category ID.
		 *
		 * @return array{name:string,file_url:string,distance:int,sheet_id:int}
		 */
		private function resolve_category_sheet( $term_id ) {
			$term = get_term( $term_id, 'product_cat' );

			if ( ! $term instanceof WP_Term ) {
				return array();
			}

			$sheets        = $this->get_technical_sheets();
			$current_term  = $term;
			$distance      = 0;

			while ( $current_term instanceof WP_Term && 'product_cat' === $current_term->taxonomy ) {
				$sheet_id = (int) get_term_meta( (int) $current_term->term_id, 'palaplast_technical_sheet_id', true );

				if ( $sheet_id && isset( $sheets[ $sheet_id ] ) ) {
					$file_url = ! empty( $sheets[ $sheet_id ]['attachment_id'] ) ? wp_get_attachment_url( (int) $sheets[ $sheet_id ]['attachment_id'] ) : '';

					if ( $file_url ) {
						return array(
							'name'     => (string) $sheets[ $sheet_id ]['name'],
							'file_url' => (string) $file_url,
							'distance' => $distance,
							'sheet_id' => $sheet_id,
						);
					}
				}

				if ( empty( $current_term->parent ) ) {
					break;
				}

				$current_term = get_term( (int) $current_term->parent, 'product_cat' );
				++$distance;
			}

			return array();
		}

		/**
		 * Get inherited sheet only (ignoring term's direct selection).
		 *
		 * @param int $term_id Category ID.
		 *
		 * @return array{name:string,file_url:string,distance:int,sheet_id:int}
		 */
		private function get_category_inherited_sheet( $term_id ) {
			$term = get_term( $term_id, 'product_cat' );

			if ( ! $term instanceof WP_Term || empty( $term->parent ) ) {
				return array();
			}

			return $this->resolve_category_sheet( (int) $term->parent );
		}

		/**
		 * Get sheet name helper.
		 *
		 * @param int $sheet_id Technical sheet ID.
		 *
		 * @return string
		 */
		private function get_sheet_name_by_id( $sheet_id ) {
			$sheets = $this->get_technical_sheets();

			if ( empty( $sheets[ $sheet_id ]['name'] ) ) {
				return '';
			}

			return (string) $sheets[ $sheet_id ]['name'];
		}

		/**
		 * Get all stored technical sheets.
		 *
		 * @return array<int,array<string,mixed>>
		 */
		private function get_technical_sheets() {
			$sheets = get_option( 'palaplast_technical_sheets', array() );

			return is_array( $sheets ) ? $sheets : array();
		}

		/**
		 * Check whether an attachment ID points to a valid PDF.
		 *
		 * @param int $attachment_id Attachment ID.
		 *
		 * @return bool
		 */
		private function is_valid_pdf_attachment( $attachment_id ) {
			if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
				return false;
			}

			$file_path = get_attached_file( $attachment_id );
			$file_type = $file_path ? wp_check_filetype( $file_path ) : array();

			return isset( $file_type['ext'] ) && 'pdf' === strtolower( (string) $file_type['ext'] );
		}

		/**
		 * Remove sheet selections from all categories when a sheet is deleted.
		 *
		 * @param int $sheet_id Technical sheet ID.
		 *
		 * @return void
		 */
		private function clear_sheet_from_categories( $sheet_id ) {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return;
			}

			foreach ( $terms as $term_id ) {
				$current_sheet = (int) get_term_meta( (int) $term_id, 'palaplast_technical_sheet_id', true );

				if ( $current_sheet === (int) $sheet_id ) {
					delete_term_meta( (int) $term_id, 'palaplast_technical_sheet_id' );
				}
			}
		}

		/**
		 * Plugin styles.
		 *
		 * @return string
		 */
		private function get_styles() {
			return '.palaplast-matrix{margin-top:10px;margin-bottom:30px}.palaplast-title{font-size:14px;font-weight:500;margin-bottom:10px;color:#222}.palaplast-table-wrap{overflow-x:auto}.palaplast-table{width:100%;border-collapse:collapse;font-size:13px;line-height:1.4}.palaplast-table th,.palaplast-table td{border-bottom:1px solid #eee;padding:6px 10px;vertical-align:middle;white-space:nowrap}.palaplast-table .col-sku{text-align:left}.palaplast-table .col-attr{text-align:center}.palaplast-table th{font-weight:600;color:#333;background:#fafafa}.palaplast-table tr:last-child td{border-bottom:none}.palaplast-technical-sheet{margin:16px 0 0}.palaplast-technical-sheet-button.button{display:inline-flex;align-items:center;gap:6px}@media (max-width:768px){.palaplast-table{font-size:12px}.palaplast-table th,.palaplast-table td{padding:5px 6px}}';
		}
	}

	new Palaplast_Variation_Matrix();
}
