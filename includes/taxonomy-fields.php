<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_render_category_sheet_add_field() {
	?>
	<div class="form-field term-palaplast-sheet-wrap">
		<label for="palaplast_technical_sheet_id"><?php esc_html_e( 'Technical Sheet', 'palaplast' ); ?></label>
		<?php palaplast_render_category_sheet_dropdown( 0 ); ?>
		<p><?php esc_html_e( 'Select a technical sheet PDF for this category.', 'palaplast' ); ?></p>
	</div>
	<?php
}

function palaplast_render_category_sheet_edit_field( $term ) {
	$sheet_id        = (int) get_term_meta( $term->term_id, 'palaplast_technical_sheet_id', true );
	$inherited_sheet = palaplast_get_category_inherited_sheet( (int) $term->term_id );
	$inherited_name  = ! empty( $inherited_sheet['name'] ) ? (string) $inherited_sheet['name'] : '';
	?>
	<tr class="form-field term-palaplast-sheet-wrap">
		<th scope="row"><label for="palaplast_technical_sheet_id"><?php esc_html_e( 'Technical Sheet', 'palaplast' ); ?></label></th>
		<td>
			<?php palaplast_render_category_sheet_dropdown( $sheet_id ); ?>
			<p class="description"><?php esc_html_e( 'Select a technical sheet PDF for this category.', 'palaplast' ); ?></p>
			<p class="description"><strong><?php esc_html_e( 'Selected Technical Sheet:', 'palaplast' ); ?></strong>
				<?php echo $sheet_id ? esc_html( palaplast_get_sheet_name_by_id( $sheet_id ) ) : esc_html__( 'None', 'palaplast' ); ?>
			</p>
			<p class="description"><strong><?php esc_html_e( 'Inherited Technical Sheet:', 'palaplast' ); ?></strong>
				<?php echo $inherited_name ? esc_html( $inherited_name ) : esc_html__( 'None', 'palaplast' ); ?>
			</p>
		</td>
	</tr>
	<?php
}

function palaplast_render_category_pricelist_add_field() {
	?>
	<div class="form-field term-palaplast-pricelist-wrap">
		<label for="palaplast_pricelist_id"><?php esc_html_e( 'Pricelist PDF', 'palaplast' ); ?></label>
		<?php palaplast_render_category_pricelist_dropdown( 0 ); ?>
		<p><?php esc_html_e( 'Select a pricelist PDF for this category.', 'palaplast' ); ?></p>
	</div>
	<?php
}

function palaplast_render_category_pricelist_edit_field( $term ) {
	$pricelist_id       = (int) get_term_meta( $term->term_id, 'palaplast_pricelist_id', true );
	$inherited          = palaplast_get_category_inherited_pricelist( (int) $term->term_id );
	$inherited_name     = ! empty( $inherited['name'] ) ? (string) $inherited['name'] : '';
	?>
	<tr class="form-field term-palaplast-pricelist-wrap">
		<th scope="row"><label for="palaplast_pricelist_id"><?php esc_html_e( 'Pricelist PDF', 'palaplast' ); ?></label></th>
		<td>
			<?php palaplast_render_category_pricelist_dropdown( $pricelist_id ); ?>
			<p class="description"><?php esc_html_e( 'Select a pricelist PDF for this category.', 'palaplast' ); ?></p>
			<p class="description"><strong><?php esc_html_e( 'Selected Pricelist:', 'palaplast' ); ?></strong>
				<?php echo $pricelist_id ? esc_html( palaplast_get_pricelist_name_by_id( $pricelist_id ) ) : esc_html__( 'None', 'palaplast' ); ?>
			</p>
			<p class="description"><strong><?php esc_html_e( 'Inherited Pricelist:', 'palaplast' ); ?></strong>
				<?php echo $inherited_name ? esc_html( $inherited_name ) : esc_html__( 'None', 'palaplast' ); ?>
			</p>
		</td>
	</tr>
	<?php
}

function palaplast_save_category_sheet( $term_id ) {
	if ( ! isset( $_POST['palaplast_technical_sheet_id'] ) ) {
		return;
	}

	$sheet_id = absint( wp_unslash( $_POST['palaplast_technical_sheet_id'] ) );
	$sheets   = palaplast_get_technical_sheets();

	if ( $sheet_id && ! isset( $sheets[ $sheet_id ] ) ) {
		$sheet_id = 0;
	}

	if ( $sheet_id ) {
		update_term_meta( $term_id, 'palaplast_technical_sheet_id', $sheet_id );
	} else {
		delete_term_meta( $term_id, 'palaplast_technical_sheet_id' );
	}
}

function palaplast_save_category_pricelist( $term_id ) {
	if ( ! isset( $_POST['palaplast_pricelist_id'] ) ) {
		return;
	}

	$pricelist_id = absint( wp_unslash( $_POST['palaplast_pricelist_id'] ) );
	$pricelists   = palaplast_get_pricelists();

	if ( $pricelist_id && ! isset( $pricelists[ $pricelist_id ] ) ) {
		$pricelist_id = 0;
	}

	if ( $pricelist_id ) {
		update_term_meta( $term_id, 'palaplast_pricelist_id', $pricelist_id );
	} else {
		delete_term_meta( $term_id, 'palaplast_pricelist_id' );
	}
}

function palaplast_render_category_sheet_dropdown( $selected_id ) {
	$sheets = palaplast_get_technical_sheets();
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

function palaplast_render_category_pricelist_dropdown( $selected_id ) {
	$pricelists = palaplast_get_pricelists();
	?>
	<select name="palaplast_pricelist_id" id="palaplast_pricelist_id">
		<option value="0"><?php esc_html_e( '— None —', 'palaplast' ); ?></option>
		<?php foreach ( $pricelists as $pricelist_id => $pricelist ) : ?>
			<option value="<?php echo esc_attr( $pricelist_id ); ?>" <?php selected( (int) $selected_id, (int) $pricelist_id ); ?>>
				<?php echo esc_html( isset( $pricelist['name'] ) ? $pricelist['name'] : '' ); ?>
			</option>
		<?php endforeach; ?>
	</select>
	<?php
}
