<?php
/**
 * Plugin Name: HydroMax Variation Matrix
 * Description: Displays a clean, compact variation matrix (SKU + attributes + price) above the product tabs for variable WooCommerce products.
 * Version: 1.5
 * Author: HydroMax
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'woocommerce_after_single_product_summary', 'hydromax_render_matrix_table', 4 );

function hydromax_render_matrix_table() {
    global $product;

    if ( ! $product || ! $product->is_type( 'variable' ) ) return;

    $available_variations = $product->get_available_variations();
    $attributes = array_keys( $product->get_variation_attributes() );
    if ( empty( $available_variations ) ) return;
    ?>

    <div class="hydromax-matrix">
        <h4 class="hydromax-title">Product Variations</h4>
        <table class="hydromax-table">
            <thead>
                <tr>
                    <th class="col-sku">SKU</th>
                    <?php foreach ( $attributes as $attr_name ) : ?>
                        <th class="col-attr"><?php echo wc_attribute_label( $attr_name ); ?></th>
                    <?php endforeach; ?>
                    <th class="col-price">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $available_variations as $variation ) :
                    $variation_obj = wc_get_product( $variation['variation_id'] ); ?>
                    <tr>
                        <td class="col-sku"><?php echo esc_html( $variation_obj->get_sku() ?: '—' ); ?></td>
                        <?php foreach ( $attributes as $attr_name ) :
                            $value_slug = $variation['attributes'][ 'attribute_' . $attr_name ] ?? '';
                            $taxonomy = wc_attribute_taxonomy_name( $attr_name );

                            // Get the original term name (with correct case)
                            if ( taxonomy_exists( $taxonomy ) && $value_slug ) {
                                $term = get_term_by( 'slug', $value_slug, $taxonomy );
                                $value = $term ? $term->name : $value_slug;
                            } else {
                                $value = $value_slug;
                            }

                            echo '<td class="col-attr">' . esc_html( $value ) . '</td>';

                        endforeach; ?>
                        <td class="col-price"><?php echo $variation_obj->get_price_html(); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
    .hydromax-matrix {
        margin-top: 10px;
        margin-bottom: 30px;
    }
    .hydromax-title {
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 10px;
        color: #222;
    }
    .hydromax-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        line-height: 1.4;
    }
    .hydromax-table th,
    .hydromax-table td {
        border-bottom: 1px solid #eee;
        padding: 6px 10px;
        vertical-align: middle;
        white-space: nowrap;
    }

    /* Column alignment logic */
    .hydromax-table .col-sku {
        text-align: left;
    }
    .hydromax-table .col-attr {
        text-align: center;
    }
    .hydromax-table .col-price {
        text-align: right;
    }

    .hydromax-table th {
        font-weight: 600;
        color: #333;
        background: #fafafa;
    }
    .hydromax-table tr:last-child td {
        border-bottom: none;
    }

    @media (max-width: 768px) {
        .hydromax-table {
            font-size: 12px;
        }
        .hydromax-table th,
        .hydromax-table td {
            padding: 5px 6px;
        }
    }
    </style>
    <?php
}
