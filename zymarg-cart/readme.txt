=== ZYMARG Cart ===
Contributors: zymarg
Tags: woocommerce, cart, multi-vendor, elementor, dokan
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.8
WC requires at least: 9.0
WC tested up to: 9.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A fully customizable multi-vendor cart plugin for the ZYMARG marketplace.

== Description ==

ZYMARG Cart provides three interconnected Elementor Pro widgets that replace
the default WooCommerce cart page with a fully branded, multi-vendor-aware
shopping cart experience.

= Three Widgets =
* **Widget 1 — Cart Header**: Live item count, edit/delete mode toggle.
* **Widget 2 — Cart Body**: Vendor-grouped product rows with variation switcher,
  quantity stepper, per-product and per-vendor coupons, Save for Later, stock
  warnings, and vendor subtotals.
* **Widget 3 — Cart Total**: Sliding order summary breakdown (subtotal, discount,
  shipping, SST tax, grand total), master select-all, and partial checkout.

= Key Features =
* Partial checkout — buy only selected items; unselected items stay in cart.
* Save for Later — hybrid storage (session for guests, user meta for logged-in
  customers) with automatic merge on login.
* ~300 Elementor controls covering every style, layout, and behaviour option
  with full Desktop / Tablet / Mobile responsive values.
* PHP 8.0+ compatible (tested up to PHP 8.4), WooCommerce HPOS compatible, Dokan Pro integrated.
* Fully translatable via standard WordPress i18n.

== Installation ==

1. Upload the `zymarg-cart` folder to `/wp-content/plugins/`.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Ensure WooCommerce, Elementor Pro, and Dokan Pro are installed and active.
4. Open any page in Elementor and search for "ZYMARG Cart" in the widget panel.

== Frequently Asked Questions ==

= Does this replace the default WooCommerce cart page? =
No. It provides Elementor widgets you place on any page. Assign that page as
your WooCommerce cart page under WooCommerce → Settings → Advanced.

= Is it compatible with Malaysian payment gateways? =
Yes. The partial checkout uses a reinforced session swap with three backup-clear
hooks (woocommerce_thankyou, order_status_processing, order_status_completed)
to handle all gateway redirect patterns including iPay88, Billplz, and FPX.

== Changelog ==

= 1.0.8 =
* Arrow icon now always visible in Order Summary bar (forced independent of Elementor toggle setting).
* Fixed ghost whitespace below Order Summary bar when panel is collapsed.
* Order Summary panel now auto-collapses when all products are unchecked.
* Vendor subtotal rows now update dynamically based on selected items only.
* Per-vendor selected subtotals added to get_totals_for_selected() response.
* applyTotals() JS function now syncs vendor subtotal rows on every selection change.

= 1.0.7 =
* Order summary breakdown panel now collapsed by default on page load.
* Breakdown panel auto-expands when at least one product is checked.
* Arrow icon changed to chevron-down (collapsed) / chevron-up (expanded) for clearer UX.
* Removed sessionStorage persistence — panel always resets to collapsed on page load.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
