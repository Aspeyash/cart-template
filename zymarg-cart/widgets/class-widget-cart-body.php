<?php
/**
 * Elementor Widget — ZYMARG Cart Body (Widget 2).
 *
 * Renders the main cart body: products grouped by Dokan vendor, each vendor
 * block containing a header row and one product row per item.
 *
 * Product row columns (desktop):
 *   Checkbox | Image | Title + SKU + Warning | Variation + Qty | Subtotal | Coupon
 *
 * Mobile: stacked card layout per product (CSS handles the switch at breakpoint).
 *
 * Also renders:
 *   - Empty cart state (illustration + message + Continue Shopping button).
 *   - Save-for-Later section below all vendor groups.
 *
 * @package ZymargCart
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Zymarg_Widget_Cart_Body extends \Elementor\Widget_Base {

	// =========================================================================
	// Widget identity
	// =========================================================================

	public function get_name(): string    { return 'zymarg-cart-body'; }
	public function get_title(): string   { return __( 'Cart Body', 'zymarg-cart' ); }
	public function get_icon(): string    { return 'eicon-product-add-to-cart'; }
	public function get_categories(): array { return [ 'zymarg-cart' ]; }
	public function get_keywords(): array {
		return [ 'zymarg', 'cart', 'body', 'products', 'vendor', 'woocommerce', 'dokan' ];
	}
	public function get_script_depends(): array {
		return [ 'zymarg-cart', 'zymarg-cart-checkbox', 'zymarg-cart-ajax',
		         'zymarg-cart-edit-mode', 'zymarg-cart-breakdown' ];
	}
	public function get_style_depends(): array {
		return [ 'zymarg-cart', 'zymarg-cart-mobile' ];
	}

	// =========================================================================
	// Controls
	// =========================================================================

	protected function register_controls(): void {

		// ─────────────────────────────────────────────────────────────────────
		// CONTENT TAB
		// ─────────────────────────────────────────────────────────────────────

		// ── Section: Content ─────────────────────────────────────────────────
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Content', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'have_coupon_text', [
			'label'   => __( '"Have a coupon?" Text', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Have a coupon?', 'zymarg-cart' ),
		] );

		$this->add_control( 'save_later_label', [
			'label'   => __( 'Save for Later Label', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Save for later', 'zymarg-cart' ),
		] );

		$this->add_control( 'move_to_cart_label', [
			'label'   => __( 'Move to Cart Label', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Move to Cart', 'zymarg-cart' ),
		] );

		$this->add_control( 'saved_section_title', [
			'label'   => __( 'Saved Section Title', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Saved for Later', 'zymarg-cart' ),
		] );

		$this->add_control( 'empty_cart_message', [
			'label'   => __( 'Empty Cart Message', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Your cart is empty.', 'zymarg-cart' ),
			'separator' => 'before',
		] );

		$this->add_control( 'continue_shopping_text', [
			'label'   => __( 'Continue Shopping Button Text', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Continue Shopping', 'zymarg-cart' ),
		] );

		$this->add_control( 'continue_shopping_url', [
			'label'   => __( 'Continue Shopping URL', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::URL,
			'default' => [ 'url' => home_url( '/shop/' ) ],
			'dynamic' => [ 'active' => true ],
		] );

		$this->end_controls_section();

		// ── Section: Visibility ───────────────────────────────────────────────
		$this->start_controls_section( 'section_visibility', [
			'label' => __( 'Visibility', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		// Vendor row
		$this->add_control( 'heading_vendor_row_vis', [
			'label' => __( 'Vendor Row', 'zymarg-cart' ),
			'type'  => \Elementor\Controls_Manager::HEADING,
		] );
		foreach ( [
			'show_vendor_checkbox' => __( 'Vendor Checkbox', 'zymarg-cart' ),
			'show_vendor_link'     => __( 'Vendor Store Link', 'zymarg-cart' ),
			'show_vendor_arrow'    => __( 'Vendor Arrow Icon', 'zymarg-cart' ),
		] as $key => $label ) {
			$this->add_control( $key, [
				'label'        => $label,
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			] );
		}

		// ── Vendor identity icon mode (v1.3.1) ────────────────────────────
		// Lets the merchant choose between the per-vendor profile photo
		// (current Dokan default — `vendor_profile`) and a single static
		// icon shared across all vendors (`static_icon`). The static icon
		// is picked from the inline-SVG library shipped in v1.3.0.
		$this->add_control( 'vendor_icon_type', [
			'label'   => __( 'Vendor Identity Icon', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'vendor_profile',
			'options' => [
				'vendor_profile' => __( 'Vendor Profile Photo + Name', 'zymarg-cart' ),
				'static_icon'    => __( 'Static Icon + Vendor Name', 'zymarg-cart' ),
			],
			'description' => __( 'Choose what appears next to the vendor name. The "Vendor Profile Photo" mode shows the Dokan vendor avatar (per-vendor). The "Static Icon" mode shows the same icon for every vendor.', 'zymarg-cart' ),
		] );

		$this->add_control( 'vendor_static_icon', [
			'label'     => __( 'Static Icon', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'building-store',
			'options'   => [
				'building-store' => __( 'Storefront', 'zymarg-cart' ),
				'shopping-bag'   => __( 'Shopping Bag', 'zymarg-cart' ),
				'shopping-cart'  => __( 'Shopping Cart', 'zymarg-cart' ),
				'briefcase'      => __( 'Briefcase', 'zymarg-cart' ),
				'user'           => __( 'User / Profile', 'zymarg-cart' ),
				'tag'            => __( 'Tag', 'zymarg-cart' ),
				'bookmark'       => __( 'Bookmark', 'zymarg-cart' ),
			],
			'condition' => [ 'vendor_icon_type' => 'static_icon' ],
		] );

		$this->add_control( 'vendor_static_icon_color', [
			'label'     => __( 'Static Icon Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5', // ZYMARG primary.
			'selectors' => [ '{{WRAPPER}} .zymarg-vendor-static-icon' => 'color: {{VALUE}};' ],
			'condition' => [ 'vendor_icon_type' => 'static_icon' ],
		] );

		$this->add_responsive_control( 'vendor_static_icon_size', [
			'label'          => __( 'Static Icon Size', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::SLIDER,
			'size_units'     => [ 'px', 'em' ],
			'range'          => [ 'px' => [ 'min' => 12, 'max' => 40 ] ],
			'default'        => [ 'size' => 18, 'unit' => 'px' ],
			'tablet_default' => [ 'size' => 16, 'unit' => 'px' ],
			'mobile_default' => [ 'size' => 16, 'unit' => 'px' ],
			'selectors'      => [ '{{WRAPPER}} .zymarg-vendor-static-icon' => 'font-size: {{SIZE}}{{UNIT}};' ],
			'condition'      => [ 'vendor_icon_type' => 'static_icon' ],
		] );

		// Product row
		$this->add_control( 'heading_product_row_vis', [
			'label'     => __( 'Product Row', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );
		foreach ( [
			'show_product_checkbox'  => __( 'Product Checkbox', 'zymarg-cart' ),
			'show_product_image'     => __( 'Product Image', 'zymarg-cart' ),
			'show_product_sku'       => __( 'SKU', 'zymarg-cart' ),
			'show_stock_warning'     => __( 'Stock Warning', 'zymarg-cart' ),
			'show_variation_dropdown'=> __( 'Variation Dropdown', 'zymarg-cart' ),
			'show_qty_stepper'       => __( 'Quantity Stepper', 'zymarg-cart' ),
			'show_product_price'     => __( 'Product Price', 'zymarg-cart' ),
			'show_coupon_field'      => __( 'Coupon Field', 'zymarg-cart' ),
			'show_table_headers'     => __( 'Table Column Headers', 'zymarg-cart' ),
		] as $key => $label ) {
			$this->add_control( $key, [
				'label'        => $label,
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			] );
		}

		// Vendor subtotal
		$this->add_control( 'show_vendor_subtotal', [
			'label'        => __( 'Vendor Subtotal Row', 'zymarg-cart' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Show', 'zymarg-cart' ),
			'label_off'    => __( 'Hide', 'zymarg-cart' ),
			'return_value' => 'yes',
			'default'      => 'yes',
			'separator'    => 'before',
		] );

		$this->end_controls_section();

		// ── Section: Save for Later ───────────────────────────────────────────
		$this->start_controls_section( 'section_save_later', [
			'label' => __( 'Save for Later', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'save_later_enabled', [
			'label'        => __( 'Enable Save for Later', 'zymarg-cart' ),
			'description'  => __( 'Master toggle — disabling this hides the entire feature.', 'zymarg-cart' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Enabled', 'zymarg-cart' ),
			'label_off'    => __( 'Disabled', 'zymarg-cart' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		foreach ( [
			'show_save_later_btn'   => __( 'Save for Later Button', 'zymarg-cart' ),
			'show_save_later_icon'  => __( 'Button Icon', 'zymarg-cart' ),
			'show_move_to_cart_btn' => __( 'Move to Cart Button', 'zymarg-cart' ),
			'show_remove_saved_btn' => __( 'Remove from Saved Button', 'zymarg-cart' ),
			'show_saved_section'    => __( 'Saved Items Section', 'zymarg-cart' ),
			'show_saved_below_cart' => __( 'Show Below Cart (vs separate page)', 'zymarg-cart' ),
			'show_price_changed'    => __( 'Price Changed Badge', 'zymarg-cart' ),
			'show_saved_count_badge'=> __( 'Saved Count Badge', 'zymarg-cart' ),
		] as $key => $label ) {
			$this->add_control( $key, [
				'label'        => $label,
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [ 'save_later_enabled' => 'yes' ],
			] );
		}

		$this->add_control( 'max_saved_items', [
			'label'     => __( 'Max Saved Items', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 50,
			'min'       => 1,
			'max'       => 200,
			'condition' => [ 'save_later_enabled' => 'yes' ],
		] );

		$this->add_control( 'saved_items_page_url', [
			'label'     => __( 'Saved Items Page URL', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::URL,
			'condition' => [
				'save_later_enabled'   => 'yes',
				'show_saved_below_cart' => '',
			],
		] );

		$this->end_controls_section();

		// ── Section: Behavior ─────────────────────────────────────────────────
		$this->start_controls_section( 'section_behavior', [
			'label' => __( 'Behavior', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		foreach ( [
			'variation_live_update' => __( 'Variation Change → Live Price Update', 'zymarg-cart' ),
			'quantity_live_update'  => __( 'Quantity Change → Live Price Update', 'zymarg-cart' ),
			'alternate_row_color'   => __( 'Alternate Row Color', 'zymarg-cart' ),
			'row_hover_highlight'   => __( 'Row Hover Highlight', 'zymarg-cart' ),
			'show_empty_cart_illus' => __( 'Empty Cart Illustration', 'zymarg-cart' ),
		] as $key => $label ) {
			$this->add_control( $key, [
				'label'        => $label,
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			] );
		}

		$this->add_control( 'mobile_breakpoint', [
			'label'      => __( 'Mobile Breakpoint (px)', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::NUMBER,
			'default'    => 480,
			'min'        => 320,
			'max'        => 1200,
			'separator'  => 'before',
			'description' => __( 'Width below which the cart switches to its mobile layout. v1.3.0 default is 480px (was 768px). Note: this control sets the data attribute used by JS responsive logic; the CSS @media queries are hard-coded to 480px in zymarg-cart-mobile.css.', 'zymarg-cart' ),
		] );

		$this->end_controls_section();

		// ─────────────────────────────────────────────────────────────────────
		// STYLE TAB
		// ─────────────────────────────────────────────────────────────────────

		// ── Section: Vendor Row Style ─────────────────────────────────────────
		$this->start_controls_section( 'section_style_vendor_row', [
			'label' => __( 'Vendor Row', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'vendor_row_bg', [
			'label'     => __( 'Background Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#eaedff',
			'selectors' => [ '{{WRAPPER}} .zymarg-vendor-row' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'vendor_name_color', [
			'label'     => __( 'Store Name Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-vendor-name' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'vendor_name_typography',
			'selector' => '{{WRAPPER}} .zymarg-vendor-name',
			'fields_options' => [
				'typography'  => [ 'default' => 'yes' ],
				'font_size'   => [ 'default' => [ 'size' => 13, 'unit' => 'px' ] ],
				'font_weight' => [ 'default' => '500' ],
			],
		] );

		$this->add_responsive_control( 'vendor_row_padding', [
			'label'      => __( 'Padding', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'default'    => [ 'top' => '10', 'right' => '16', 'bottom' => '10', 'left' => '16', 'unit' => 'px', 'isLinked' => false ],
			'tablet_default' => [ 'top' => '9', 'right' => '14', 'bottom' => '9', 'left' => '14', 'unit' => 'px' ],
			'mobile_default' => [ 'top' => '8', 'right' => '12', 'bottom' => '8', 'left' => '12', 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-vendor-row' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'vendor_block_border_radius', [
			'label'      => __( 'Vendor Block Border Radius', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 32 ] ],
			'default'    => [ 'size' => 12 ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-vendor-block' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'vendor_block_gap', [
			'label'      => __( 'Gap Between Vendor Groups', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'default'        => [ 'size' => 10 ],
			'tablet_default' => [ 'size' => 8 ],
			'mobile_default' => [ 'size' => 8 ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-cart-body' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Product Table ────────────────────────────────────────────
		$this->start_controls_section( 'section_style_product_table', [
			'label' => __( 'Product Table', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'table_row_bg', [
			'label'     => __( 'Row Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .zymarg-product-row' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'table_row_bg_alt', [
			'label'     => __( 'Alternate Row Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#faf8ff',
			'selectors' => [ '{{WRAPPER}} .zymarg-product-row:nth-child(even)' => 'background-color: {{VALUE}};' ],
			'condition' => [ 'alternate_row_color' => 'yes' ],
		] );

		$this->add_control( 'table_row_border_color', [
			'label'     => __( 'Row Divider Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#eaedff',
			'selectors' => [ '{{WRAPPER}} .zymarg-product-row' => 'border-bottom-color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'table_row_padding', [
			'label'      => __( 'Row Padding', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px' ],
			'default'    => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px', 'isLinked' => true ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-product-row' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'table_header_bg', [
			'label'     => __( 'Table Header Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#faf8ff',
			'selectors' => [ '{{WRAPPER}} .zymarg-product-table thead th' => 'background-color: {{VALUE}};' ],
			'condition' => [ 'show_table_headers' => 'yes' ],
		] );

		$this->add_control( 'table_header_color', [
			'label'     => __( 'Table Header Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#857183',
			'selectors' => [ '{{WRAPPER}} .zymarg-product-table thead th' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_table_headers' => 'yes' ],
		] );

		$this->end_controls_section();

		// ── Section: Product Image ────────────────────────────────────────────
		$this->start_controls_section( 'section_style_image', [
			'label'     => __( 'Product Image', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_product_image' => 'yes' ],
		] );

		$this->add_responsive_control( 'product_image_width', [
			'label'      => __( 'Width', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 30, 'max' => 150 ] ],
			'default'        => [ 'size' => 54 ],
			'tablet_default' => [ 'size' => 50 ],
			'mobile_default' => [ 'size' => 54 ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-product-img-wrap' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'product_image_radius', [
			'label'      => __( 'Border Radius', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px', '%' ],
			'range'      => [ 'px' => [ 'min' => 0, 'max' => 50 ] ],
			'default'    => [ 'size' => 8 ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-product-img-wrap' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'product_image_bg', [
			'label'     => __( 'Placeholder Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#eaedff',
			'selectors' => [ '{{WRAPPER}} .zymarg-product-img-wrap' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Product Title ────────────────────────────────────────────
		$this->start_controls_section( 'section_style_title', [
			'label' => __( 'Product Title & SKU', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'product_title_color', [
			'label'     => __( 'Title Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#131b2e',
			'selectors' => [ '{{WRAPPER}} .zymarg-product-title' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'product_title_typography',
			'selector' => '{{WRAPPER}} .zymarg-product-title',
			'fields_options' => [
				'typography'  => [ 'default' => 'yes' ],
				'font_size'   => [
					'default'        => [ 'size' => 13, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 13, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 12, 'unit' => 'px' ],
				],
				'font_weight' => [ 'default' => '500' ],
			],
		] );

		$this->add_control( 'sku_color', [
			'label'     => __( 'SKU Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#857183',
			'selectors' => [ '{{WRAPPER}} .zymarg-product-sku' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_product_sku' => 'yes' ],
		] );

		$this->end_controls_section();

		// ── Section: Variation & Qty ──────────────────────────────────────────
		$this->start_controls_section( 'section_style_var_qty', [
			'label' => __( 'Variation & Quantity', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'variation_select_border_color', [
			'label'     => __( 'Dropdown Border Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#d8bfd3',
			'selectors' => [ '{{WRAPPER}} .zymarg-variation-select' => 'border-color: {{VALUE}};' ],
		] );

		$this->add_control( 'variation_select_radius', [
			'label'      => __( 'Dropdown Border Radius', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'default'    => [ 'size' => 6 ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-variation-select' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'qty_btn_bg', [
			'label'     => __( 'Qty Button Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#eaedff',
			'selectors' => [ '{{WRAPPER}} .zymarg-qty-btn' => 'background-color: {{VALUE}};' ],
			'separator' => 'before',
		] );

		$this->add_control( 'qty_btn_color', [
			'label'     => __( 'Qty Button Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-qty-btn' => 'color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'qty_stepper_size', [
			'label'      => __( 'Stepper Button Size', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 20, 'max' => 50 ] ],
			'default'        => [ 'size' => 26 ],
			'tablet_default' => [ 'size' => 24 ],
			'mobile_default' => [ 'size' => 26 ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-qty-btn, {{WRAPPER}} .zymarg-qty-value' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Subtotal ─────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_subtotal', [
			'label' => __( 'Subtotal Column', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'subtotal_color', [
			'label'     => __( 'Subtotal Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#131b2e',
			'selectors' => [ '{{WRAPPER}} .zymarg-col-subtotal .zymarg-subtotal-amount' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'subtotal_typography',
			'selector' => '{{WRAPPER}} .zymarg-col-subtotal .zymarg-subtotal-amount',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [ 'default' => [ 'size' => 13, 'unit' => 'px' ] ],
				'font_weight'=> [ 'default' => '500' ],
			],
		] );

		$this->add_control( 'unit_price_color', [
			'label'     => __( 'Unit Price Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#857183',
			/* v1.2.0: was bound to .zymarg-unit-price (the old "× qty"
			   breakdown line that was removed in the v1.2.0 redesign).
			   Kept here so existing saved page settings don't error in
			   the editor — but it now targets nothing (silent no-op).
			   The new Product Price section below replaces it. */
			'selectors' => [ '{{WRAPPER}} .zymarg-unit-price' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_unit_price' => 'yes' ],
		] );

		$this->end_controls_section();

		// ── Section: Product Price (v1.2.0) ───────────────────────────────────
		// Styles the new unit-price element shown under the title (desktop)
		// and under the image (mobile). Both share the .zymarg-product-price
		// class so a single set of controls applies to both.
		$this->start_controls_section( 'section_style_product_price', [
			'label'     => __( 'Product Price', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_product_price' => 'yes' ],
		] );

		$this->add_control( 'product_price_color', [
			'label'     => __( 'Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5', // ZYMARG primary.
			'selectors' => [ '{{WRAPPER}} .zymarg-product-price' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'product_price_typography',
			'selector' => '{{WRAPPER}} .zymarg-product-price',
			'fields_options' => [
				'typography'  => [ 'default' => 'yes' ],
				'font_size'   => [ 'default' => [ 'size' => 14, 'unit' => 'px' ] ],
				'font_weight' => [ 'default' => '600' ],
			],
		] );

		$this->add_responsive_control( 'product_price_margin', [
			'label'          => __( 'Margin', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units'     => [ 'px', 'em' ],
			'default'        => [ 'top' => '2', 'right' => '0', 'bottom' => '4', 'left' => '0', 'unit' => 'px', 'isLinked' => false ],
			'tablet_default' => [ 'top' => '2', 'right' => '0', 'bottom' => '4', 'left' => '0', 'unit' => 'px' ],
			'mobile_default' => [ 'top' => '4', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px' ],
			'selectors'      => [ '{{WRAPPER}} .zymarg-product-price' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Coupon ───────────────────────────────────────────────────
		$this->start_controls_section( 'section_style_coupon', [
			'label'     => __( 'Coupon Field', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_coupon_field' => 'yes' ],
		] );

		$this->add_control( 'coupon_apply_bg', [
			'label'     => __( 'Apply Button Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => 'transparent',
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-apply' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'coupon_apply_color', [
			'label'     => __( 'Apply Button Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-apply' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'coupon_success_color', [
			'label'     => __( 'Success Message Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#3b6d11',
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-feedback.zymarg-coupon-success' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'coupon_error_color', [
			'label'     => __( 'Error Message Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#a32d2d',
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-feedback.zymarg-coupon-error' => 'color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Stock Warning ────────────────────────────────────────────
		$this->start_controls_section( 'section_style_stock', [
			'label'     => __( 'Stock Warning', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_stock_warning' => 'yes' ],
		] );

		$this->add_control( 'stock_warning_color', [
			'label'     => __( 'Low Stock Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#854f0b',
			'selectors' => [ '{{WRAPPER}} .zymarg-stock-warning.zymarg-low-stock' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'out_of_stock_color', [
			'label'     => __( 'Out of Stock Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#a32d2d',
			'selectors' => [ '{{WRAPPER}} .zymarg-stock-warning.zymarg-out-of-stock' => 'color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Vendor Subtotal Row ──────────────────────────────────────
		$this->start_controls_section( 'section_style_vendor_subtotal', [
			'label'     => __( 'Vendor Subtotal Row', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_vendor_subtotal' => 'yes' ],
		] );

		$this->add_control( 'vendor_subtotal_bg', [
			'label'     => __( 'Background Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#faf8ff',
			'selectors' => [ '{{WRAPPER}} .zymarg-vendor-subtotal-row' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'vendor_subtotal_label_color', [
			'label'     => __( 'Label Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#534152',
			'selectors' => [ '{{WRAPPER}} .zymarg-vendor-subtotal-label' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'vendor_subtotal_value_color', [
			'label'     => __( 'Value Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#131b2e',
			'selectors' => [ '{{WRAPPER}} .zymarg-vendor-subtotal-value' => 'color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'vendor_subtotal_padding', [
			'label'      => __( 'Padding', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px' ],
			'default'    => [ 'top' => '8', 'right' => '16', 'bottom' => '8', 'left' => '16', 'unit' => 'px', 'isLinked' => false ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-vendor-subtotal-row' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Save for Later Style ─────────────────────────────────────
		// ── Section: Save for Later Button (full controls) ─────────────────────
		$this->start_controls_section( 'section_style_save_later', [
			'label'     => __( 'Save for Later Button', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'save_later_enabled' => 'yes', 'show_save_later_btn' => 'yes' ],
		] );

		$this->start_controls_tabs( 'save_later_btn_tabs' );

		$this->start_controls_tab( 'save_later_btn_tab_normal', [
			'label' => __( 'Normal', 'zymarg-cart' ),
		] );

		$this->add_control( 'save_later_btn_color', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#534152',
			'selectors' => [ '{{WRAPPER}} .zymarg-save-later-btn' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'save_later_btn_bg', [
			'label'     => __( 'Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-save-later-btn' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab( 'save_later_btn_tab_hover', [
			'label' => __( 'Hover', 'zymarg-cart' ),
		] );

		$this->add_control( 'save_later_btn_color_hover', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-save-later-btn:hover' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'save_later_btn_bg_hover', [
			'label'     => __( 'Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-save-later-btn:hover' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'save_later_typography',
			'selector' => '{{WRAPPER}} .zymarg-save-later-btn',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [ 'default' => [ 'size' => 11, 'unit' => 'px' ] ],
			],
		] );

		$this->add_control( 'save_later_btn_border_color', [
			'label'     => __( 'Border Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-save-later-btn' => 'border-color: {{VALUE}};' ],
			'separator' => 'before',
		] );

		$this->add_responsive_control( 'save_later_btn_border_radius', [
			'label'      => __( 'Border Radius', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-save-later-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'save_later_btn_padding', [
			'label'      => __( 'Padding', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-save-later-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Remove Saved Button ──────────────────────────────────────
		$this->start_controls_section( 'section_style_remove_saved_btn', [
			'label'     => __( 'Remove Saved Button', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'save_later_enabled' => 'yes', 'show_remove_saved_btn' => 'yes' ],
		] );

		$this->start_controls_tabs( 'remove_saved_btn_tabs' );

		$this->start_controls_tab( 'remove_saved_btn_tab_normal', [
			'label' => __( 'Normal', 'zymarg-cart' ),
		] );

		$this->add_control( 'remove_saved_btn_color', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#534152',
			'selectors' => [ '{{WRAPPER}} .zymarg-remove-saved-btn, {{WRAPPER}} .zymarg-remove-saved-label' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'remove_saved_btn_bg', [
			'label'     => __( 'Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-remove-saved-btn' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab( 'remove_saved_btn_tab_hover', [
			'label' => __( 'Hover', 'zymarg-cart' ),
		] );

		$this->add_control( 'remove_saved_btn_color_hover', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-remove-saved-btn:hover, {{WRAPPER}} .zymarg-remove-saved-btn:hover .zymarg-remove-saved-label' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'remove_saved_btn_bg_hover', [
			'label'     => __( 'Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-remove-saved-btn:hover' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'remove_saved_btn_typography',
			'selector' => '{{WRAPPER}} .zymarg-remove-saved-btn',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [ 'default' => [ 'size' => 11, 'unit' => 'px' ] ],
			],
		] );

		$this->add_control( 'remove_saved_btn_border_color', [
			'label'     => __( 'Border Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-remove-saved-btn' => 'border-color: {{VALUE}};' ],
			'separator' => 'before',
		] );

		$this->add_responsive_control( 'remove_saved_btn_border_radius', [
			'label'      => __( 'Border Radius', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-remove-saved-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'remove_saved_btn_padding', [
			'label'      => __( 'Padding', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-remove-saved-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Have a Coupon Button ─────────────────────────────────────
		$this->start_controls_section( 'section_style_have_coupon_btn', [
			'label'     => __( 'Have a Coupon? Button', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_coupon_field' => 'yes' ],
		] );

		$this->start_controls_tabs( 'have_coupon_btn_tabs' );

		$this->start_controls_tab( 'have_coupon_btn_tab_normal', [
			'label' => __( 'Normal', 'zymarg-cart' ),
		] );

		$this->add_control( 'coupon_toggle_color', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-toggle' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'have_coupon_btn_bg', [
			'label'     => __( 'Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-toggle' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->start_controls_tab( 'have_coupon_btn_tab_hover', [
			'label' => __( 'Hover', 'zymarg-cart' ),
		] );

		$this->add_control( 'have_coupon_btn_color_hover', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-toggle:hover' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'have_coupon_btn_bg_hover', [
			'label'     => __( 'Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-toggle:hover' => 'background-color: {{VALUE}};' ],
		] );

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'have_coupon_btn_typography',
			'selector' => '{{WRAPPER}} .zymarg-coupon-toggle',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [ 'default' => [ 'size' => 12, 'unit' => 'px' ] ],
			],
		] );

		$this->add_control( 'have_coupon_btn_border_color', [
			'label'     => __( 'Border Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'selectors' => [ '{{WRAPPER}} .zymarg-coupon-toggle' => 'border-color: {{VALUE}};' ],
			'separator' => 'before',
		] );

		$this->add_responsive_control( 'have_coupon_btn_border_radius', [
			'label'      => __( 'Border Radius', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-coupon-toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'have_coupon_btn_padding', [
			'label'      => __( 'Padding', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', 'em' ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-coupon-toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Empty Cart ───────────────────────────────────────────────
		$this->start_controls_section( 'section_style_empty_cart', [
			'label' => __( 'Empty Cart', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'empty_message_color', [
			'label'     => __( 'Message Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#131b2e',
			'selectors' => [ '{{WRAPPER}} .zymarg-empty-message' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'empty_message_typography',
			'selector' => '{{WRAPPER}} .zymarg-empty-message',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [
					'default'        => [ 'size' => 15, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 14, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 14, 'unit' => 'px' ],
				],
			],
		] );

		$this->add_control( 'continue_btn_bg', [
			'label'     => __( 'Button Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-continue-shopping' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'continue_btn_color', [
			'label'     => __( 'Button Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#ffd6fb',
			'selectors' => [ '{{WRAPPER}} .zymarg-continue-shopping' => 'color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'empty_cart_padding', [
			'label'      => __( 'Section Padding', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px' ],
			'default'    => [ 'top' => '48', 'right' => '20', 'bottom' => '48', 'left' => '20', 'unit' => 'px', 'isLinked' => false ],
			'tablet_default' => [ 'top' => '40', 'right' => '16', 'bottom' => '40', 'left' => '16', 'unit' => 'px' ],
			'mobile_default' => [ 'top' => '32', 'right' => '14', 'bottom' => '32', 'left' => '14', 'unit' => 'px' ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-cart-empty' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->end_controls_section();

		// ─────────────────────────────────────────────────────────────────────
		// SECTION: Icons (v1.3.0)
		// ─────────────────────────────────────────────────────────────────────
		// Per-icon color and responsive-size controls for every icon rendered
		// by this widget. Icons are inline SVG (rendered via
		// Zymarg_Cart_Helpers::icon()) so font-size on the wrapper span scales
		// the SVG via `width: 1em; height: 1em`, and `currentColor` flows
		// through to the SVG strokes — all controllable from the editor.
		$this->start_controls_section( 'section_style_icons', [
			'label' => __( 'Icons', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_icon_style_controls( 'icon_save_later',  __( 'Save for Later', 'zymarg-cart' ),    '.zymarg-save-later-btn .zymarg-icon-bookmark', '#9500a5' );
		$this->add_icon_style_controls( 'icon_coupon_tag',  __( 'Have a Coupon (Tag)', 'zymarg-cart' ),'.zymarg-coupon-toggle .zymarg-icon-tag',       '#9500a5' );
		$this->add_icon_style_controls( 'icon_coupon_disc', __( 'Applied Coupon Badge', 'zymarg-cart' ),'.zymarg-applied-coupon-badge .zymarg-icon-discount-2', '#9500a5' );
		$this->add_icon_style_controls( 'icon_stock_warn',  __( 'Stock Warning', 'zymarg-cart' ),    '.zymarg-stock-warning .zymarg-icon-alert-triangle', '#d32f2f' );
		$this->add_icon_style_controls( 'icon_qty_minus',   __( 'Quantity Minus', 'zymarg-cart' ),   '.zymarg-qty-btn .zymarg-icon-minus',           '#534152' );
		$this->add_icon_style_controls( 'icon_qty_plus',    __( 'Quantity Plus', 'zymarg-cart' ),    '.zymarg-qty-btn .zymarg-icon-plus',            '#534152' );
		$this->add_icon_style_controls( 'icon_move_cart',   __( 'Move to Cart', 'zymarg-cart' ),     '.zymarg-icon-shopping-cart-plus',              '#9500a5' );
		$this->add_icon_style_controls( 'icon_remove_x',    __( 'Remove (X)', 'zymarg-cart' ),       '.zymarg-icon-x',                               '#534152' );
		$this->add_icon_style_controls( 'icon_price_chg',   __( 'Price Changed', 'zymarg-cart' ),    '.zymarg-icon-trending-up',                     '#d32f2f' );
		$this->add_icon_style_controls( 'icon_vendor_link', __( 'Vendor Link Arrow', 'zymarg-cart' ), '.zymarg-vendor-arrow',                         '#9500a5' );
		$this->add_icon_style_controls( 'icon_empty_back',  __( 'Continue Shopping Arrow', 'zymarg-cart' ), '.zymarg-cart-empty .zymarg-icon-arrow-left', '#ffffff' );

		$this->end_controls_section();
	}

	// =========================================================================
	// Helper: add color + responsive size controls for a single icon role
	// =========================================================================
	//
	// Used by the v1.3.0 "Icons" style section to expose per-icon styling in
	// the Elementor editor. Each call adds two controls:
	//   - {key}_color : color picker bound to `color: VALUE;` on the selector
	//   - {key}_size  : responsive slider bound to `font-size: VALUE;`
	//                   (font-size scales the inline SVG because the wrapper
	//                   span sets the SVG's width/height to 1em)
	//
	// @param string $key            Control key prefix (e.g. 'icon_save_later').
	// @param string $label          Human label shown in the editor.
	// @param string $selector       CSS selector RELATIVE to {{WRAPPER}}.
	// @param string $default_color  Initial color value.

	private function add_icon_style_controls( string $key, string $label, string $selector, string $default_color = '#534152' ): void {
		$this->add_control( $key . '_color', [
			/* translators: %s: icon role name (e.g. "Save for Later"). */
			'label'     => sprintf( __( '%s — Color', 'zymarg-cart' ), $label ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => $default_color,
			'selectors' => [ '{{WRAPPER}} ' . $selector => 'color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( $key . '_size', [
			/* translators: %s: icon role name. */
			'label'          => sprintf( __( '%s — Size', 'zymarg-cart' ), $label ),
			'type'           => \Elementor\Controls_Manager::SLIDER,
			'size_units'     => [ 'px', 'em' ],
			'range'          => [ 'px' => [ 'min' => 8, 'max' => 32 ] ],
			'default'        => [ 'size' => 14, 'unit' => 'px' ],
			'tablet_default' => [ 'size' => 13, 'unit' => 'px' ],
			'mobile_default' => [ 'size' => 12, 'unit' => 'px' ],
			'selectors'      => [ '{{WRAPPER}} ' . $selector => 'font-size: {{SIZE}}{{UNIT}};' ],
			'separator'      => 'after',
		] );
	}

	// =========================================================================
	// Render
	// =========================================================================

	protected function render(): void {
		$settings = $this->get_settings_for_display();

		// Apply max saved items from widget setting.
		if ( ! empty( $settings['max_saved_items'] ) ) {
			add_filter(
				'zymarg_cart_max_saved_items',
				static fn() => (int) $settings['max_saved_items'],
				20
			);
		}

		// Get grouped cart data (all items selected by default on server render).
		$grouped_vendors = Zymarg_Cart_Dokan::get_cart_grouped_by_vendor( [] );
		$is_empty        = empty( $grouped_vendors );

		// Get saved-for-later items.
		$user_id     = get_current_user_id();
		$saved_items = $user_id > 0
			? Zymarg_Cart_Usermeta::get_saved_items( $user_id )
			: Zymarg_Cart_Session::get_saved_items();

		// Applied coupons for display in product rows.
		$applied_coupons = Zymarg_Cart_Dokan::get_applied_coupons();

		include ZYMARG_CART_PATH . 'templates/cart-body.php';
	}

	protected function content_template(): void {
		?>
		<div class="zymarg-cart-body">
			<div class="zymarg-vendor-block">
				<div class="zymarg-vendor-row">
					<span class="zymarg-vendor-cb-wrap"><input type="checkbox" class="zymarg-vendor-cb"></span>
					<span class="zymarg-vendor-name">Sample Store</span>
				</div>
				<div style="padding:12px 16px; color:#857183; font-size:12px;">
					<?php esc_html_e( 'Product rows will appear here on the live page.', 'zymarg-cart' ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}
