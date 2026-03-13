<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_enqueue_styles() {
	$should_enqueue = function_exists( 'is_product' ) && is_product();

	if ( ! $should_enqueue && is_singular() ) {
		$post = get_post();
		if ( $post instanceof WP_Post ) {
			$should_enqueue = has_shortcode( $post->post_content, 'palaplast_technical_sheets_list' ) || has_shortcode( $post->post_content, 'palaplast_pricelists_list' );
		}
	}

	if ( ! $should_enqueue ) {
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
	return '.palaplast-matrix{margin-top:10px;margin-bottom:30px}.palaplast-title{font-size:14px;font-weight:500;margin-bottom:10px;color:#222}.palaplast-table-wrap{overflow-x:auto}.palaplast-table{width:100%;border-collapse:collapse;font-size:13px;line-height:1.4}.palaplast-table th,.palaplast-table td{border-bottom:1px solid #eee;padding:6px 10px;vertical-align:middle;white-space:nowrap}.palaplast-table .col-sku{text-align:left}.palaplast-table .col-attr{text-align:center}.palaplast-table th{font-weight:600;color:#333;background:#fafafa;line-height:1.2}.palaplast-table th .spec-title{display:block}.palaplast-table th .spec-unit{display:block;font-size:12px;opacity:.7;line-height:1.1;margin-top:2px}.palaplast-table tr:last-child td{border-bottom:none}.palaplast-table .vt-custom-row td{white-space:normal;text-align:left;padding:8px 10px}.palaplast-table .vt-custom-row__content{font-size:12px;line-height:1.45}.palaplast-table .vt-custom-row--info td{background:#eef6ff;color:#1f3a5f}.palaplast-table .vt-custom-row--warning td{background:#fff7e6;color:#7a4b00}.palaplast-table .vt-custom-row--note td{background:#f6f6f6;color:#444}.palaplast-technical-sheet,.palaplast-pricelist{margin:16px 0 0}.palaplast-technical-sheet-button.button,.palaplast-pricelist-button.button{display:inline-flex;align-items:center;gap:6px}.palaplast-pdf-list{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:12px}.palaplast-pdf-list-item{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease}.palaplast-pdf-list-item:hover{border-color:#d1d5db;box-shadow:0 8px 18px rgba(15,23,42,.08);transform:translateY(-1px)}.palaplast-pdf-list-item__title{font-size:15px;line-height:1.45;font-weight:500;color:#1f2937}.palaplast-pdf-list-item__action{display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-radius:999px;background:#111827;color:#fff;font-size:13px;font-weight:600;line-height:1;text-decoration:none;white-space:nowrap;transition:background-color .2s ease,color .2s ease}.palaplast-pdf-list-item__action:hover,.palaplast-pdf-list-item__action:focus{background:#374151;color:#fff}.palaplast-pdf-list-item__action:focus-visible{outline:2px solid #111827;outline-offset:2px}@media (max-width:768px){.palaplast-table{font-size:12px}.palaplast-table th,.palaplast-table td{padding:5px 6px}.palaplast-table .vt-custom-row td{padding:6px}.palaplast-pdf-list-item{flex-direction:column;align-items:flex-start}.palaplast-pdf-list-item__action{width:100%;justify-content:center}}';
}
