=== Palaplast ===
Contributors: palaplast
Tags: woocommerce, variations, table, matrix, products
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.8.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WooCommerce plugin that renders a clean variation matrix (SKU + attributes + price) above product tabs on variable product pages.

== Description ==

Palaplast displays a compact variation matrix for WooCommerce variable products and keeps the output readable on all screen sizes.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/palaplast`.
2. Activate **Palaplast** through the **Plugins** menu in WordPress.
3. Open a variable product page to confirm the matrix appears above product tabs.

== Changelog ==

= 1.8.0 =
* Added a new Pricelists feature with dedicated admin CRUD screen (Name + PDF) under WooCommerce.
* Added category-level Pricelist PDF assignment with parent-category inheritance and read-only selected/inherited indicators.
* Added frontend "Download Pricelist" button on product pages with deterministic category resolution for multi-category products.
* Deleting a pricelist now clears category references so inheritance can fall back automatically.

= 1.6.2 =
* Declared compatibility with WooCommerce custom order tables (HPOS) and Cart/Checkout blocks to prevent false incompatible plugin notices.
* Updated variation visibility filter callback signature to match WooCommerce hook arguments.

= 1.6.0 =
* Initial release.
