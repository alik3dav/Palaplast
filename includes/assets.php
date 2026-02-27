<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_enqueue_styles() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	wp_register_style( 'palaplast', false, array(), PALAPLAST_VERSION );
	wp_enqueue_style( 'palaplast' );
	wp_add_inline_style( 'palaplast', palaplast_get_styles() );
}

function palaplast_enqueue_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'woocommerce_page_palaplast-technical-sheets', 'woocommerce_page_palaplast-pricelists' ), true ) ) {
		return;
	}

	$selection_title = 'woocommerce_page_palaplast-pricelists' === $hook_suffix
		? __( 'Select Pricelist PDF', 'palaplast' )
		: __( 'Select Technical Sheet PDF', 'palaplast' );

	wp_enqueue_media();
	wp_add_inline_script(
		'jquery-core',
		"jQuery(function($){var frame;$('.palaplast-select-pdf').on('click',function(e){e.preventDefault();if(frame){frame.open();return;}frame=wp.media({title:'" . esc_js( $selection_title ) . "',button:{text:'" . esc_js( __( 'Use PDF', 'palaplast' ) ) . "'},library:{type:'application/pdf'},multiple:false});frame.on('select',function(){var attachment=frame.state().get('selection').first().toJSON();$('#palaplast_attachment_id').val(attachment.id);$('.palaplast-selected-file').text(attachment.filename || attachment.url);});frame.open();});$('.palaplast-remove-pdf').on('click',function(e){e.preventDefault();$('#palaplast_attachment_id').val('');$('.palaplast-selected-file').text('" . esc_js( __( 'No file selected.', 'palaplast' ) ) . "');});});"
	);
}

function palaplast_get_styles() {
	return '.palaplast-matrix{margin-top:10px;margin-bottom:30px}.palaplast-title{font-size:14px;font-weight:500;margin-bottom:10px;color:#222}.palaplast-table-wrap{overflow-x:auto}.palaplast-table{width:100%;border-collapse:collapse;font-size:13px;line-height:1.4}.palaplast-table th,.palaplast-table td{border-bottom:1px solid #eee;padding:6px 10px;vertical-align:middle;white-space:nowrap}.palaplast-table .col-sku{text-align:left}.palaplast-table .col-attr{text-align:center}.palaplast-table th{font-weight:600;color:#333;background:#fafafa}.palaplast-table tr:last-child td{border-bottom:none}.palaplast-technical-sheet,.palaplast-pricelist{margin:16px 0 0}.palaplast-technical-sheet-button.button,.palaplast-pricelist-button.button{display:inline-flex;align-items:center;gap:6px}@media (max-width:768px){.palaplast-table{font-size:12px}.palaplast-table th,.palaplast-table td{padding:5px 6px}}';
}
