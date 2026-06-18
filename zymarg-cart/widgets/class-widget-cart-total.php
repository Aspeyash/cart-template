<?php
/**
 * Elementor Widget — ZYMARG Cart Total (Widget 3).
 *
 * Renders the cart total section with two parts:
 *
 * Part A — Breakdown panel (hidden by default, slides up on click):
 *   Subtotal · Discount · Shipping · Tax (SST) · Grand Total
 *
 * Part B — Action bar (always visible):
 *   [ Master checkbox + "n of n selected" ] [ Grand Total ] [ Checkout → ]
 *
 * The sliding Part A is driven by zymarg-cart-breakdown.js.
 * The master checkbox interconnects with Widget 2 via zymarg-cart-checkbox.js.
 * The checkout button triggers Solution 1 partial checkout via zymarg-cart-ajax.js.
 *
 * @package ZymargCart
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Zymarg_Widget_Cart_Total extends \Elementor\Widget_Base {

	// =========================================================================
	// Widget identity
	// =========================================================================

	public function get_name(): string    { return 'zymarg-cart-total'; }
	public function get_title(): string   { return __( 'Cart Total', 'zymarg-cart' ); }
	public function get_icon(): string    { return 'eicon-price-table'; }
	public function get_categories(): array { return [ 'zymarg-cart' ]; }
	public function get_keywords(): array {
		return [ 'zymarg', 'cart', 'total', 'checkout', 'subtotal', 'woocommerce' ];
	}
	public function get_script_depends(): array {
		return [
			'zymarg-cart',
			'zymarg-cart-checkbox',
			'zymarg-cart-ajax',
			'zymarg-cart-breakdown',
		];
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

		$this->add_control( 'order_summary_text', [
			'label'   => __( 'Order Summary Label', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Order Summary', 'zymarg-cart' ),
		] );

		$this->add_control( 'tax_label_text', [
			'label'   => __( 'Tax Line Label', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Tax (6% SST)', 'zymarg-cart' ),
		] );

		$this->add_control( 'grand_total_label_text', [
			'label'   => __( 'Grand Total Label', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Grand Total', 'zymarg-cart' ),
		] );

		$this->add_control( 'checkout_btn_text', [
			'label'   => __( 'Checkout Button Text', 'zymarg-cart' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Proceed to Checkout', 'zymarg-cart' ),
			'separator' => 'before',
		] );

		$this->end_controls_section();

		// ── Section: Visibility ───────────────────────────────────────────────
		$this->start_controls_section( 'section_visibility', [
			'label' => __( 'Visibility', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		// Subtotal bar
		$this->add_control( 'heading_bar', [
			'label' => __( 'Subtotal Bar', 'zymarg-cart' ),
			'type'  => \Elementor\Controls_Manager::HEADING,
		] );
		foreach ( [
			'show_subtotal_bar'  => __( 'Subtotal Bar', 'zymarg-cart' ),
			'show_bar_arrow'     => __( 'Arrow Icon', 'zymarg-cart' ),
		] as $k => $l ) {
			$this->add_control( $k, [
				'label' => $l, 'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'zymarg-cart' ), 'label_off' => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes', 'default' => 'yes',
			] );
		}

		// Breakdown panel (Part A)
		$this->add_control( 'heading_panel', [
			'label'     => __( 'Breakdown Panel (Part A)', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );
		foreach ( [
			'show_subtotal_line'     => __( 'Subtotal Line', 'zymarg-cart' ),
			'show_discount_line'     => __( 'Discount Line', 'zymarg-cart' ),
			'show_shipping_line'     => __( 'Shipping Line', 'zymarg-cart' ),
			'show_shipping_per_vendor'=> __( 'Per-Vendor Shipping Breakdown', 'zymarg-cart' ),
			'show_tax_line'          => __( 'Tax Line', 'zymarg-cart' ),
			'show_panel_grand'       => __( 'Grand Total in Panel', 'zymarg-cart' ),
			'show_divider'           => __( 'Divider Line', 'zymarg-cart' ),
		] as $k => $l ) {
			$this->add_control( $k, [
				'label' => $l, 'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'zymarg-cart' ), 'label_off' => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes', 'default' => 'yes',
			] );
		}

		// Action bar (Part B)
		$this->add_control( 'heading_action', [
			'label'     => __( 'Action Bar (Part B)', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
		] );
		foreach ( [
			'show_master_cb'       => __( 'Master Checkbox', 'zymarg-cart' ),
			'show_select_label'    => __( '"Select All" Label', 'zymarg-cart' ),
			'show_selected_count'  => __( 'Selected Count (n of n)', 'zymarg-cart' ),
			'show_action_grand'    => __( 'Grand Total Amount', 'zymarg-cart' ),
			'show_action_grand_label' => __( 'Grand Total Label', 'zymarg-cart' ),
			'show_checkout_btn'    => __( 'Checkout Button', 'zymarg-cart' ),
			'show_checkout_icon'   => __( 'Checkout Button Icon', 'zymarg-cart' ),
		] as $k => $l ) {
			$this->add_control( $k, [
				'label' => $l, 'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'zymarg-cart' ), 'label_off' => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes', 'default' => 'yes',
			] );
		}

		$this->end_controls_section();

		// ── Section: Behavior ─────────────────────────────────────────────────
		$this->start_controls_section( 'section_behavior', [
			'label' => __( 'Behavior', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'animate_breakdown', [
			'label'        => __( 'Slide Animation', 'zymarg-cart' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'On', 'zymarg-cart' ),
			'label_off'    => __( 'Off', 'zymarg-cart' ),
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'animation_speed', [
			'label'      => __( 'Animation Speed (ms)', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'range'      => [ 'px' => [ 'min' => 100, 'max' => 1000, 'step' => 50 ] ],
			'default'    => [ 'size' => 300 ],
			'condition'  => [ 'animate_breakdown' => 'yes' ],
		] );

		$this->add_control( 'breakdown_open_default', [
			'label'        => __( 'Open by Default', 'zymarg-cart' ),
			'description'  => __( 'Show the order summary panel expanded on page load.', 'zymarg-cart' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'label_on'     => __( 'Open', 'zymarg-cart' ),
			'label_off'    => __( 'Closed', 'zymarg-cart' ),
			'return_value' => 'yes',
			'default'      => '',
			'separator'    => 'before',
		] );

		$this->add_control( 'checkout_btn_loading', [
			'label'        => __( 'Button Loading State', 'zymarg-cart' ),
			'description'  => __( 'Show spinner on checkout button while processing.', 'zymarg-cart' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();

		// ─────────────────────────────────────────────────────────────────────
		// STYLE TAB
		// ─────────────────────────────────────────────────────────────────────

		// ── Section: Subtotal Bar ─────────────────────────────────────────────
		$this->start_controls_section( 'section_style_bar', [
			'label'     => __( 'Subtotal Bar', 'zymarg-cart' ),
			'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
			'condition' => [ 'show_subtotal_bar' => 'yes' ],
		] );

		$this->add_control( 'bar_bg_color', [
			'label'     => __( 'Background Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#eaedff',
			'selectors' => [ '{{WRAPPER}} .zymarg-subtotal-bar' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_control( 'bar_text_color', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#534152',
			'selectors' => [ '{{WRAPPER}} .zymarg-subtotal-bar' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'bar_amount_color', [
			'label'     => __( 'Amount Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-subtotal-bar-amount' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'bar_arrow_color', [
			'label'     => __( 'Arrow Icon Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-breakdown-arrow' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_bar_arrow' => 'yes' ],
		] );

		$this->add_responsive_control( 'bar_arrow_size', [
			'label'          => __( 'Arrow Size', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::SLIDER,
			'size_units'     => [ 'px' ],
			'range'          => [ 'px' => [ 'min' => 10, 'max' => 30 ] ],
			'default'        => [ 'size' => 14 ],
			'tablet_default' => [ 'size' => 14 ],
			'mobile_default' => [ 'size' => 13 ],
			'selectors'      => [ '{{WRAPPER}} .zymarg-breakdown-arrow' => 'font-size: {{SIZE}}{{UNIT}};' ],
			'condition'      => [ 'show_bar_arrow' => 'yes' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'bar_typography',
			'selector' => '{{WRAPPER}} .zymarg-subtotal-bar',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [
					'default'        => [ 'size' => 12, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 12, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 11, 'unit' => 'px' ],
				],
			],
		] );

		$this->add_responsive_control( 'bar_padding', [
			'label'          => __( 'Padding', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units'     => [ 'px', 'em' ],
			'default'        => [ 'top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px', 'isLinked' => false ],
			'tablet_default' => [ 'top' => '9', 'right' => '16', 'bottom' => '9', 'left' => '16', 'unit' => 'px' ],
			'mobile_default' => [ 'top' => '8', 'right' => '14', 'bottom' => '8', 'left' => '14', 'unit' => 'px' ],
			'selectors'      => [ '{{WRAPPER}} .zymarg-subtotal-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_control( 'bar_border_color', [
			'label'     => __( 'Bottom Border Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#d8bfd3',
			'selectors' => [ '{{WRAPPER}} .zymarg-subtotal-bar' => 'border-bottom-color: {{VALUE}};' ],
		] );

		$this->end_controls_section();

		// ── Section: Breakdown Panel (Part A) ─────────────────────────────────
		$this->start_controls_section( 'section_style_panel', [
			'label' => __( 'Breakdown Panel (Part A)', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'panel_bg_color', [
			'label'     => __( 'Background Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .zymarg-breakdown-panel' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'panel_padding', [
			'label'          => __( 'Padding', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units'     => [ 'px', 'em' ],
			'default'        => [ 'top' => '14', 'right' => '20', 'bottom' => '14', 'left' => '20', 'unit' => 'px', 'isLinked' => false ],
			'tablet_default' => [ 'top' => '12', 'right' => '16', 'bottom' => '12', 'left' => '16', 'unit' => 'px' ],
			'mobile_default' => [ 'top' => '12', 'right' => '14', 'bottom' => '12', 'left' => '14', 'unit' => 'px' ],
			// Scoped to .breakdown-open so user-set padding only applies when the
			// panel is expanded — otherwise it overrides the collapsed-state CSS
			// and leaves a partly-visible row when the bar is closed (v1.1.2).
			'selectors'      => [ '{{WRAPPER}} .zymarg-breakdown-panel.breakdown-open .zymarg-breakdown-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		$this->add_responsive_control( 'breakdown_row_gap', [
			'label'          => __( 'Row Gap', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::SLIDER,
			'size_units'     => [ 'px' ],
			'range'          => [ 'px' => [ 'min' => 0, 'max' => 20 ] ],
			'default'        => [ 'size' => 5 ],
			'tablet_default' => [ 'size' => 5 ],
			'mobile_default' => [ 'size' => 4 ],
			// Same .breakdown-open scoping as the padding control above (v1.1.2).
			'selectors'      => [ '{{WRAPPER}} .zymarg-breakdown-panel.breakdown-open .zymarg-breakdown-inner' => 'gap: {{SIZE}}{{UNIT}};' ],
		] );

		$this->add_control( 'row_label_color', [
			'label'     => __( 'Label Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#534152',
			'selectors' => [ '{{WRAPPER}} .zymarg-total-label' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'row_label_typography',
			'selector' => '{{WRAPPER}} .zymarg-total-label',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [
					'default'        => [ 'size' => 12, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 12, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 11, 'unit' => 'px' ],
				],
			],
		] );

		$this->add_control( 'row_value_color', [
			'label'     => __( 'Value Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#131b2e',
			'selectors' => [ '{{WRAPPER}} .zymarg-total-value' => 'color: {{VALUE}};' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'row_value_typography',
			'selector' => '{{WRAPPER}} .zymarg-total-value',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [ 'default' => [ 'size' => 12, 'unit' => 'px' ] ],
			],
		] );

		$this->add_control( 'discount_value_color', [
			'label'     => __( 'Discount Value Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#3b6d11',
			'selectors' => [ '{{WRAPPER}} .zymarg-total-row--discount .zymarg-total-value' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_discount_line' => 'yes' ],
		] );

		$this->add_control( 'divider_color', [
			'label'     => __( 'Divider Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#d8bfd3',
			'selectors' => [ '{{WRAPPER}} .zymarg-breakdown-divider' => 'border-top-color: {{VALUE}};' ],
			'condition' => [ 'show_divider' => 'yes' ],
			'separator' => 'before',
		] );

		$this->add_control( 'divider_thickness', [
			'label'      => __( 'Divider Thickness', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::SLIDER,
			'size_units' => [ 'px' ],
			'range'      => [ 'px' => [ 'min' => 0.5, 'max' => 4 ] ],
			'default'    => [ 'size' => 0.5 ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-breakdown-divider' => 'border-top-width: {{SIZE}}{{UNIT}};' ],
			'condition'  => [ 'show_divider' => 'yes' ],
		] );

		$this->add_control( 'panel_grand_label_color', [
			'label'     => __( 'Grand Total Label Color (Panel)', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#131b2e',
			'selectors' => [ '{{WRAPPER}} .zymarg-total-row--grand .zymarg-total-label' => 'color: {{VALUE}}; font-weight: 500;' ],
			'condition' => [ 'show_panel_grand' => 'yes' ],
			'separator' => 'before',
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'panel_grand_label_typography',
			'selector' => '{{WRAPPER}} .zymarg-total-row--grand .zymarg-total-label',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [
					'default'        => [ 'size' => 13, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 13, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 13, 'unit' => 'px' ],
				],
				'font_weight'=> [ 'default' => '500' ],
			],
			'condition' => [ 'show_panel_grand' => 'yes' ],
		] );

		$this->add_control( 'panel_grand_value_color', [
			'label'     => __( 'Grand Total Value Color (Panel)', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-total-row--grand .zymarg-total-value' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_panel_grand' => 'yes' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'panel_grand_value_typography',
			'selector' => '{{WRAPPER}} .zymarg-total-row--grand .zymarg-total-value',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [
					'default'        => [ 'size' => 15, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 15, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 14, 'unit' => 'px' ],
				],
				'font_weight'=> [ 'default' => '500' ],
			],
			'condition' => [ 'show_panel_grand' => 'yes' ],
		] );

		$this->end_controls_section();

		// ── Section: Action Bar (Part B) ──────────────────────────────────────
		$this->start_controls_section( 'section_style_action', [
			'label' => __( 'Action Bar (Part B)', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'action_bar_bg', [
			'label'     => __( 'Background Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#faf8ff',
			'selectors' => [ '{{WRAPPER}} .zymarg-action-bar' => 'background-color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'action_bar_padding', [
			'label'          => __( 'Padding', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units'     => [ 'px', 'em' ],
			'default'        => [ 'top' => '14', 'right' => '20', 'bottom' => '14', 'left' => '20', 'unit' => 'px', 'isLinked' => false ],
			'tablet_default' => [ 'top' => '12', 'right' => '16', 'bottom' => '12', 'left' => '16', 'unit' => 'px' ],
			'mobile_default' => [ 'top' => '12', 'right' => '14', 'bottom' => '12', 'left' => '14', 'unit' => 'px' ],
			'selectors'      => [ '{{WRAPPER}} .zymarg-action-bar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
		] );

		// Master checkbox
		$this->add_responsive_control( 'master_cb_size', [
			'label'          => __( 'Checkbox Size', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::SLIDER,
			'size_units'     => [ 'px' ],
			'range'          => [ 'px' => [ 'min' => 12, 'max' => 32 ] ],
			'default'        => [ 'size' => 16 ],
			'tablet_default' => [ 'size' => 16 ],
			'mobile_default' => [ 'size' => 16 ],
			'selectors'      => [ '{{WRAPPER}} .zymarg-master-cb' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ],
			'condition'      => [ 'show_master_cb' => 'yes' ],
		] );

		$this->add_control( 'master_cb_color', [
			'label'     => __( 'Checkbox Accent Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-master-cb' => 'accent-color: {{VALUE}};' ],
			'condition' => [ 'show_master_cb' => 'yes' ],
		] );

		$this->add_control( 'select_label_color', [
			'label'     => __( 'Selected Count Label Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#534152',
			'selectors' => [ '{{WRAPPER}} .zymarg-selected-label' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_select_label' => 'yes' ],
			'separator' => 'before',
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'select_label_typography',
			'selector' => '{{WRAPPER}} .zymarg-selected-label',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [
					'default'        => [ 'size' => 12, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 12, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 11, 'unit' => 'px' ],
				],
			],
			'condition' => [ 'show_select_label' => 'yes' ],
		] );

		// Grand total in action bar
		$this->add_control( 'action_grand_label_color', [
			'label'     => __( 'Grand Total Label Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#857183',
			'selectors' => [ '{{WRAPPER}} .zymarg-action-grand-label' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_action_grand_label' => 'yes' ],
			'separator' => 'before',
		] );

		$this->add_control( 'action_grand_amount_color', [
			'label'     => __( 'Grand Total Amount Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-action-grand-total' => 'color: {{VALUE}};' ],
			'condition' => [ 'show_action_grand' => 'yes' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'action_grand_typography',
			'selector' => '{{WRAPPER}} .zymarg-action-grand-total',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [
					'default'        => [ 'size' => 18, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 17, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 16, 'unit' => 'px' ],
				],
				'font_weight'=> [ 'default' => '500' ],
			],
			'condition' => [ 'show_action_grand' => 'yes' ],
		] );

		// Checkout button
		$this->add_control( 'heading_checkout_btn', [
			'label'     => __( 'Checkout Button', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::HEADING,
			'separator' => 'before',
			'condition' => [ 'show_checkout_btn' => 'yes' ],
		] );

		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), [
			'name'     => 'checkout_btn_typography',
			'selector' => '{{WRAPPER}} .zymarg-checkout-btn',
			'fields_options' => [
				'typography' => [ 'default' => 'yes' ],
				'font_size'  => [
					'default'        => [ 'size' => 13, 'unit' => 'px' ],
					'tablet_default' => [ 'size' => 13, 'unit' => 'px' ],
					'mobile_default' => [ 'size' => 12, 'unit' => 'px' ],
				],
				'font_weight'=> [ 'default' => '500' ],
			],
			'condition' => [ 'show_checkout_btn' => 'yes' ],
		] );

		$this->start_controls_tabs( 'tabs_checkout_btn', [
			'condition' => [ 'show_checkout_btn' => 'yes' ],
		] );

		$this->start_controls_tab( 'tab_checkout_normal', [ 'label' => __( 'Normal', 'zymarg-cart' ) ] );
		$this->add_control( 'checkout_btn_bg', [
			'label'     => __( 'Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#9500a5',
			'selectors' => [ '{{WRAPPER}} .zymarg-checkout-btn' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'checkout_btn_text_color', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#ffd6fb',
			'selectors' => [ '{{WRAPPER}} .zymarg-checkout-btn' => 'color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();

		$this->start_controls_tab( 'tab_checkout_hover', [ 'label' => __( 'Hover', 'zymarg-cart' ) ] );
		$this->add_control( 'checkout_btn_bg_hover', [
			'label'     => __( 'Background', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#bd00d1',
			'selectors' => [ '{{WRAPPER}} .zymarg-checkout-btn:hover' => 'background-color: {{VALUE}};' ],
		] );
		$this->add_control( 'checkout_btn_text_hover', [
			'label'     => __( 'Text Color', 'zymarg-cart' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#ffffff',
			'selectors' => [ '{{WRAPPER}} .zymarg-checkout-btn:hover' => 'color: {{VALUE}};' ],
		] );
		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control( 'checkout_btn_border_radius', [
			'label'      => __( 'Border Radius', 'zymarg-cart' ),
			'type'       => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units' => [ 'px', '%' ],
			'default'    => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px', 'isLinked' => true ],
			'selectors'  => [ '{{WRAPPER}} .zymarg-checkout-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			'separator'  => 'before',
			'condition'  => [ 'show_checkout_btn' => 'yes' ],
		] );

		$this->add_responsive_control( 'checkout_btn_padding', [
			'label'          => __( 'Padding', 'zymarg-cart' ),
			'type'           => \Elementor\Controls_Manager::DIMENSIONS,
			'size_units'     => [ 'px', 'em' ],
			'default'        => [ 'top' => '10', 'right' => '22', 'bottom' => '10', 'left' => '22', 'unit' => 'px', 'isLinked' => false ],
			'tablet_default' => [ 'top' => '9', 'right' => '18', 'bottom' => '9', 'left' => '18', 'unit' => 'px' ],
			'mobile_default' => [ 'top' => '9', 'right' => '16', 'bottom' => '9', 'left' => '16', 'unit' => 'px' ],
			'selectors'      => [ '{{WRAPPER}} .zymarg-checkout-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
			'condition'      => [ 'show_checkout_btn' => 'yes' ],
		] );

		$this->end_controls_section();

		// ─────────────────────────────────────────────────────────────────────
		// SECTION: Icons (v1.3.0)
		// ─────────────────────────────────────────────────────────────────────
		// Per-icon color + responsive size controls. The Order Summary toggle
		// arrow keeps its existing dedicated controls (in the section that
		// targets .zymarg-breakdown-arrow); only the new inline-SVG icons
		// added in v1.3.0 are exposed here.
		$this->start_controls_section( 'section_style_icons', [
			'label' => __( 'Icons', 'zymarg-cart' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_icon_style_controls( 'icon_coupon_remove', __( 'Coupon Remove (X)', 'zymarg-cart' ), '.zymarg-applied-coupons .zymarg-icon-x',   '#534152' );
		$this->add_icon_style_controls( 'icon_checkout_lock', __( 'Checkout Lock', 'zymarg-cart' ),    '.zymarg-checkout-btn .zymarg-icon-lock',   '#ffffff' );
		$this->add_icon_style_controls( 'icon_checkout_arrow', __( 'Checkout Arrow', 'zymarg-cart' ),  '.zymarg-checkout-btn .zymarg-btn-arrow',   '#ffffff' );

		$this->end_controls_section();
	}

	// =========================================================================
	// Helper: add color + responsive size controls for a single icon role (v1.3.0)
	// =========================================================================

	private function add_icon_style_controls( string $key, string $label, string $selector, string $default_color = '#534152' ): void {
		$this->add_control( $key . '_color', [
			/* translators: %s: icon role name. */
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

		// Get live cart totals (all items selected on server-side render).
		$totals = Zymarg_Cart_Helpers::is_cart_available()
			? Zymarg_Cart_Dokan::get_totals_for_selected( [] )
			: [];

		$item_count = Zymarg_Cart_Helpers::is_cart_available()
			? (int) WC()->cart->get_cart_contents_count()
			: 0;

		include ZYMARG_CART_PATH . 'templates/cart-total.php';
	}

	protected function content_template(): void {
		?>
		<#
		var btnText = settings.checkout_btn_text || 'Proceed to Checkout';
		var gtLabel = settings.grand_total_label_text || 'Grand Total';
		#>
		<div class="zymarg-cart-total">
			<# if ( 'yes' === settings.show_subtotal_bar ) { #>
			<div class="zymarg-subtotal-bar">
				<span><?php esc_html_e( 'Order Summary', 'zymarg-cart' ); ?></span>
				<# if ( 'yes' === settings.show_bar_arrow ) { #>
					<span class="zymarg-icon zymarg-icon-chevron-up zymarg-breakdown-arrow" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="m6 15 6-6 6 6"/></svg></span>
				<# } #>
			</div>
			<# } #>
			<div class="zymarg-breakdown-panel">
				<div class="zymarg-breakdown-inner" style="padding:12px 20px">
					<div style="color:#857183;font-size:12px;"><?php esc_html_e( 'Order breakdown will appear here.', 'zymarg-cart' ); ?></div>
				</div>
			</div>
			<div class="zymarg-action-bar">
				<# if ( 'yes' === settings.show_master_cb ) { #>
					<input type="checkbox" class="zymarg-master-cb" checked>
				<# } #>
				<# if ( 'yes' === settings.show_action_grand ) { #>
					<span class="zymarg-action-grand-total">RM 0.00</span>
				<# } #>
				<# if ( 'yes' === settings.show_checkout_btn ) { #>
					<button class="zymarg-checkout-btn">{{{ btnText }}}</button>
				<# } #>
			</div>
		</div>
		<?php
	}
}
