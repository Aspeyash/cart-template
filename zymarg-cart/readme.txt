=== ZYMARG Cart ===
Contributors: zymarg
Tags: woocommerce, cart, multi-vendor, elementor, dokan
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.2.1
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

= 1.2.1 =
* **[Layout fix] Desktop unit price now sits in its own column.**
  v1.2.0 placed the new Product Price element under the title (Option A,
  inside `.zymarg-col-title`). v1.2.1 moves it to a dedicated
  `.zymarg-col-price` column between the title and the variation/qty
  stepper, which is what was actually wanted. The desktop grid is
  updated from a 6-column to a 7-column layout:
    cb | img | title | **price** | variation | subtotal | coupon
  The two duplicate price elements (`.zymarg-product-price--desktop`
  inside col-title and `.zymarg-product-price--mobile` inside its own
  cell) have been consolidated into a single `.zymarg-product-price`
  inside the new `.zymarg-col-price` cell. CSS Grid's `grid-area: price`
  positions the same DOM element correctly on both viewports — between
  title and variation on desktop, under the image on mobile.
* **[Bug fix] Mobile "Have a coupon?" button now opens the form.**
  In v1.2.0 the mobile coupon-toggle was a silent no-op. The JS
  handler in `assets/js/zymarg-cart.js` located the form by walking up
  the DOM from the clicked button to `.zymarg-col-coupon`, then
  searching for `.zymarg-coupon-form` inside. That worked for the
  desktop toggle (which IS inside `.zymarg-col-coupon`) but failed for
  the new mobile toggle, which lives inside
  `.zymarg-col-mobile-actions` (a different parent cell). The fix:
  resolve the form via the `aria-controls` attribute that both
  toggles already carry, so the form ID is looked up directly via
  `document.getElementById()` regardless of where in the DOM the
  toggle button sits. The `aria-expanded` state is now also synced
  on both the desktop and mobile toggle for the same row, so screen
  readers report the correct state regardless of which button was
  pressed.

= 1.2.0 =
* **[New feature] Product Price element added to every cart row.** A
  prominent unit-price display now appears under the product title on
  desktop and under the product image on mobile. For variable
  products, the price updates live when the customer changes the
  variation dropdown. Sale prices are reflected automatically — when
  a product is on sale, the displayed value is the sale price (the
  regular price is not struck through, per the explicit project
  decision; only the active price is shown).
* **[Layout] Mobile cart-body redesign — 3 column × 5 row grid.**
  Replaces the previous mobile layout with:
    Col 1: checkbox (full height)
    Col 2: product image (rows 1–2) stacked over unit price (row 3)
    Col 3 row 1: title + SKU
    Col 3 row 2: variation switcher inline with quantity stepper
    Col 3 row 3: line subtotal
    Col 3 row 4: Save-for-later inline with Have-a-coupon, equal-spaced
    Col 3 row 5: coupon form (only visible when expanded)
  When Save-for-later is disabled in widget settings, the Have-a-coupon
  button left-aligns automatically. Respects the existing
  `mobile_breakpoint` widget setting (default 768px).
* **[Removed] The "× qty" breakdown line under the desktop subtotal**
  (the small `18₺ × 1` line) has been removed. Its information is now
  carried by the new Product Price element under the title plus the
  always-visible quantity stepper. The old `show_unit_price` toggle
  and `unit_price_color` Elementor control are kept as silent no-ops
  for backwards compatibility with existing saved page settings — they
  no longer affect anything but won't cause editor errors.
* **[New widget controls] Product Price section** added under the
  cart-body widget Style tab with:
    - Show Product Price toggle (default: yes)
    - Color (default: ZYMARG primary `#9500a5`)
    - Typography (font size, weight — responsive)
    - Margin (responsive — desktop / tablet / mobile)
* **[JS] `updateRowUnitPrice()`** in `assets/js/zymarg-cart-ajax.js`
  now targets `.zymarg-product-price` so both the desktop and the
  mobile price displays stay in sync when a variation changes.
  `updateRowUnitPriceQty()` becomes a documented no-op for the same
  reason and is kept only to preserve call-site compatibility.

= 1.1.4 =
* **[Critical] Cash-on-Delivery (and other instant-status gateways) no
  longer wipes the unselected cart items after partial checkout.**
  Pre-1.1.4, the post-order restore tried to re-add unselected items
  directly to the WC cart inside `woocommerce_order_status_processing`
  — which fires INLINE during COD checkout submission, BEFORE
  WooCommerce's own `empty_cart()` runs. The just-added items got
  wiped milliseconds later, and a per-order transient lock then
  blocked the later `woocommerce_thankyou` hook from doing a second
  restore. Net result pre-1.1.4: COD customers permanently lost any
  items they didn't check out. v1.1.4 always defers the restore to
  the next /cart/ visit (via the existing pending-restore queue),
  which runs on `woocommerce_before_cart` priority 5 — long after
  WC's empty_cart() has happened — so the race is sidestepped.
* **[High] Guest customers now also get their unselected items
  restored after a partial checkout.** Pre-1.1.4 the pending-restore
  queue was user-meta-only and silently dropped guest restores.
  Added a parallel WC-session storage key
  (`Helpers::SESSION_KEY_PENDING`) so guests are handled the same way
  as logged-in customers. The login-migration handler in
  `Partial::on_login()` now also migrates this session key to
  user-meta if a guest places an order and then logs in before
  visiting /cart/.
* No data migration required. Drop-in upgrade. Abandoned-checkout
  restoration (Scenario B) is unchanged.

= 1.1.3 =
**Comprehensive cart-stability release: 13 race-condition / correctness / UX
fixes across all three widgets, identified by a full plugin audit.**

* **[Critical] Bulk delete in edit mode is now truly sequential.**
  The delete-selected-items loop awaits each AJAX response before firing
  the next instead of relying on a 120 ms timer gap. Pre-1.1.3, two
  parallel deletions could each read the cart before the other had
  committed, both report `vendor_empty: false`, and one of the two items
  could remain in the cart while both were removed visually. Empty
  vendor blocks now reliably disappear after their last product is
  removed.
* **[Critical] Quantity stepper no longer drops cross-row updates.**
  Pre-1.1.3 a single shared debounce timer served every product row, so
  clicking +/- on Row B within 400 ms of Row A would silently cancel
  Row A's pending AJAX. Each row now has its own debounce timer.
* **[High] Variation dropdown actually rolls back on AJAX failure.**
  Pre-1.1.3 the rollback logic stored the NEW value as "previous" right
  before sending the request, so the rollback set the dropdown to the
  same value it already had — the user saw the new selection while the
  cart still contained the old one. Now reads from the template-seeded
  `data-prev-val` attribute instead.
* **[High] Selected-keys race during variation change.**
  When variation change rewrites a cart-item-key, the global selected-
  keys array is now updated optimistically before the request, so any
  other action that fires while the variation request is in flight
  (e.g. clicking + on a different row) no longer sends a stale key the
  server can't resolve.
* **[High] Quantity check in partial checkout.**
  `handle_zymarg_partial_checkout` now calls `has_enough_stock( $qty )`
  in addition to `is_in_stock()`. Pre-1.1.3, a cart line with quantity
  exceeding available stock would pass the partial-checkout pre-flight
  and only fail (silently capped or with a confusing message) at the
  WooCommerce checkout step.
* **[High] `getTotals` and `removeCoupon` no longer swallow network
  errors silently.** Both now show the standard error notice on a `.fail`,
  preventing the user from continuing with stale totals or an
  uncertain coupon state.
* **[Medium] Move-to-cart preserves scroll position across the reload.**
  The reload itself remains for now (full AJAX-based DOM injection is
  planned for v1.2.0), but the user's scroll position is stashed in
  `sessionStorage` before the reload and restored on page load — so
  long cart pages no longer feel like they lose their place.
* **[Medium] Stale row-count flicker after delete is gone.**
  `applyTotals()` excludes rows mid-fadeout (`.zymarg-removing`) when
  computing the "X of Y selected" label, so the count snaps to the
  final value cleanly instead of briefly showing the pre-delete total.
* **[Medium] Cross-tab partial-checkout restore lock window shortened
  from 30 s to 5 s.** The 5 s window still covers the AJAX-to-redirect
  hand-off, but no longer blocks legitimate cart restores in other
  browser tabs that the user may open during normal flow.
* **[Medium] Edit-mode entry now clears the global selected-keys
  array.** Pre-1.1.3 the edit-mode unchecking of all checkboxes did
  not propagate because the checkbox module's edit-mode guard short-
  circuits the cascade — leaving stale keys readable by any code that
  introspected `ZymargCart.getSelectedKeys()` while in edit mode.
* **[Low] `escAttr()` now performs the full HTML-attribute escape set
  (`&`, `"`, `'`, `<`, `>`).** Pre-1.1.3 only added `'` on top of
  `escHtml()`'s basic substitution. Defensive only — no known active
  exploit path, but tightens up dynamic coupon-code rendering.
* **[Low] Back-forward cache (bfcache) restoration now resets ALL
  loading states.** Pre-1.1.3 only re-enabled the checkout button, so
  qty / variation / coupon / save-for-later loading classes could
  remain frozen if the user navigated away mid-AJAX and pressed Back.

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

= 1.2.1 =
Bug fixes for v1.2.0: desktop unit price now sits in its own column
between the title and the variation/qty stepper (was incorrectly
under the title in v1.2.0). The mobile "Have a coupon?" button now
actually opens its form (was a silent no-op in v1.2.0 because the
JS handler didn't know how to find the form from inside the new
mobile actions row). Drop-in upgrade — no data migration.

= 1.2.0 =
Cart-body widget redesign + new Product Price element. Adds a new
prominent unit-price display under the product title (desktop) and
under the product image (mobile). Mobile layout reorganised into a
3-column / 5-row grid for better readability and tap targets. The
old "× qty" breakdown line under the subtotal is removed (replaced
by the new Product Price). Drop-in upgrade — no data migration,
existing widget configurations remain compatible.

= 1.1.4 =
Critical fix for partial checkout with Cash-on-Delivery and other
instant-status payment gateways: unselected cart items were being
permanently lost after order completion. They are now reliably
restored on the next /cart/ visit. Guest customers are also covered
for the first time. Drop-in upgrade — no data migration.

= 1.1.3 =
Major cart-stability release. Fixes 13 race-condition / correctness /
UX bugs across all three widgets — including the bulk-delete race that
left empty vendor blocks visible, the silent dropping of quantity
updates across rows, and the silently-broken variation rollback. No
data migration required, no breaking changes — drop-in upgrade.

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
