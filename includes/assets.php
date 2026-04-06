<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_enqueue_styles() {
	$should_enqueue = function_exists( 'is_product' ) && is_product();

	if ( ! $should_enqueue && is_singular() ) {
		$post = get_post();
		if ( $post instanceof WP_Post ) {
			$should_enqueue = has_shortcode( $post->post_content, 'palaplast_technical_sheets_list' ) || has_shortcode( $post->post_content, 'palaplast_pricelists_list' ) || has_shortcode( $post->post_content, 'palaplast_certificates_list' );
		}
	}

	if ( ! $should_enqueue ) {
		return;
	}

	wp_enqueue_style( 'palaplast', PALAPLAST_PLUGIN_URL . 'assets/css/palaplast-frontend.css', array(), PALAPLAST_VERSION );

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
	wp_enqueue_style( 'palaplast-admin', PALAPLAST_PLUGIN_URL . 'assets/css/palaplast-admin.css', array(), PALAPLAST_VERSION );
	wp_add_inline_script(
		'jquery-core',
		"jQuery(function($){var frame;$('.palaplast-select-pdf').on('click',function(e){e.preventDefault();if(frame){frame.open();return;}frame=wp.media({title:'" . esc_js( $selection_title ) . "',button:{text:'" . esc_js( __( 'Use PDF', 'palaplast' ) ) . "'},library:{type:'application/pdf'},multiple:false});frame.on('select',function(){var attachment=frame.state().get('selection').first().toJSON();$('#palaplast_attachment_id').val(attachment.id);$('.palaplast-selected-file').text(attachment.filename || attachment.url);});frame.open();});$('.palaplast-remove-pdf').on('click',function(e){e.preventDefault();$('#palaplast_attachment_id').val('');$('.palaplast-selected-file').text('" . esc_js( __( 'No file selected.', 'palaplast' ) ) . "');});});"
	);
}

function palaplast_get_scripts() {
	return <<<'JS'
jQuery(function($){$(document).on('click','.palaplast-table .palaplast-copy-code',function(){var button=this;var value=button.getAttribute('data-copy-value');if(!value){return;}var onCopied=function(){button.classList.add('is-copied');var textEl=button.querySelector('.palaplast-copy-code__text');if(textEl){textEl.textContent='Copied';}clearTimeout(button._palaplastCopyTimer);button._palaplastCopyTimer=setTimeout(function(){button.classList.remove('is-copied');if(textEl){textEl.textContent='Copy';}},1200);};if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(value).then(onCopied).catch(function(){var fallback=$('<textarea>').val(value).css({position:'fixed',opacity:0}).appendTo('body');fallback[0].select();try{document.execCommand('copy');onCopied();}catch(e){}fallback.remove();});return;}var fallback=$('<textarea>').val(value).css({position:'fixed',opacity:0}).appendTo('body');fallback[0].select();try{document.execCommand('copy');onCopied();}catch(e){}fallback.remove();});});
JS;
}
