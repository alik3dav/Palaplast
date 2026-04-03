<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'palaplast_register_technical_sheets_menu' );
add_action( 'admin_menu', 'palaplast_register_pricelists_menu' );
add_action( 'admin_menu', 'palaplast_register_certificates_menu' );
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
add_action( 'woocommerce_product_options_general_product_data', 'palaplast_render_variation_table_custom_rows_field' );
add_action( 'woocommerce_admin_process_product_object', 'palaplast_save_variation_table_custom_rows_field' );

function palaplast_register_technical_sheets_menu() {
	add_submenu_page( 'woocommerce', __( 'Technical Sheets', 'palaplast' ), __( 'Technical Sheets', 'palaplast' ), 'manage_woocommerce', 'palaplast-technical-sheets', 'palaplast_render_technical_sheets_page' );
}

function palaplast_register_pricelists_menu() {
	add_submenu_page( 'woocommerce', __( 'Pricelists', 'palaplast' ), __( 'Pricelists', 'palaplast' ), 'manage_woocommerce', 'palaplast-pricelists', 'palaplast_render_pricelists_page' );
}

function palaplast_register_certificates_menu() {
	add_submenu_page( 'woocommerce', __( 'Certificates', 'palaplast' ), __( 'Certificates', 'palaplast' ), 'manage_woocommerce', 'edit.php?post_type=palaplast_cert' );
	add_submenu_page( 'woocommerce', __( 'Add Certificate', 'palaplast' ), __( 'Add Certificate', 'palaplast' ), 'manage_woocommerce', 'post-new.php?post_type=palaplast_cert' );
}

function palaplast_render_variation_table_custom_rows_field() {
	global $post;

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$rows = get_post_meta( $post->ID, '_palaplast_variation_table_custom_rows', true );
	$rows = is_array( $rows ) ? array_values( $rows ) : array();

	$style_options = array(
		'info'    => __( 'Info', 'palaplast' ),
		'warning' => __( 'Warning', 'palaplast' ),
		'note'    => __( 'Note', 'palaplast' ),
	);
	?>
	<div class="options_group show_if_variable">
		<p class="form-field">
			<label><?php esc_html_e( 'Variation Table Custom Rows', 'palaplast' ); ?></label>
			<span class="description"><?php esc_html_e( 'Insert informational rows into the variations table after specific variation row numbers.', 'palaplast' ); ?></span>
		</p>
		<div id="palaplast-custom-rows-repeater">
			<?php foreach ( $rows as $index => $row ) :
				$position = isset( $row['position'] ) ? (int) $row['position'] : 1;
				$text     = isset( $row['text'] ) ? (string) $row['text'] : '';
				$style    = isset( $row['style'] ) ? (string) $row['style'] : 'info';
				$enabled  = isset( $row['enabled'] ) ? (bool) $row['enabled'] : true;
				?>
				<div class="palaplast-custom-row-item">
					<p class="form-field palaplast-custom-row-position-field">
						<label><?php esc_html_e( 'Position (after row #)', 'palaplast' ); ?></label>
						<input type="number" min="1" step="1" name="palaplast_custom_rows[<?php echo esc_attr( $index ); ?>][position]" value="<?php echo esc_attr( max( 1, $position ) ); ?>" />
					</p>
					<p class="form-field palaplast-custom-row-text-field">
						<label><?php esc_html_e( 'Text', 'palaplast' ); ?></label>
						<textarea name="palaplast_custom_rows[<?php echo esc_attr( $index ); ?>][text]" rows="3"><?php echo esc_textarea( $text ); ?></textarea>
					</p>
					<p class="form-field palaplast-custom-row-style-field">
						<label><?php esc_html_e( 'Style', 'palaplast' ); ?></label>
						<select name="palaplast_custom_rows[<?php echo esc_attr( $index ); ?>][style]">
							<?php foreach ( $style_options as $style_key => $style_label ) : ?>
								<option value="<?php echo esc_attr( $style_key ); ?>" <?php selected( $style, $style_key ); ?>><?php echo esc_html( $style_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
					<p class="form-field palaplast-custom-row-enabled-field">
						<label><?php esc_html_e( 'Enabled', 'palaplast' ); ?></label>
						<label><input type="checkbox" name="palaplast_custom_rows[<?php echo esc_attr( $index ); ?>][enabled]" value="1" <?php checked( $enabled ); ?> /> <?php esc_html_e( 'Show this row', 'palaplast' ); ?></label>
					</p>
					<p class="form-field palaplast-custom-row-actions-field"><button type="button" class="button palaplast-remove-custom-row"><?php esc_html_e( 'Remove', 'palaplast' ); ?></button></p>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="form-field"><button type="button" class="button" id="palaplast-add-custom-row"><?php esc_html_e( 'Add Custom Row', 'palaplast' ); ?></button></p>
	</div>
	<script type="text/html" id="tmpl-palaplast-custom-row-item">
		<div class="palaplast-custom-row-item">
			<p class="form-field palaplast-custom-row-position-field">
				<label><?php esc_html_e( 'Position (after row #)', 'palaplast' ); ?></label>
				<input type="number" min="1" step="1" name="palaplast_custom_rows[{{{data.index}}}][position]" value="1" />
			</p>
			<p class="form-field palaplast-custom-row-text-field">
				<label><?php esc_html_e( 'Text', 'palaplast' ); ?></label>
				<textarea name="palaplast_custom_rows[{{{data.index}}}][text]" rows="3"></textarea>
			</p>
			<p class="form-field palaplast-custom-row-style-field">
				<label><?php esc_html_e( 'Style', 'palaplast' ); ?></label>
				<select name="palaplast_custom_rows[{{{data.index}}}][style]">
					<option value="info"><?php esc_html_e( 'Info', 'palaplast' ); ?></option>
					<option value="warning"><?php esc_html_e( 'Warning', 'palaplast' ); ?></option>
					<option value="note"><?php esc_html_e( 'Note', 'palaplast' ); ?></option>
				</select>
			</p>
			<p class="form-field palaplast-custom-row-enabled-field">
				<label><?php esc_html_e( 'Enabled', 'palaplast' ); ?></label>
				<label><input type="checkbox" name="palaplast_custom_rows[{{{data.index}}}][enabled]" value="1" checked="checked" /> <?php esc_html_e( 'Show this row', 'palaplast' ); ?></label>
			</p>
			<p class="form-field palaplast-custom-row-actions-field"><button type="button" class="button palaplast-remove-custom-row"><?php esc_html_e( 'Remove', 'palaplast' ); ?></button></p>
		</div>
	</script>
	<script>
		jQuery(function($){
			var $container = $('#palaplast-custom-rows-repeater');
			var template = wp.template('palaplast-custom-row-item');
			var index = $container.find('.palaplast-custom-row-item').length;

			$('#palaplast-add-custom-row').on('click', function(){
				$container.append(template({ index: index }));
				index++;
			});

			$container.on('click', '.palaplast-remove-custom-row', function(){
				$(this).closest('.palaplast-custom-row-item').remove();
			});
		});
	</script>
	<style>
		#palaplast-custom-rows-repeater .palaplast-custom-row-item{border:1px solid #dcdcde;padding:10px;margin:0 0 10px;background:#fff}
		#palaplast-custom-rows-repeater .form-field{margin:0 0 8px;padding:0}
		#palaplast-custom-rows-repeater .form-field:last-child{margin-bottom:0}
		#palaplast-custom-rows-repeater label{display:block;margin-bottom:4px}
		#palaplast-custom-rows-repeater textarea,#palaplast-custom-rows-repeater input[type="number"],#palaplast-custom-rows-repeater select{width:100%;max-width:420px}
	</style>
	<?php
}

function palaplast_save_variation_table_custom_rows_field( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$custom_rows = isset( $_POST['palaplast_custom_rows'] ) && is_array( $_POST['palaplast_custom_rows'] ) ? wp_unslash( $_POST['palaplast_custom_rows'] ) : array();
	$clean_rows  = array();

	foreach ( $custom_rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$text = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';
		if ( '' === $text ) {
			continue;
		}

		$position = isset( $row['position'] ) ? (int) $row['position'] : 1;
		$position = max( 1, $position );

		$style = isset( $row['style'] ) ? sanitize_key( (string) $row['style'] ) : 'info';
		if ( ! in_array( $style, array( 'info', 'warning', 'note' ), true ) ) {
			$style = 'info';
		}

		$clean_rows[] = array(
			'position' => $position,
			'text'     => $text,
			'style'    => $style,
			'enabled'  => ! empty( $row['enabled'] ),
		);
	}

	if ( empty( $clean_rows ) ) {
		$product->delete_meta_data( '_palaplast_variation_table_custom_rows' );
		return;
	}

	$product->update_meta_data( '_palaplast_variation_table_custom_rows', array_values( $clean_rows ) );
}
