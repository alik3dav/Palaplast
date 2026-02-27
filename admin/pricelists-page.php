<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_render_pricelists_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$pricelists = palaplast_get_pricelists();
	$edit_id    = isset( $_GET['edit_pricelist'] ) ? absint( wp_unslash( $_GET['edit_pricelist'] ) ) : 0;
	$pricelist  = ( $edit_id && isset( $pricelists[ $edit_id ] ) ) ? $pricelists[ $edit_id ] : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Pricelists', 'palaplast' ); ?></h1>
		<?php if ( isset( $_GET['pricelist_updated'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pricelist saved.', 'palaplast' ); ?></p></div><?php endif; ?>
		<?php if ( isset( $_GET['pricelist_deleted'] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pricelist deleted.', 'palaplast' ); ?></p></div><?php endif; ?>

		<div class="card" style="max-width:780px;padding:20px;margin-top:20px;">
			<h2 style="margin-top:0;"><?php echo $edit_id ? esc_html__( 'Edit Pricelist', 'palaplast' ) : esc_html__( 'Add Pricelist', 'palaplast' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'palaplast_save_pricelist' ); ?>
				<input type="hidden" name="action" value="palaplast_save_pricelist" />
				<input type="hidden" name="pricelist_id" value="<?php echo esc_attr( $edit_id ); ?>" />
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="palaplast_sheet_name"><?php esc_html_e( 'Name', 'palaplast' ); ?></label></th><td><input type="text" class="regular-text" id="palaplast_sheet_name" name="sheet_name" required value="<?php echo esc_attr( isset( $pricelist['name'] ) ? $pricelist['name'] : '' ); ?>" /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'PDF file', 'palaplast' ); ?></th><td><?php $current_attachment = isset( $pricelist['attachment_id'] ) ? (int) $pricelist['attachment_id'] : 0; ?><input type="hidden" id="palaplast_attachment_id" name="attachment_id" value="<?php echo esc_attr( $current_attachment ); ?>" /><button type="button" class="button palaplast-select-pdf"><?php esc_html_e( 'Select PDF', 'palaplast' ); ?></button> <button type="button" class="button palaplast-remove-pdf"><?php esc_html_e( 'Remove', 'palaplast' ); ?></button><p class="description palaplast-selected-file" style="margin-top:8px;"><?php echo $current_attachment ? esc_html( basename( (string) get_attached_file( $current_attachment ) ) ) : esc_html__( 'No file selected.', 'palaplast' ); ?></p></td></tr>
				</table>
				<?php submit_button( $edit_id ? __( 'Update Pricelist', 'palaplast' ) : __( 'Add Pricelist', 'palaplast' ) ); ?>
			</form>
		</div>

		<h2 style="margin-top:30px;"><?php esc_html_e( 'All Pricelists', 'palaplast' ); ?></h2>
		<table class="widefat striped" style="max-width:980px;">
			<thead><tr><th><?php esc_html_e( 'Name', 'palaplast' ); ?></th><th><?php esc_html_e( 'PDF File', 'palaplast' ); ?></th><th><?php esc_html_e( 'Date', 'palaplast' ); ?></th><th><?php esc_html_e( 'Actions', 'palaplast' ); ?></th></tr></thead>
			<tbody><?php if ( empty( $pricelists ) ) : ?><tr><td colspan="4"><?php esc_html_e( 'No pricelists found.', 'palaplast' ); ?></td></tr><?php else : foreach ( $pricelists as $pricelist_id => $pricelist_data ) : $file_url = ! empty( $pricelist_data['attachment_id'] ) ? wp_get_attachment_url( (int) $pricelist_data['attachment_id'] ) : ''; $file_name = ! empty( $pricelist_data['attachment_id'] ) ? basename( (string) get_attached_file( (int) $pricelist_data['attachment_id'] ) ) : ''; $edit_url = add_query_arg( array( 'page' => 'palaplast-pricelists', 'edit_pricelist' => $pricelist_id ), admin_url( 'admin.php' ) ); $delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'palaplast_delete_pricelist', 'pricelist_id' => $pricelist_id ), admin_url( 'admin-post.php' ) ), 'palaplast_delete_pricelist_' . $pricelist_id ); ?>
				<tr><td><?php echo esc_html( isset( $pricelist_data['name'] ) ? $pricelist_data['name'] : '' ); ?></td><td><?php if ( $file_url ) : ?><a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $file_name ? $file_name : $file_url ); ?></a><?php else : esc_html_e( 'No file', 'palaplast' ); endif; ?></td><td><?php echo ! empty( $pricelist_data['created_at'] ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $pricelist_data['created_at'] ) ) ) : ''; ?></td><td><a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'palaplast' ); ?></a> <a class="button button-small" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this pricelist?', 'palaplast' ) ); ?>');"><?php esc_html_e( 'Delete', 'palaplast' ); ?></a></td></tr>
			<?php endforeach; endif; ?></tbody>
		</table>
	</div>
	<?php
}

function palaplast_handle_save_pricelist() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'palaplast' ) );
	}

	check_admin_referer( 'palaplast_save_pricelist' );
	$pricelist_id   = isset( $_POST['pricelist_id'] ) ? absint( wp_unslash( $_POST['pricelist_id'] ) ) : 0;
	$pricelist_name = isset( $_POST['sheet_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sheet_name'] ) ) : '';
	$attachment_id  = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;

	if ( '' === $pricelist_name || ! palaplast_is_valid_pdf_attachment( $attachment_id ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=palaplast-pricelists' ) );
		exit;
	}

	$pricelists = palaplast_get_pricelists();
	if ( $pricelist_id && isset( $pricelists[ $pricelist_id ] ) ) {
		$pricelists[ $pricelist_id ]['name']          = $pricelist_name;
		$pricelists[ $pricelist_id ]['attachment_id'] = $attachment_id;
	} else {
		$pricelist_id                = time() + wp_rand( 1, 999 );
		$pricelists[ $pricelist_id ] = array( 'name' => $pricelist_name, 'attachment_id' => $attachment_id, 'created_at' => current_time( 'mysql' ) );
	}

	update_option( 'palaplast_pricelists', $pricelists, false );
	wp_safe_redirect( admin_url( 'admin.php?page=palaplast-pricelists&pricelist_updated=1' ) );
	exit;
}

function palaplast_handle_delete_pricelist() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'palaplast' ) );
	}

	$pricelist_id = isset( $_GET['pricelist_id'] ) ? absint( wp_unslash( $_GET['pricelist_id'] ) ) : 0;
	if ( ! $pricelist_id ) {
		wp_safe_redirect( admin_url( 'admin.php?page=palaplast-pricelists' ) );
		exit;
	}

	check_admin_referer( 'palaplast_delete_pricelist_' . $pricelist_id );
	$pricelists = palaplast_get_pricelists();
	if ( isset( $pricelists[ $pricelist_id ] ) ) {
		unset( $pricelists[ $pricelist_id ] );
		update_option( 'palaplast_pricelists', $pricelists, false );
		palaplast_clear_pricelist_from_categories( $pricelist_id );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=palaplast-pricelists&pricelist_deleted=1' ) );
	exit;
}
