<?php
/**
 * Core plugin class for ZYMARG Cart.
 *
 * Responsibilities:
 * - Singleton entry point.
 * - Dependency validation (WooCommerce, Elementor Pro, Dokan Pro).
 * - Admin notice system for missing or outdated dependencies.
 * - Elementor widget registration.
 * - Frontend asset enqueue (CSS + JS) — only on pages that contain our widgets.
 * - AJAX action registration for both logged-in and guest users.
 * - WooCommerce HPOS (High Performance Order Storage) compatibility declaration.
 * - Activation and deactivation routines.
 *
 * @package ZymargCart
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Zymarg_Cart {

	// -------------------------------------------------------------------------
	// Singleton.
	// -------------------------------------------------------------------------

	private static ?Zymarg_Cart $instance = null;

	/**
	 * Returns the single instance of the class.
	 * Called by plugins_loaded hook in the bootstrap file.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Prevent direct instantiation from outside. */
	private function __construct() {
		if ( ! $this->check_dependencies() ) {
			$this->register_dependency_notices();
			return;
		}

		$this->load_textdomain();
		$this->load_includes();
		$this->register_hooks();
	}

	/** Prevent cloning of the singleton. */
	private function __clone() {}

	/** Prevent unserialization of the singleton. */
	public function __wakeup(): void {
		throw new \LogicException( 'The Zymarg_Cart singleton cannot be unserialized.' );
	}

	// -------------------------------------------------------------------------
	// Dependency validation.
	// -------------------------------------------------------------------------

	/** Holds a human-readable list of anything that is missing or outdated. */
	private array $missing_dependencies = [];

	/**
	 * Validates that every required plugin is installed, active, and at the
	 * minimum required version.
	 *
	 * @return bool True when all dependencies are satisfied.
	 */
	private function check_dependencies(): bool {
		$this->missing_dependencies = [];

		// ----- WooCommerce -----
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->missing_dependencies[] = sprintf(
				/* translators: %s: Minimum WooCommerce version. */
				__( 'WooCommerce %s or higher (not installed / not active)', 'zymarg-cart' ),
				ZYMARG_CART_MIN_WC
			);
		} elseif (
			defined( 'WC_VERSION' ) &&
			version_compare( WC_VERSION, ZYMARG_CART_MIN_WC, '<' )
		) {
			$this->missing_dependencies[] = sprintf(
				/* translators: 1: Minimum version, 2: Installed version. */
				__( 'WooCommerce %1$s or higher (you have %2$s)', 'zymarg-cart' ),
				ZYMARG_CART_MIN_WC,
				WC_VERSION
			);
		}

		// ----- Elementor (free) -----
		if ( ! did_action( 'elementor/loaded' ) ) {
			$this->missing_dependencies[] = sprintf(
				/* translators: %s: Minimum Elementor version. */
				__( 'Elementor %s or higher (not installed / not active)', 'zymarg-cart' ),
				ZYMARG_CART_MIN_ELEMENTOR
			);
		} elseif (
			defined( 'ELEMENTOR_VERSION' ) &&
			version_compare( ELEMENTOR_VERSION, ZYMARG_CART_MIN_ELEMENTOR, '<' )
		) {
			$this->missing_dependencies[] = sprintf(
				/* translators: 1: Minimum version, 2: Installed version. */
				__( 'Elementor %1$s or higher (you have %2$s)', 'zymarg-cart' ),
				ZYMARG_CART_MIN_ELEMENTOR,
				ELEMENTOR_VERSION
			);
		}

		// ----- Elementor Pro -----
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$this->missing_dependencies[] = __( 'Elementor Pro (not installed / not active)', 'zymarg-cart' );
		}

		// ----- Dokan Pro -----
		$dokan_active = (
			class_exists( 'WeDevs_Dokan' ) ||
			function_exists( 'dokan' ) ||
			defined( 'DOKAN_PRO_PLUGIN_VERSION' )
		);

		if ( ! $dokan_active ) {
			$this->missing_dependencies[] = sprintf(
				/* translators: %s: Minimum Dokan Pro version. */
				__( 'Dokan Pro %s or higher (not installed / not active)', 'zymarg-cart' ),
				ZYMARG_CART_MIN_DOKAN
			);
		}

		return empty( $this->missing_dependencies );
	}

	/**
	 * Registers an admin_notices callback so the dashboard shows a clear
	 * message about what is missing.
	 */
	private function register_dependency_notices(): void {
		add_action( 'admin_notices', [ $this, 'show_dependency_notice' ] );
	}

	/** Renders the admin notice for missing dependencies. */
	public function show_dependency_notice(): void {
		$list = '<ul style="margin:.4em 0 0 1.2em;list-style:disc">';
		foreach ( $this->missing_dependencies as $dep ) {
			$list .= '<li>' . esc_html( $dep ) . '</li>';
		}
		$list .= '</ul>';

		echo '<div class="notice notice-error"><p>' .
			wp_kses_post(
				sprintf(
					/* translators: %s: HTML list of missing plugins/versions. */
					__( '<strong>ZYMARG Cart</strong> cannot run because the following required plugins are missing or outdated: %s', 'zymarg-cart' ),
					$list
				)
			) .
			'</p></div>';
	}

	// -------------------------------------------------------------------------
	// Internationalisation.
	// -------------------------------------------------------------------------

	/** Loads the plugin text domain for translations. */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'zymarg-cart',
			false,
			dirname( ZYMARG_CART_BASENAME ) . '/languages'
		);
	}

	// -------------------------------------------------------------------------
	// Include files.
	// -------------------------------------------------------------------------

	/**
	 * Loads all supporting PHP files.
	 * Files are loaded in dependency order; helpers first, then data-layer,
	 * then integration, then AJAX.
	 */
	private function load_includes(): void {
		$files = [
			// Utilities — no dependencies on other plugin classes.
			'includes/class-zymarg-cart-helpers.php',

			// Data layer.
			'includes/class-zymarg-cart-session.php',
			'includes/class-zymarg-cart-usermeta.php',
			'includes/class-zymarg-cart-merge.php',

			// Checkout logic.
			'includes/class-zymarg-cart-partial.php',

			// Integrations.
			'includes/class-zymarg-cart-dokan.php',

			// AJAX — depends on all of the above.
			'includes/class-zymarg-cart-ajax.php',
		];

		foreach ( $files as $relative_path ) {
			$abs = ZYMARG_CART_PATH . $relative_path;
			if ( file_exists( $abs ) ) {
				require_once $abs;
			}
		}
	}

	// -------------------------------------------------------------------------
	// WordPress hooks.
	// -------------------------------------------------------------------------

	/** Registers all WordPress / WooCommerce / Elementor hooks. */
	private function register_hooks(): void {

		// Elementor widget registration.
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );

		// Register the 'ZYMARG Cart' category so all three widgets appear in
		// their own labelled section in the Elementor widget panel.
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_widget_category' ] );

		// Frontend assets.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );

		// Elementor editor assets.
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_assets' ] );

		// AJAX handlers.
		$this->register_ajax_handlers();

		// Register guest-to-logged-in Save-for-Later merge hooks.
		Zymarg_Cart_Merge::init();

		// Register partial-checkout backup, restore, and expiry hooks.
		// Note: Zymarg_Cart_Partial::init() registers woocommerce_cart_loaded_from_session
		// directly, so we do not add a separate hook for it here.
		Zymarg_Cart_Partial::init();

		// HPOS compatibility declaration (must run before init).
		add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );

		// Admin notice for fresh activation.
		add_action( 'admin_notices', [ $this, 'show_activation_notice' ] );
	}

	// -------------------------------------------------------------------------
	// Elementor widget category.
	// -------------------------------------------------------------------------

	/**
	 * Registers the 'ZYMARG Cart' category in the Elementor widget panel.
	 * All three widgets use this category so they appear together.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_widget_category( \Elementor\Elements_Manager $elements_manager ): void {
		$elements_manager->add_category(
			'zymarg-cart',
			[
				'title' => __( 'ZYMARG Cart', 'zymarg-cart' ),
				'icon'  => 'eicon-cart',
			]
		);
	}

	// -------------------------------------------------------------------------
	// Elementor widget registration.
	// -------------------------------------------------------------------------

	/**
	 * Registers all three ZYMARG Cart Elementor widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager The Elementor widgets manager instance.
	 */
	public function register_widgets( \Elementor\Widgets_Manager $widgets_manager ): void {
		$widgets = [
			'widgets/class-widget-cart-header.php' => 'Zymarg_Widget_Cart_Header',
			'widgets/class-widget-cart-body.php'   => 'Zymarg_Widget_Cart_Body',
			'widgets/class-widget-cart-total.php'  => 'Zymarg_Widget_Cart_Total',
		];

		foreach ( $widgets as $file => $class ) {
			$abs = ZYMARG_CART_PATH . $file;
			if ( file_exists( $abs ) ) {
				require_once $abs;
				if ( class_exists( $class ) ) {
					$widgets_manager->register( new $class() );
				}
			}
		}
	}

	// -------------------------------------------------------------------------
	// Asset enqueue.
	// -------------------------------------------------------------------------

	/**
	 * Enqueues all frontend CSS and JS files.
	 * Assets are only loaded on pages that actually contain one of our widgets
	 * to avoid polluting every page on the site.
	 */
	public function enqueue_assets(): void {
		if ( ! $this->page_has_zymarg_widget() ) {
			return;
		}

		// --- Styles ---
		wp_enqueue_style(
			'zymarg-cart',
			ZYMARG_CART_URL . 'assets/css/zymarg-cart.css',
			[],
			ZYMARG_CART_VERSION
		);

		wp_enqueue_style(
			'zymarg-cart-mobile',
			ZYMARG_CART_URL . 'assets/css/zymarg-cart-mobile.css',
			[ 'zymarg-cart' ],
			ZYMARG_CART_VERSION
		);

		// --- Scripts ---
		// Main initializer — boots all modules on DOMContentLoaded.
		wp_enqueue_script(
			'zymarg-cart',
			ZYMARG_CART_URL . 'assets/js/zymarg-cart.js',
			[ 'jquery' ],
			ZYMARG_CART_VERSION,
			true   // Load in footer.
		);

		// Checkbox interconnection logic (Widget 1 ↔ Widget 2 ↔ Widget 3).
		wp_enqueue_script(
			'zymarg-cart-checkbox',
			ZYMARG_CART_URL . 'assets/js/zymarg-cart-checkbox.js',
			[ 'zymarg-cart' ],
			ZYMARG_CART_VERSION,
			true
		);

		// Centralised AJAX wrapper — all server communication goes through here.
		wp_enqueue_script(
			'zymarg-cart-ajax',
			ZYMARG_CART_URL . 'assets/js/zymarg-cart-ajax.js',
			[ 'zymarg-cart' ],
			ZYMARG_CART_VERSION,
			true
		);

		// Part A slide animation (Widget 3 breakdown panel).
		wp_enqueue_script(
			'zymarg-cart-breakdown',
			ZYMARG_CART_URL . 'assets/js/zymarg-cart-breakdown.js',
			[ 'zymarg-cart' ],
			ZYMARG_CART_VERSION,
			true
		);

		// Edit / delete mode toggle (Widget 1).
		wp_enqueue_script(
			'zymarg-cart-edit-mode',
			ZYMARG_CART_URL . 'assets/js/zymarg-cart-edit-mode.js',
			[ 'zymarg-cart' ],
			ZYMARG_CART_VERSION,
			true
		);

		// Pass PHP-side data to all JS modules via the main handle.
		wp_localize_script(
			'zymarg-cart',
			'zymargCartData',
			$this->build_localized_data()
		);
	}

	/** Enqueues assets used only inside the Elementor editor. */
	public function enqueue_editor_assets(): void {
		wp_enqueue_style(
			'zymarg-cart-editor',
			ZYMARG_CART_URL . 'assets/css/zymarg-cart-editor.css',
			[],
			ZYMARG_CART_VERSION
		);
	}

	/**
	 * Builds the data object passed to the frontend via wp_localize_script.
	 *
	 * Contains: AJAX URL, security nonce, cart state, currency settings,
	 * user context, and all translatable strings used by JS modules.
	 *
	 * @return array<string, mixed>
	 */
	private function build_localized_data(): array {
		$cart       = WC()->cart;
		$item_count = $cart instanceof \WC_Cart ? $cart->get_cart_contents_count() : 0;

		return [
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'zymarg_cart_nonce' ),
			'itemCount'     => $item_count,
			'currency'      => get_woocommerce_currency_symbol(),
			'currencyPos'   => get_option( 'woocommerce_currency_pos', 'left' ),
			'thousandSep'   => wc_get_price_thousand_separator(),
			'decimalSep'    => wc_get_price_decimal_separator(),
			'numDecimals'   => wc_get_price_decimals(),
			'isLoggedIn'    => is_user_logged_in(),
			'userId'        => get_current_user_id(),
			'hasBackup'     => class_exists( 'Zymarg_Cart_Partial' ) && Zymarg_Cart_Partial::has_backup(),
			'sessionExpiry' => (int) apply_filters( 'zymarg_cart_session_expiry', 7200 ),
			'debug'         => defined( 'WP_DEBUG' ) && WP_DEBUG,

			// All translatable strings consumed by JS — centralised here so
			// translators only need to look in one place.
			'i18n' => [
				'saveForLater'   => __( 'Save for Later',                    'zymarg-cart' ),
				'saved'          => __( 'Saved',                             'zymarg-cart' ),
				'moveToCart'     => __( 'Move to Cart',                      'zymarg-cart' ),
				'remove'         => __( 'Remove',                            'zymarg-cart' ),
				'delete'         => __( 'Delete',                            'zymarg-cart' ),
				'edit'           => __( 'Edit',                              'zymarg-cart' ),
				'done'           => __( 'Done',                              'zymarg-cart' ),
				'apply'          => __( 'Apply',                             'zymarg-cart' ),
				'haveCoupon'     => __( 'Have a coupon?',                    'zymarg-cart' ),
				'couponApplied'  => __( 'Coupon applied',                    'zymarg-cart' ),
				'couponInvalid'  => __( 'Invalid coupon code',               'zymarg-cart' ),
				'couponExpired'  => __( 'Coupon expired',                    'zymarg-cart' ),
				'outOfStock'     => __( 'Out of stock — remove to proceed',  'zymarg-cart' ),
				/* translators: %d: Number of items remaining in stock. */
				'lowStock'       => __( 'Only %d left',                      'zymarg-cart' ),
				'continueShop'   => __( 'Continue Shopping',                 'zymarg-cart' ),
				'emptyCart'      => __( 'Your cart is empty',                'zymarg-cart' ),
				'emptyMessage'   => __( 'Looks like you haven\'t added anything yet.', 'zymarg-cart' ),
				'checkout'       => __( 'Proceed to Checkout',               'zymarg-cart' ),
				'confirmDelete'  => __( 'Are you sure you want to remove the selected items?', 'zymarg-cart' ),
				'selectAll'      => __( 'Select All',                        'zymarg-cart' ),
				/* translators: 1: Selected count, 2: Total count. */
				'selectedOf'     => __( '%1$d of %2$d selected',             'zymarg-cart' ),
				'calculating'    => __( 'Calculating…',                      'zymarg-cart' ),
				'updating'       => __( 'Updating…',                         'zymarg-cart' ),
				'restoring'      => __( 'Restoring your cart…',              'zymarg-cart' ),
				'calcAtCheckout' => __( 'Calculated at checkout',            'zymarg-cart' ),
				'priceChanged'   => __( 'Price changed',                     'zymarg-cart' ),
				'savedItems'     => __( 'Saved for Later',                   'zymarg-cart' ),
				'noSavedItems'   => __( 'No saved items',                    'zymarg-cart' ),
				'error'          => __( 'Something went wrong. Please try again.', 'zymarg-cart' ),
				'visitStore'     => __( 'Visit store',                       'zymarg-cart' ),
				'subtotal'       => __( 'Subtotal',                          'zymarg-cart' ),
				'discount'       => __( 'Discount',                          'zymarg-cart' ),
				'shipping'       => __( 'Shipping',                          'zymarg-cart' ),
				'tax'            => __( 'Tax (6% SST)',                      'zymarg-cart' ),
				'grandTotal'     => __( 'Grand Total',                       'zymarg-cart' ),
				'orderSummary'   => __( 'Order Summary',                     'zymarg-cart' ),
			],
		];
	}

	// -------------------------------------------------------------------------
	// AJAX handler registration.
	// -------------------------------------------------------------------------

	/**
	 * Registers all AJAX actions for both authenticated and guest users.
	 *
	 * The actual handler methods live in Zymarg_Cart_Ajax.
	 * Every action is registered for wp_ajax_ (logged in) and
	 * wp_ajax_nopriv_ (logged out / guest) so guests can fully use the cart.
	 */
	private function register_ajax_handlers(): void {
		$actions = [
			'zymarg_update_quantity',   // Qty stepper +/−.
			'zymarg_change_variation',  // Variation dropdown change.
			'zymarg_remove_item',       // Remove product in edit mode.
			'zymarg_apply_coupon',      // Apply coupon code.
			'zymarg_remove_coupon',     // Remove applied coupon.
			'zymarg_save_for_later',    // Move product from cart → saved list.
			'zymarg_move_to_cart',      // Move product from saved list → cart.
			'zymarg_remove_saved',      // Remove product from saved list.
			'zymarg_get_totals',        // Recalculate totals for selected items.
			'zymarg_partial_checkout',  // Trigger Solution 1 partial checkout.
			'zymarg_restore_cart',      // Restore backup cart on page load.
		];

		foreach ( $actions as $action ) {
			$handler = [ 'Zymarg_Cart_Ajax', 'handle_' . $action ];

			add_action( 'wp_ajax_' . $action,        $handler );
			add_action( 'wp_ajax_nopriv_' . $action, $handler );
		}
	}

	// -------------------------------------------------------------------------
	// Page detection — load assets only when needed.
	// -------------------------------------------------------------------------

	/**
	 * Determines whether the current frontend page contains at least one of
	 * the three ZYMARG Cart widgets so assets are not loaded globally.
	 *
	 * Falls back to TRUE on the native WooCommerce cart page in case someone
	 * uses the widget without Elementor on that specific page.
	 *
	 * @return bool
	 */
	private function page_has_zymarg_widget(): bool {
		// Always load on the WooCommerce cart page as a safety net.
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return false;
		}

		// Check if the page was built with Elementor.
		if (
			! class_exists( '\Elementor\Plugin' ) ||
			! \Elementor\Plugin::$instance->documents->get( $post_id )?->is_built_with_elementor()
		) {
			return false;
		}

		// Walk the Elementor element tree looking for our widget type prefix.
		$data = \Elementor\Plugin::$instance->documents->get( $post_id )?->get_elements_data();
		if ( empty( $data ) ) {
			return false;
		}

		return $this->elements_contain_zymarg_widget( $data );
	}

	/**
	 * Recursively walks an Elementor elements array to find a widget whose
	 * widgetType starts with 'zymarg-cart-'.
	 *
	 * @param array<int, array<string, mixed>> $elements Elementor elements data.
	 * @return bool
	 */
	private function elements_contain_zymarg_widget( array $elements ): bool {
		foreach ( $elements as $element ) {
			if (
				! empty( $element['widgetType'] ) &&
				str_starts_with( (string) $element['widgetType'], 'zymarg-cart-' )
			) {
				return true;
			}

			if (
				! empty( $element['elements'] ) &&
				is_array( $element['elements'] ) &&
				$this->elements_contain_zymarg_widget( $element['elements'] )
			) {
				return true;
			}
		}

		return false;
	}

	// -------------------------------------------------------------------------
	// WooCommerce HPOS compatibility.
	// -------------------------------------------------------------------------

	/**
	 * Declares compatibility with WooCommerce High Performance Order Storage.
	 * Required for WooCommerce 7.1+ HPOS feature flag.
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				ZYMARG_CART_FILE,
				true
			);
		}
	}

	// -------------------------------------------------------------------------
	// Activation notice.
	// -------------------------------------------------------------------------

	/** Shows a one-time success notice on the first admin page load after activation. */
	public function show_activation_notice(): void {
		if ( ! get_transient( 'zymarg_cart_activated' ) ) {
			return;
		}

		delete_transient( 'zymarg_cart_activated' );

		echo '<div class="notice notice-success is-dismissible"><p>' .
			wp_kses_post(
				sprintf(
					/* translators: %s: Plugin name. */
					__( '<strong>%s</strong> has been activated. Open any page in Elementor and search for "ZYMARG Cart" in the widget panel to get started.', 'zymarg-cart' ),
					'ZYMARG Cart v' . ZYMARG_CART_VERSION
				)
			) .
			'</p></div>';
	}

	// -------------------------------------------------------------------------
	// Activation & deactivation.
	// -------------------------------------------------------------------------

	/**
	 * Runs on plugin activation.
	 *
	 * Validates hard requirements (PHP version, WordPress version) and bails
	 * with a user-friendly message if they are not met so the plugin is never
	 * left in a broken state.
	 */
	public static function activate(): void {
		// PHP version.
		if ( version_compare( PHP_VERSION, ZYMARG_CART_MIN_PHP, '<' ) ) {
			deactivate_plugins( ZYMARG_CART_BASENAME );
			wp_die(
				wp_kses_post(
					sprintf(
						/* translators: 1: Plugin name, 2: Required PHP version, 3: Current PHP version. */
						__( '<strong>%1$s</strong> requires PHP <strong>%2$s</strong> or higher. Your server is running PHP <strong>%3$s</strong>. Please upgrade your PHP version and try again.', 'zymarg-cart' ),
						'ZYMARG Cart',
						ZYMARG_CART_MIN_PHP,
						PHP_VERSION
					)
				),
				esc_html__( 'Plugin activation failed', 'zymarg-cart' ),
				[ 'back_link' => true ]
			);
		}

		// WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, ZYMARG_CART_MIN_WP, '<' ) ) {
			deactivate_plugins( ZYMARG_CART_BASENAME );
			wp_die(
				wp_kses_post(
					sprintf(
						/* translators: 1: Plugin name, 2: Required WP version. */
						__( '<strong>%1$s</strong> requires WordPress <strong>%2$s</strong> or higher. Please update WordPress and try again.', 'zymarg-cart' ),
						'ZYMARG Cart',
						ZYMARG_CART_MIN_WP
					)
				),
				esc_html__( 'Plugin activation failed', 'zymarg-cart' ),
				[ 'back_link' => true ]
			);
		}

		// Set a transient so the activation notice shows once.
		set_transient( 'zymarg_cart_activated', true, MINUTE_IN_SECONDS * 5 );

		// Flush rewrite rules after activation.
		flush_rewrite_rules();
	}

	/**
	 * Runs on plugin deactivation.
	 *
	 * Cleans up scheduled events and transients.
	 * Does NOT remove user data (saved items in user meta) or session data —
	 * those are preserved in case the plugin is reactivated.
	 */
	public static function deactivate(): void {
		// Clear any scheduled background events.
		wp_clear_scheduled_hook( 'zymarg_cart_cleanup_expired_backups' );
		wp_clear_scheduled_hook( 'zymarg_cart_refresh_saved_item_prices' );

		// Remove activation transient.
		delete_transient( 'zymarg_cart_activated' );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}
