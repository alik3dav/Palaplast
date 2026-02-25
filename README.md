# Palaplast

A lightweight WooCommerce plugin that renders a clean variation matrix (SKU + attributes + price) above product tabs on variable product pages.

## What it does

- Outputs a table for variable products only.
- Shows SKU, each variation attribute, and the formatted WooCommerce price.
- Resolves taxonomy attribute values to proper term names (e.g., preserving capitalization).
- Gracefully handles empty values by printing `—`.
- Adds responsive horizontal scrolling on smaller screens.

## Technical notes

- Uses WooCommerce hook `woocommerce_after_single_product_summary` with priority `4`.
- Loads only when WooCommerce is active.
- Enqueues CSS via `wp_enqueue_scripts` instead of printing inline `<style>` inside content output.
- Escapes all dynamic output in the table.

## Installation

1. Copy `palaplast.php` to your plugins directory as `palaplast/palaplast.php`.
2. Activate **Palaplast** in WordPress admin.
3. Open any variable product page to verify the matrix appears above product tabs.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 6.0+
