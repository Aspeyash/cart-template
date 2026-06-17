<?php
/**
 * Shared utility helpers for ZYMARG Cart.
 *
 * This class provides static utility methods used across every other class in
 * the plugin. No WordPress hooks are registered here — this is pure utility.
 *
 * Responsibilities:
 * - Item key generation (deterministic hash from product + variation data).
 * - Input sanitization.
 * - Nonce verification.
 * - Standardised JSON response helpers (success + error).
 * - Price formatting.
 * - Product display data retrieval.
 * - WC session / cart availability guards.
 * - Max saved-items limit accessor.
 * - Debug logger (WooCommerce logger wrapper).
 *
 * @package ZymargCart
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Zymarg_Cart_Helpers {

	// -------------------------------------------------------------------------
	// Constants.
	// -------------------------------------------------------------------------

	/** WC session key for the Save-for-Later list (guest users). */
	public const SESSION_KEY_SAVED = '_zymarg_saved_items';

	/** User-meta key for the Save-for-Later list (logged-in users). */
	public const USERMETA_KEY_SAVED = '_zymarg_saved_items';

	/** WC session key for the partial-checkout cart backup (guest users). */
	public const SESSION_KEY_BACKUP = '_zymarg_cart_backup';

	/** User-meta key for the partial-checkout cart backup (logged-in users). */
	public const USERMETA_KEY_BACKUP = '_zymarg_cart_backup';

	/** Default maximum number of items a customer can save for later. */
	public const DEFAULT_MAX_SAVED = 50;

	/** Nonce action used for all AJAX calls. */
	public const NONCE_ACTION = 'zymarg_cart_nonce';

	// -------------------------------------------------------------------------
	// Prevent instantiation — static class only.
	// -------------------------------------------------------------------------

	private function __construct() {}

	// -------------------------------------------------------------------------
	// Item key generation.
	// -------------------------------------------------------------------------

	/**
	 * Generates a deterministic, collision-resistant item key from the
	 * combination of product ID, variation ID, and variation attributes.
	 *
	 * Two calls with identical arguments always produce the same key, so the
	 * same physical product+variation will not be saved twice.
	 *
	 * @param int                  $product_id   WooCommerce product ID.
	 * @param int                  $variation_id WooCommerce variation ID (0 for simple products).
	 * @param array<string, mixed> $variation    Variation attributes, e.g. ['attribute_pa_size' => 'L'].
	 *
	 * @return string 32-character lowercase hex MD5 hash.
	 */
	public static function generate_item_key(
		int $product_id,
		int $variation_id = 0,
		array $variation = []
	): string {
		ksort( $variation );

		return md5(
			$product_id . '|' .
			$variation_id . '|' .
			( ! empty( $variation ) ? serialize( $variation ) : '' ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		);
	}

	// -------------------------------------------------------------------------
	// Sanitization.
	// -------------------------------------------------------------------------

	/**
	 * Strips anything that is not a valid MD5 hex character from an item key.
	 * Prevents directory-traversal or injection attacks when keys are used as
	 * array indices or passed through AJAX.
	 *
	 * @param string $key Raw key from user input or URL.
	 * @return string Sanitized key (empty string if completely invalid).
	 */
	public static function sanitize_item_key( string $key ): string {
		return (string) preg_replace( '/[^a-f0-9]/', '', strtolower( $key ) );
	}

	/**
	 * Sanitizes a product or variation ID received from an AJAX request.
	 *
	 * @param mixed $id Raw value from $_POST / $_GET.
	 * @return int Positive integer or 0 if invalid.
	 */
	public static function sanitize_product_id( mixed $id ): int {
		$int = (int) $id;
		return $int > 0 ? $int : 0;
	}

	/**
	 * Sanitizes a quantity value.
	 * Returns 1 as fallback to avoid zero or negative quantities.
	 *
	 * @param mixed $qty Raw value.
	 * @return int Quantity ≥ 1.
	 */
	public static function sanitize_quantity( mixed $qty ): int {
		$int = (int) $qty;
		return $int > 0 ? $int : 1;
	}

	// -------------------------------------------------------------------------
	// Nonce verification.
	// -------------------------------------------------------------------------

	/**
	 * Verifies the ZYMARG cart AJAX nonce.
	 * Returns false (does not die) so callers can send a proper JSON error.
	 *
	 * @param string $nonce  The nonce string to verify.
	 * @param string $action Nonce action. Defaults to the plugin nonce action.
	 *
	 * @return bool True if valid, false if invalid or expired.
	 */
	public static function verify_nonce(
		string $nonce,
		string $action = self::NONCE_ACTION
	): bool {
		return (bool) wp_verify_nonce( $nonce, $action );
	}

	// -------------------------------------------------------------------------
	// Standardised AJAX responses.
	// -------------------------------------------------------------------------

	/**
	 * Sends a JSON success response and terminates execution.
	 *
	 * A fresh nonce is included in every response so the JS layer can update
	 * its stored nonce and prevent 403s on long sessions.
	 *
	 * @param mixed  $data    Optional payload to include under the 'data' key.
	 * @param string $message Optional human-readable message.
	 *
	 * @return never
	 */
	public static function send_success( mixed $data = null, string $message = '' ): void {
		$response = [
			'success' => true,
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
		];

		if ( $data !== null ) {
			$response['data'] = $data;
		}

		if ( $message !== '' ) {
			$response['message'] = $message;
		}

		wp_send_json( $response, 200 );
	}

	/**
	 * Sends a JSON error response and terminates execution.
	 *
	 * @param string $message Human-readable error description.
	 * @param int    $code    HTTP status code (default 400).
	 * @param mixed  $data    Optional debug payload (only included when WP_DEBUG is on).
	 *
	 * @return never
	 */
	public static function send_error(
		string $message,
		int $code = 400,
		mixed $data = null
	): void {
		$response = [
			'success' => false,
			'message' => $message,
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
		];

		if ( $data !== null && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$response['debug'] = $data;
		}

		wp_send_json( $response, $code );
	}

	// -------------------------------------------------------------------------
	// Price formatting.
	// -------------------------------------------------------------------------

	/**
	 * Formats a numeric price using WooCommerce's configured currency symbol,
	 * decimal separator, and thousand separator.
	 *
	 * @param float $price The price to format.
	 * @return string HTML-formatted price string (e.g. "RM&nbsp;89.00").
	 */
	public static function format_price( float $price ): string {
		return wc_price( $price );
	}

	/**
	 * Returns a raw formatted price string without HTML — useful for
	 * JSON responses consumed by JavaScript.
	 *
	 * @param float $price The price to format.
	 * @return string Plain-text price string (e.g. "89.00").
	 */
	public static function format_price_raw( float $price ): string {
		return number_format(
			$price,
			wc_get_price_decimals(),
			wc_get_price_decimal_separator(),
			wc_get_price_thousand_separator()
		);
	}

	// -------------------------------------------------------------------------
	// Product display data.
	// -------------------------------------------------------------------------

	/**
	 * Retrieves all data needed to render a saved-item card in the frontend.
	 *
	 * For variable products the variation-level product is used for price and
	 * image; the parent product provides the title and permalink.
	 *
	 * @param int $product_id   Parent product ID.
	 * @param int $variation_id Variation ID (0 for simple / non-variable products).
	 *
	 * @return array<string, mixed> Associative array of display data,
	 *                              or an empty array if the product does not exist.
	 */
	public static function get_product_display_data(
		int $product_id,
		int $variation_id = 0
	): array {
		$parent  = wc_get_product( $product_id );
		$product = $variation_id > 0 ? wc_get_product( $variation_id ) : $parent;

		if ( ! $product instanceof \WC_Product || ! $parent instanceof \WC_Product ) {
			return [];
		}

		// Prefer the variation image; fall back to the parent image.
		$image_id  = $product->get_image_id() ?: $parent->get_image_id();
		$image_url = $image_id
			? (string) wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
			: (string) wc_placeholder_img_src( 'woocommerce_thumbnail' );

		$stock_qty = $product->get_stock_quantity();

		return [
			'product_id'    => $product_id,
			'variation_id'  => $variation_id,
			'title'         => wp_strip_all_tags( $parent->get_name() ),
			'permalink'     => (string) $parent->get_permalink(),
			'image_url'     => $image_url,
			'sku'           => (string) $product->get_sku(),
			'price'         => (float) $product->get_price(),
			'price_html'    => $product->get_price_html(),
			'regular_price' => (float) $product->get_regular_price(),
			'sale_price'    => (float) $product->get_sale_price(),
			'on_sale'       => $product->is_on_sale(),
			'stock_status'  => $product->get_stock_status(),    // 'instock'|'outofstock'|'onbackorder'
			'is_in_stock'   => $product->is_in_stock(),
			'manage_stock'  => $product->managing_stock(),
			'stock_qty'     => is_int( $stock_qty ) ? $stock_qty : null,
			'is_purchasable' => $product->is_purchasable(),
			'type'          => $parent->get_type(),
		];
	}

	/**
	 * Returns variation attribute labels suitable for display beneath the
	 * product title (e.g. "Color: Red, Size: L").
	 *
	 * @param array<string, string> $variation Raw variation data from cart item.
	 * @return string Comma-separated attribute label pairs, HTML-escaped.
	 */
	public static function format_variation_labels( array $variation ): string {
		$labels = [];

		foreach ( $variation as $attr => $value ) {
			if ( '' === $value ) {
				continue;
			}

			// Convert 'attribute_pa_color' → 'Color'.
			$taxonomy = str_replace( 'attribute_', '', $attr );
			$label    = wc_attribute_label( $taxonomy );

			// For term-based attributes, get the term name.
			if ( taxonomy_exists( $taxonomy ) ) {
				$term = get_term_by( 'slug', $value, $taxonomy );
				$value = $term instanceof \WP_Term ? $term->name : $value;
			}

			$labels[] = esc_html( $label ) . ': ' . esc_html( $value );
		}

		return implode( ', ', $labels );
	}

	// -------------------------------------------------------------------------
	// WooCommerce availability guards.
	// -------------------------------------------------------------------------

	/**
	 * Returns the WooCommerce session object if it is properly initialised,
	 * or null otherwise.
	 *
	 * Use this guard instead of accessing WC()->session directly so all
	 * callers handle the null case without repeated null checks.
	 *
	 * @return \WC_Session|\WC_Session_Handler|null
	 */
	public static function get_wc_session(): ?\WC_Session {
		$wc = WC();
		return ( $wc && $wc->session instanceof \WC_Session )
			? $wc->session
			: null;
	}

	/**
	 * Returns true if the WooCommerce cart object is initialised and usable.
	 *
	 * @return bool
	 */
	public static function is_cart_available(): bool {
		$wc = WC();
		return $wc && $wc->cart instanceof \WC_Cart;
	}

	// -------------------------------------------------------------------------
	// Configuration accessors.
	// -------------------------------------------------------------------------

	/**
	 * Returns the maximum number of items a customer is allowed to save for
	 * later. Filterable so site owners can override via theme or child plugin.
	 *
	 * @return int
	 */
	public static function get_max_saved_items(): int {
		return (int) apply_filters( 'zymarg_cart_max_saved_items', self::DEFAULT_MAX_SAVED );
	}

	/**
	 * Returns the partial-checkout backup expiry in seconds.
	 * Defaults to 7200 (2 hours); filterable.
	 *
	 * @return int
	 */
	public static function get_backup_expiry(): int {
		return (int) apply_filters( 'zymarg_cart_session_expiry', 7200 );
	}

	// -------------------------------------------------------------------------
	// Debug logger.
	// -------------------------------------------------------------------------

	/**
	 * Logs a message to the WooCommerce logger under the 'zymarg-cart' source.
	 * Only writes when WP_DEBUG is enabled to avoid log bloat on production.
	 *
	 * @param string $message Human-readable log entry.
	 * @param mixed  $context Optional structured context (serialised as JSON).
	 * @param string $level   Log level: 'debug'|'info'|'warning'|'error'.
	 */
	public static function log(
		string $message,
		mixed $context = null,
		string $level = 'debug'
	): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$logger  = wc_get_logger();
		$wc_ctx  = [ 'source' => 'zymarg-cart' ];
		$entry   = $context !== null
			? $message . ' | ' . wp_json_encode( $context )
			: $message;

		match ( $level ) {
			'error'   => $logger->error( $entry, $wc_ctx ),
			'warning' => $logger->warning( $entry, $wc_ctx ),
			'info'    => $logger->info( $entry, $wc_ctx ),
			default   => $logger->debug( $entry, $wc_ctx ),
		};
	}

	// -------------------------------------------------------------------------
	// Cart item data helpers.
	// -------------------------------------------------------------------------

	/**
	 * Extracts and normalises variation attributes from a WooCommerce cart
	 * item array. Ensures the array is always keyed by attribute slug.
	 *
	 * @param array<string, mixed> $cart_item Raw cart item from WC()->cart->get_cart().
	 * @return array<string, string> Normalised variation array.
	 */
	public static function normalize_variation( array $cart_item ): array {
		$raw = $cart_item['variation'] ?? [];
		return is_array( $raw ) ? $raw : [];
	}

	/**
	 * Builds a canonical saved-item array from raw cart item data.
	 * This is the single source of truth for the item data structure used by
	 * both the session class and the user-meta class.
	 *
	 * @param array<string, mixed> $cart_item   WooCommerce cart item array.
	 * @param string               $cart_item_key WooCommerce cart item key.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_saved_item_from_cart( array $cart_item, string $cart_item_key ): array {
		$product_id   = (int) ( $cart_item['product_id'] ?? 0 );
		$variation_id = (int) ( $cart_item['variation_id'] ?? 0 );
		$quantity     = (int) ( $cart_item['quantity'] ?? 1 );
		$variation    = self::normalize_variation( $cart_item );

		$price_id = $variation_id ?: $product_id;
		$product  = wc_get_product( $price_id );
		$price    = $product instanceof \WC_Product ? (float) $product->get_price() : 0.0;

		return [
			'item_key'         => self::generate_item_key( $product_id, $variation_id, $variation ),
			'cart_item_key'    => $cart_item_key,
			'product_id'       => $product_id,
			'variation_id'     => $variation_id,
			'quantity'         => $quantity,
			'variation'        => $variation,
			'saved_at'         => time(),
			'saved_price'      => $price,
			'current_price'    => $price,
			'price_changed'    => false,
		];
	}

	// -------------------------------------------------------------------------
	// Stock status helpers.
	// -------------------------------------------------------------------------

	/**
	 * Returns a normalised stock status descriptor for a product/variation.
	 * Used by both the usermeta class and the AJAX totals handler.
	 *
	 * @param int $product_id   Product (or variation) ID.
	 * @param int $quantity     Desired quantity (to detect insufficient stock).
	 *
	 * @return array{status: string, is_in_stock: bool, qty: int|null, low_stock: bool}
	 */
	public static function get_stock_info( int $product_id, int $quantity = 1 ): array {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product ) {
			return [
				'status'     => 'unavailable',
				'is_in_stock' => false,
				'qty'         => null,
				'low_stock'   => false,
			];
		}

		$stock_qty  = $product->get_stock_quantity();
		$low_threshold = absint(
			get_option( 'woocommerce_notify_low_stock_amount', 2 )
		);

		return [
			'status'       => $product->get_stock_status(),
			'is_in_stock'  => $product->is_in_stock(),
			'qty'          => is_int( $stock_qty ) ? $stock_qty : null,
			'low_stock'    => (
				$product->managing_stock() &&
				is_int( $stock_qty ) &&
				$stock_qty > 0 &&
				$stock_qty <= $low_threshold
			),
			'insufficient' => (
				$product->managing_stock() &&
				is_int( $stock_qty ) &&
				$stock_qty < $quantity
			),
		];
	}
}
