=== ZYMARG Cart ===
Contributors: zymarg
Tags: woocommerce, cart, multi-vendor, elementor, dokan
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.1.2
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
  shipping, tax, grand total), master select-all, and partial checkout.

= Key Features =
* Partial checkout — buy only selected items; unselected items stay in cart.
* Save for Later — hybrid storage (session for guests, user meta for logged-in
  customers) with automatic merge on login.
* ~300 Elementor controls covering every style, layout, and behaviour option
  with full Desktop / Tablet / Mobile responsive values.
* Filterable tax label — defaults to "Tax (6% SST)" for Malaysia, override
  via the `zymarg_cart_tax_label` filter for other regions.
* PHP 8.1+ compatible (tested up to PHP 8.4), WooCommerce HPOS compatible, Dokan Pro integrated.
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

= 1.1.2 =
* **Removed: Grand Total row inside the Order Summary breakdown panel.**
  The Grand Total now appears only in the always-visible action bar at
  the bottom of the cart total widget — it no longer also appears inside
  the expandable breakdown panel above. The horizontal divider that sat
  above the in-breakdown Grand Total has been removed too. The
  Elementor controls `Show Panel Grand Total`, `Show Divider`, and the
  Grand Total label text setting are kept in the widget for backwards
  compatibility but no longer render anything.
* **Fixed: Order Summary bar now collapses fully (for real this time).**
  The v1.1.1 collapse fix was being defeated by the widget's own
  Inner Padding Elementor control, whose generated inline `<style>`
  selector (`{{WRAPPER}} .zymarg-breakdown-inner`) had higher CSS
  specificity than the collapsed-state padding rule in the plugin's
  CSS file. As a result, the inner kept its 14px top/bottom padding
  even when the panel was supposed to be closed, leaving a partly
  visible Subtotal row between the bar and the action bar. Fixed by
  scoping the widget's Inner Padding and Inner Gap controls to
  `.zymarg-breakdown-panel.breakdown-open .zymarg-breakdown-inner`,
  so user-customized padding only applies when the panel is expanded.
  When collapsed, the plain `.zymarg-breakdown-inner` rule from the
  CSS file wins, padding-top and padding-bottom collapse to 0, and
  the bar sits flush against the action bar.

= 1.1.1 =
* **Fixed: Order Summary bar now collapses fully.** The breakdown panel
  previously left the Subtotal row partly visible between the Order
  Summary bar and the action bar (master checkbox + Grand Total +
  Checkout button) on some browser/theme combinations. The collapse
  is now bulletproof — the two bars sit flush against each other when
  collapsed. Hardened with `overflow: hidden` on the panel and
  `min-block-size: 0` on the inner so it works across all browsers
  and themes.
* **Fixed: Toggle arrow always renders.** The Order Summary bar's
  toggle arrow previously used the Tabler Icons webfont and was
  invisible on sites where the icon font failed to load. It is now an
  inline SVG (no font dependency). Direction convention:
    - Collapsed → arrow UP
    - Expanded  → arrow DOWN (rotates 180° via existing CSS)
  Existing widget controls for arrow color and size continue to work.

= 1.1.0 =
* **Breaking:** Minimum PHP version is now 8.1 (was 8.0).
* Save-for-Later item-key hash format changed from PHP serialize() to
  wp_json_encode() for stability across PHP versions. Existing saved items
  are migrated lazily on first read after upgrade — no manual action needed,
  no data loss.
* Tax line label is now filterable via the `zymarg_cart_tax_label` filter
  (default remains "Tax (6% SST)" for Malaysia).
* Fixed accessibility bug: checkout button aria-label was double-escaped,
  causing entities like "&" to render as "&amp;amp;" in screen readers.
* Fixed a guest-to-logged-in merge fallback that was effectively a no-op
  due to an incorrect `is_user_logged_in()` gate; the transient lock inside
  the merge already handles deduplication, so the gate has been removed.
* `Helpers::send_success()` and `Helpers::send_error()` now use the PHP 8.1
  `: never` return type for accurate static analysis.
* Added inline documentation explaining the priority chain between the
  Save-for-Later merge hooks and the Partial-Checkout login hook.
* Removed dead `wp_clear_scheduled_hook()` calls in `deactivate()` for
  cron hooks that were never scheduled.
* Removed literal `{includes,widgets,...}` directory artefact that shipped
  with v1.0.8 due to a `mkdir` brace-expansion mishap.
* Added `Tested up to: 6.7` plugin header so it matches readme.txt.
* `vendor_subtotals_html` is now always present in the AJAX totals response,
  including when the cart is empty — frontend JS no longer hits `undefined`
  on that branch.
* Removed duplicate `$user_id` assignment in `handle_zymarg_save_for_later`.

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

= 1.1.2 =
UI cleanup: removes the duplicate Grand Total row from inside the
Order Summary breakdown panel (it stays in the action bar), and
finally fixes the Order Summary bar so it collapses fully — no more
partly-visible Subtotal row between the bar and the action bar.

= 1.1.1 =
UI fix release. Order Summary bar now collapses fully (no half-visible
Subtotal row), and the toggle arrow renders reliably regardless of
icon-font availability.

= 1.1.0 =
Requires PHP 8.1+. Upgrades the saved-items hash format — existing saved
items are migrated automatically on first read, no manual action required.

= 1.0.0 =
Initial release.
