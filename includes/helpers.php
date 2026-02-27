<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function palaplast_get_technical_sheets() {
	$sheets = get_option( 'palaplast_technical_sheets', array() );

	return is_array( $sheets ) ? $sheets : array();
}

function palaplast_get_pricelists() {
	$pricelists = get_option( 'palaplast_pricelists', array() );

	return is_array( $pricelists ) ? $pricelists : array();
}

function palaplast_get_sheet_name_by_id( $sheet_id ) {
	$sheets = palaplast_get_technical_sheets();

	if ( empty( $sheets[ $sheet_id ]['name'] ) ) {
		return '';
	}

	return (string) $sheets[ $sheet_id ]['name'];
}

function palaplast_get_pricelist_name_by_id( $pricelist_id ) {
	$pricelists = palaplast_get_pricelists();

	if ( empty( $pricelists[ $pricelist_id ]['name'] ) ) {
		return '';
	}

	return (string) $pricelists[ $pricelist_id ]['name'];
}

function palaplast_is_valid_pdf_attachment( $attachment_id ) {
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		return false;
	}

	$file_path = get_attached_file( $attachment_id );
	$file_type = $file_path ? wp_check_filetype( $file_path ) : array();

	return isset( $file_type['ext'] ) && 'pdf' === strtolower( (string) $file_type['ext'] );
}

function palaplast_clear_sheet_from_categories( $sheet_id ) {
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

function palaplast_clear_pricelist_from_categories( $pricelist_id ) {
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
		$current_pricelist = (int) get_term_meta( (int) $term_id, 'palaplast_pricelist_id', true );

		if ( $current_pricelist === (int) $pricelist_id ) {
			delete_term_meta( (int) $term_id, 'palaplast_pricelist_id' );
		}
	}
}

function palaplast_resolve_category_sheet( $term_id ) {
	$term = get_term( $term_id, 'product_cat' );

	if ( ! $term instanceof WP_Term ) {
		return array();
	}

	$sheets       = palaplast_get_technical_sheets();
	$current_term = $term;
	$distance     = 0;

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

function palaplast_resolve_category_pricelist( $term_id ) {
	$term = get_term( $term_id, 'product_cat' );

	if ( ! $term instanceof WP_Term ) {
		return array();
	}

	$pricelists   = palaplast_get_pricelists();
	$current_term = $term;
	$distance     = 0;

	while ( $current_term instanceof WP_Term && 'product_cat' === $current_term->taxonomy ) {
		$pricelist_id = (int) get_term_meta( (int) $current_term->term_id, 'palaplast_pricelist_id', true );

		if ( $pricelist_id && isset( $pricelists[ $pricelist_id ] ) ) {
			$file_url = ! empty( $pricelists[ $pricelist_id ]['attachment_id'] ) ? wp_get_attachment_url( (int) $pricelists[ $pricelist_id ]['attachment_id'] ) : '';

			if ( $file_url ) {
				return array(
					'name'         => (string) $pricelists[ $pricelist_id ]['name'],
					'file_url'     => (string) $file_url,
					'distance'     => $distance,
					'pricelist_id' => $pricelist_id,
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

function palaplast_get_category_inherited_sheet( $term_id ) {
	$term = get_term( $term_id, 'product_cat' );

	if ( ! $term instanceof WP_Term || empty( $term->parent ) ) {
		return array();
	}

	return palaplast_resolve_category_sheet( (int) $term->parent );
}

function palaplast_get_category_inherited_pricelist( $term_id ) {
	$term = get_term( $term_id, 'product_cat' );

	if ( ! $term instanceof WP_Term || empty( $term->parent ) ) {
		return array();
	}

	return palaplast_resolve_category_pricelist( (int) $term->parent );
}

function palaplast_get_product_technical_sheet( $product_id ) {
	$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	sort( $terms, SORT_NUMERIC );
	$candidates = array();

	foreach ( $terms as $index => $term_id ) {
		$resolved_sheet = palaplast_resolve_category_sheet( (int) $term_id );

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

function palaplast_get_product_pricelist( $product_id ) {
	$terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	sort( $terms, SORT_NUMERIC );
	$candidates = array();

	foreach ( $terms as $index => $term_id ) {
		$resolved_pricelist = palaplast_resolve_category_pricelist( (int) $term_id );

		if ( empty( $resolved_pricelist['file_url'] ) ) {
			continue;
		}

		$candidates[] = array(
			'distance'  => isset( $resolved_pricelist['distance'] ) ? (int) $resolved_pricelist['distance'] : PHP_INT_MAX,
			'order'     => (int) $index,
			'term_id'   => (int) $term_id,
			'pricelist' => array(
				'name'     => (string) $resolved_pricelist['name'],
				'file_url' => (string) $resolved_pricelist['file_url'],
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

	return $candidates[0]['pricelist'];
}
