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

	wp_enqueue_script( 'jquery-core' );
	wp_add_inline_script( 'jquery-core', palaplast_get_scripts() );
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
	return '.palaplast-matrix{margin-top:10px;margin-bottom:30px}.palaplast-title{font-size:14px;font-weight:500;margin-bottom:10px;color:#222}.palaplast-table-wrap{overflow-x:auto;border:1px solid #e7e9ed;border-radius:10px;background:#fff}.palaplast-table{width:100%;table-layout:auto;border-collapse:separate;border-spacing:0;font-size:11px;line-height:1.25;color:#1f2937}.palaplast-table th,.palaplast-table td{border-bottom:1px solid #edf1f5;vertical-align:middle;white-space:nowrap}.palaplast-table th{padding:6px 7px;text-align:center}.palaplast-table td{padding:0 7px;text-align:center}.palaplast-table .col-sku{width:1%;text-align:left}.palaplast-table .col-attr{text-align:center}.palaplast-table th{font-size:11px;font-weight:500;color:#374151;background:#f8fafc;line-height:1.15;letter-spacing:.005em}.palaplast-table th .spec-title{display:block}.palaplast-table th .spec-unit{display:block;font-size:9px;opacity:.55;line-height:1.05;margin-top:1px}.palaplast-table tbody tr{transition:background-color .14s ease}.palaplast-table tbody tr:hover td{background:#f7fafd}.palaplast-table tr:last-child td{border-bottom:none}.palaplast-table .vt-custom-row td{white-space:normal;text-align:left;padding:0 7px}.palaplast-table .vt-custom-row__content{font-size:10px;line-height:1.35}.palaplast-table .vt-custom-row--info td{background:#eef6ff;color:#1f3a5f}.palaplast-table .vt-custom-row--warning td{background:#fff7e6;color:#7a4b00}.palaplast-table .vt-custom-row--note td{background:#f6f6f6;color:#444}.palaplast-table .palaplast-code-cell{display:inline-flex;align-items:center;justify-content:flex-start;gap:2px;max-width:100%}.palaplast-table .palaplast-code-value{font-variant-numeric:tabular-nums}.palaplast-table .palaplast-copy-code{-webkit-appearance:none;appearance:none;-webkit-tap-highlight-color:transparent;position:relative;display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:14px;height:14px;margin-left:1px;padding:0;border:0;border-radius:3px;background:transparent;color:#6b7280;line-height:1;cursor:pointer;transition:color .14s ease,transform .08s ease,opacity .14s ease}.palaplast-table .palaplast-copy-code:hover{color:#374151;opacity:.96}.palaplast-table .palaplast-copy-code:active{transform:scale(.92)}.palaplast-table .palaplast-copy-code:focus{outline:none;box-shadow:none}.palaplast-table .palaplast-copy-code:focus-visible{outline:1px solid #bfdbfe;outline-offset:1px}.palaplast-table .palaplast-copy-code.is-copied{color:#0f766e}.palaplast-table .palaplast-copy-code.is-copied::after{content:"Copied";position:absolute;bottom:calc(100% + 3px);left:50%;transform:translateX(-50%);padding:1px 4px;border-radius:3px;background:#111827;color:#fff;font-size:8px;font-weight:500;line-height:1;white-space:nowrap;letter-spacing:.01em;pointer-events:none}.palaplast-table .palaplast-copy-code__icon{display:inline-flex;width:10px;height:10px}.palaplast-table .palaplast-copy-code__icon svg{display:block;width:100%;height:100%;fill:currentColor}.palaplast-table .palaplast-copy-code__text{position:absolute!important;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}.palaplast-technical-sheet,.palaplast-pricelist{margin:16px 0 0}.palaplast-technical-sheet-button.button,.palaplast-pricelist-button.button{display:inline-flex;align-items:center;gap:6px}.palaplast-pdf-list{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:12px}.palaplast-pdf-list-item{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 16px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;transition:border-color .2s ease,box-shadow .2s ease,transform .2s ease}.palaplast-pdf-list-item:hover{border-color:#d1d5db;box-shadow:0 8px 18px rgba(15,23,42,.08);transform:translateY(-1px)}.palaplast-pdf-list-item__title{font-size:15px;line-height:1.45;font-weight:500;color:#1f2937}.palaplast-pdf-list-item__action{display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-radius:999px;background:#111827;color:#fff;font-size:13px;font-weight:600;line-height:1;text-decoration:none;white-space:nowrap;transition:background-color .2s ease,color .2s ease}.palaplast-pdf-list-item__action:hover,.palaplast-pdf-list-item__action:focus{background:#374151;color:#fff}.palaplast-pdf-list-item__action:focus-visible{outline:2px solid #111827;outline-offset:2px}@media (max-width:768px){.palaplast-table{font-size:10.5px}.palaplast-table th{padding:6px 5px}.palaplast-table td{padding:0 5px}.palaplast-table .vt-custom-row td{padding:0 5px}.palaplast-table .palaplast-copy-code{width:13px;height:13px}.palaplast-table .palaplast-copy-code__icon{width:9px;height:9px}.palaplast-pdf-list-item{flex-direction:column;align-items:flex-start}.palaplast-pdf-list-item__action{width:100%;justify-content:center}}';
}

function palaplast_get_scripts() {
	return <<<'JS'
jQuery(function($){$(document).on('click','.palaplast-table .palaplast-copy-code',function(){var button=this;var value=button.getAttribute('data-copy-value');if(!value){return;}var onCopied=function(){button.classList.add('is-copied');var textEl=button.querySelector('.palaplast-copy-code__text');if(textEl){textEl.textContent='Copied';}clearTimeout(button._palaplastCopyTimer);button._palaplastCopyTimer=setTimeout(function(){button.classList.remove('is-copied');if(textEl){textEl.textContent='Copy';}},1200);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(value).then(onCopied).catch(function(){var fallback=$('<textarea>').val(value).css({position:'fixed',opacity:0}).appendTo('body');fallback[0].select();try{document.execCommand('copy');onCopied();}catch(e){}fallback.remove();});return;}var fallback=$('<textarea>').val(value).css({position:'fixed',opacity:0}).appendTo('body');fallback[0].select();try{document.execCommand('copy');onCopied();}catch(e){}fallback.remove();});});
JS;
}
