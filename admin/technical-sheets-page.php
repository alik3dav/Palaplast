<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_render_technical_sheets_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$sheets  = palaplast_get_technical_sheets();
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
			<thead><tr><th><?php esc_html_e( 'Name', 'palaplast' ); ?></th><th><?php esc_html_e( 'PDF File', 'palaplast' ); ?></th><th><?php esc_html_e( 'Date', 'palaplast' ); ?></th><th><?php esc_html_e( 'Actions', 'palaplast' ); ?></th></tr></thead>
			<tbody>
				<?php if ( empty( $sheets ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No technical sheets found.', 'palaplast' ); ?></td></tr>
				<?php else : foreach ( $sheets as $sheet_id => $sheet_data ) :
					$file_url = ! empty( $sheet_data['attachment_id'] ) ? wp_get_attachment_url( (int) $sheet_data['attachment_id'] ) : '';
					$file_name = ! empty( $sheet_data['attachment_id'] ) ? basename( (string) get_attached_file( (int) $sheet_data['attachment_id'] ) ) : '';
					$edit_url = add_query_arg( array( 'page' => 'palaplast-technical-sheets', 'edit_sheet' => $sheet_id ), admin_url( 'admin.php' ) );
					$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'palaplast_delete_sheet', 'sheet_id' => $sheet_id ), admin_url( 'admin-post.php' ) ), 'palaplast_delete_sheet_' . $sheet_id );
					?>
					<tr>
						<td><?php echo esc_html( isset( $sheet_data['name'] ) ? $sheet_data['name'] : '' ); ?></td>
						<td><?php if ( $file_url ) : ?><a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $file_name ? $file_name : $file_url ); ?></a><?php else : esc_html_e( 'No file', 'palaplast' ); endif; ?></td>
						<td><?php echo ! empty( $sheet_data['created_at'] ) ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sheet_data['created_at'] ) ) ) : ''; ?></td>
						<td><a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'palaplast' ); ?></a> <a class="button button-small" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this technical sheet?', 'palaplast' ) ); ?>');"><?php esc_html_e( 'Delete', 'palaplast' ); ?></a></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

function palaplast_handle_save_sheet() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'palaplast' ) );
	}

	check_admin_referer( 'palaplast_save_sheet' );
	$sheet_id      = isset( $_POST['sheet_id'] ) ? absint( wp_unslash( $_POST['sheet_id'] ) ) : 0;
	$sheet_name    = isset( $_POST['sheet_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sheet_name'] ) ) : '';
	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;

	if ( '' === $sheet_name || ! palaplast_is_valid_pdf_attachment( $attachment_id ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=palaplast-technical-sheets' ) );
		exit;
	}

	$sheets = palaplast_get_technical_sheets();
	if ( $sheet_id && isset( $sheets[ $sheet_id ] ) ) {
		$sheets[ $sheet_id ]['name']          = $sheet_name;
		$sheets[ $sheet_id ]['attachment_id'] = $attachment_id;
	} else {
		$sheet_id            = time() + wp_rand( 1, 999 );
		$sheets[ $sheet_id ] = array( 'name' => $sheet_name, 'attachment_id' => $attachment_id, 'created_at' => current_time( 'mysql' ) );
	}

	update_option( 'palaplast_technical_sheets', $sheets, false );
	wp_safe_redirect( admin_url( 'admin.php?page=palaplast-technical-sheets&sheet_updated=1' ) );
	exit;
}

function palaplast_handle_delete_sheet() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'palaplast' ) );
	}

	$sheet_id = isset( $_GET['sheet_id'] ) ? absint( wp_unslash( $_GET['sheet_id'] ) ) : 0;
	if ( ! $sheet_id ) {
		wp_safe_redirect( admin_url( 'admin.php?page=palaplast-technical-sheets' ) );
		exit;
	}

	check_admin_referer( 'palaplast_delete_sheet_' . $sheet_id );
	$sheets = palaplast_get_technical_sheets();
	if ( isset( $sheets[ $sheet_id ] ) ) {
		unset( $sheets[ $sheet_id ] );
		update_option( 'palaplast_technical_sheets', $sheets, false );
		palaplast_clear_sheet_from_categories( $sheet_id );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=palaplast-technical-sheets&sheet_deleted=1' ) );
	exit;
}
