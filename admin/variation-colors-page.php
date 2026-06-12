<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_render_variation_colors_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$colors = palaplast_get_variation_colors();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Variation Colors', 'palaplast' ); ?></h1>

		<div class="notice notice-info palaplast-admin-notice">
			<p><?php esc_html_e( 'Create the color list used by the Palaplast color picker inside each product variation.', 'palaplast' ); ?></p>
		</div>

		<?php if ( isset( $_GET['colors_updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Variation colors saved.', 'palaplast' ); ?></p></div>
		<?php endif; ?>

		<div class="card palaplast-admin-card palaplast-colors-card">
			<h2 class="palaplast-admin-card-title"><?php esc_html_e( 'Color List', 'palaplast' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'palaplast_save_variation_colors' ); ?>
				<input type="hidden" name="action" value="palaplast_save_variation_colors" />

				<table class="widefat striped palaplast-colors-table" id="palaplast-colors-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'palaplast' ); ?></th>
							<th><?php esc_html_e( 'Color', 'palaplast' ); ?></th>
							<th><?php esc_html_e( 'Preview', 'palaplast' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'palaplast' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $colors ) ) : ?>
							<?php palaplast_render_variation_color_row( 0, '', '#000000' ); ?>
						<?php else : ?>
							<?php foreach ( array_values( $colors ) as $index => $color ) : ?>
								<?php palaplast_render_variation_color_row( $index, isset( $color['name'] ) ? (string) $color['name'] : '', isset( $color['hex'] ) ? (string) $color['hex'] : '#000000' ); ?>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>

				<p>
					<button type="button" class="button" id="palaplast-add-color-row"><?php esc_html_e( 'Add Color', 'palaplast' ); ?></button>
				</p>

				<?php submit_button( __( 'Save Colors', 'palaplast' ) ); ?>
			</form>
		</div>
	</div>

	<script type="text/html" id="tmpl-palaplast-color-row">
		<?php palaplast_render_variation_color_row( '{{{data.index}}}', '', '#000000' ); ?>
	</script>
	<script>
		jQuery(function($){
			var $table = $('#palaplast-colors-table');
			var template = wp.template('palaplast-color-row');
			var index = $table.find('tbody tr').length;

			$('#palaplast-add-color-row').on('click', function(){
				$table.find('tbody').append(template({ index: index }));
				index++;
			});

			$table.on('click', '.palaplast-remove-color-row', function(){
				$(this).closest('tr').remove();
			});

			$table.on('input change', '.palaplast-color-input', function(){
				$(this).closest('tr').find('.palaplast-color-preview').css('background-color', $(this).val());
			});
		});
	</script>
	<?php
}

function palaplast_render_variation_color_row( $index, $name, $hex ) {
	$hex = sanitize_hex_color( $hex );
	if ( ! $hex ) {
		$hex = '#000000';
	}
	?>
	<tr>
		<td><input type="text" class="regular-text" name="palaplast_variation_colors[<?php echo esc_attr( $index ); ?>][name]" value="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr__( 'Black', 'palaplast' ); ?>" /></td>
		<td><input type="color" class="palaplast-color-input" name="palaplast_variation_colors[<?php echo esc_attr( $index ); ?>][hex]" value="<?php echo esc_attr( $hex ); ?>" /></td>
		<td><span class="palaplast-color-preview" style="background-color: <?php echo esc_attr( $hex ); ?>;"></span></td>
		<td><button type="button" class="button button-small palaplast-remove-color-row"><?php esc_html_e( 'Remove', 'palaplast' ); ?></button></td>
	</tr>
	<?php
}

function palaplast_handle_save_variation_colors() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'palaplast' ) );
	}

	check_admin_referer( 'palaplast_save_variation_colors' );

	$posted_colors = isset( $_POST['palaplast_variation_colors'] ) && is_array( $_POST['palaplast_variation_colors'] ) ? wp_unslash( $_POST['palaplast_variation_colors'] ) : array();
	$colors        = array();

	foreach ( $posted_colors as $posted_color ) {
		if ( ! is_array( $posted_color ) ) {
			continue;
		}

		$name = isset( $posted_color['name'] ) ? sanitize_text_field( (string) $posted_color['name'] ) : '';
		$hex  = isset( $posted_color['hex'] ) ? sanitize_hex_color( (string) $posted_color['hex'] ) : '';

		if ( '' === $name || ! $hex ) {
			continue;
		}

		$colors[] = array(
			'name' => $name,
			'hex'  => strtolower( $hex ),
		);
	}

	update_option( 'palaplast_variation_colors', $colors, false );
	wp_safe_redirect( admin_url( 'admin.php?page=palaplast-variation-colors&colors_updated=1' ) );
	exit;
}
