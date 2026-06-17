<?php
/**
 * Elementor Widget — ZYMARG Cart Header (Widget 1).
 *
 * Renders the cart page header row:
 *   [ Cart Icon ]  My Cart (n items)          [ Delete ]  [ Edit / Done ]
 *
 * Edit-mode behaviour (managed by zymarg-cart-edit-mode.js):
 *   First click  → adds .zymarg-edit-mode to the cart wrapper;
 *                  Delete button appears, row-level trash icons appear.
 *   Second click → removes .zymarg-edit-mode; Delete button disappears.
 *   Delete       → fires zymarg_remove_item AJAX for each selected row;
 *                  button remains disabled until ≥ 1 row is checked.
 *
 * Controls overview (~36 individual controls / group controls):
 *   Content tab  → Content, Visibility, Behavior sections.
 *   Style tab    → Header, Title, Count Badge, Icon, Edit Button,
 *                  Delete Button sections — all with responsive D/T/M values.
 *
 * @package ZymargCart
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Zymarg_Widget_Cart_Header
 */
class Zymarg_Widget_Cart_Header extends \Elementor\Widget_Base {

	// =========================================================================
	// Widget identity
	// =========================================================================

	public function get_name(): string {
		return 'zymarg-cart-header';
	}

	public function get_title(): string {
		return __( 'Cart Header', 'zymarg-cart' );
	}

	public function get_icon(): string {
		return 'eicon-cart';
	}

	public function get_categories(): array {
		return [ 'zymarg-cart' ];
	}

	public function get_keywords(): array {
		return [ 'zymarg', 'cart', 'header', 'edit', 'woocommerce' ];
	}

	public function get_script_depends(): array {
		return [ 'zymarg-cart', 'zymarg-cart-edit-mode', 'zymarg-cart-ajax' ];
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
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Content', 'zymarg-cart' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'cart_title',
			[
				'label'       => __( 'Cart Title', 'zymarg-cart' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'My Cart', 'zymarg-cart' ),
				'placeholder' => __( 'My Cart', 'zymarg-cart' ),
				'dynamic'     => [ 'active' => true ],
			]
		);

		$this->add_control(
			'edit_btn_label',
			[
				'label'   => __( 'Edit Button Label', 'zymarg-cart' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Edit', 'zymarg-cart' ),
			]
		);

		$this->add_control(
			'done_btn_label',
			[
				'label'   => __( 'Done Button Label (exit edit mode)', 'zymarg-cart' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Done', 'zymarg-cart' ),
			]
		);

		$this->add_control(
			'delete_btn_label',
			[
				'label'   => __( 'Delete Button Label', 'zymarg-cart' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'Delete', 'zymarg-cart' ),
			]
		);

		$this->end_controls_section();

		// ── Section: Visibility ───────────────────────────────────────────────
		$this->start_controls_section(
			'section_visibility',
			[
				'label' => __( 'Visibility', 'zymarg-cart' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_cart_icon',
			[
				'label'        => __( 'Cart Icon', 'zymarg-cart' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_cart_title',
			[
				'label'        => __( 'Cart Title', 'zymarg-cart' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_item_count',
			[
				'label'        => __( 'Item Count Badge', 'zymarg-cart' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_edit_btn',
			[
				'label'        => __( 'Edit Button', 'zymarg-cart' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_delete_btn',
			[
				'label'        => __( 'Delete Button (edit mode)', 'zymarg-cart' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		// ── Section: Behavior ─────────────────────────────────────────────────
		$this->start_controls_section(
			'section_behavior',
			[
				'label' => __( 'Behavior', 'zymarg-cart' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'edit_confirm_dialog',
			[
				'label'        => __( 'Delete Confirmation Dialog', 'zymarg-cart' ),
				'description'  => __( 'Show a confirmation prompt before deleting selected items.', 'zymarg-cart' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'zymarg-cart' ),
				'label_off'    => __( 'Off', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'confirm_dialog_text',
			[
				'label'     => __( 'Confirmation Message', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => __( 'Are you sure you want to remove the selected items?', 'zymarg-cart' ),
				'condition' => [ 'edit_confirm_dialog' => 'yes' ],
			]
		);

		$this->end_controls_section();

		// ─────────────────────────────────────────────────────────────────────
		// STYLE TAB
		// ─────────────────────────────────────────────────────────────────────

		// ── Section: Header ───────────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_header',
			[
				'label' => __( 'Header', 'zymarg-cart' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'header_bg_enabled',
			[
				'label'        => __( 'Background', 'zymarg-cart' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'On', 'zymarg-cart' ),
				'label_off'    => __( 'Off', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'header_bg_color',
			[
				'label'     => __( 'Background Color', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#faf8ff',
				'selectors' => [
					'{{WRAPPER}} .zymarg-cart-header' => 'background-color: {{VALUE}};',
				],
				'condition' => [ 'header_bg_enabled' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'header_padding',
			[
				'label'      => __( 'Padding', 'zymarg-cart' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'top'    => '14', 'right'  => '20',
					'bottom' => '14', 'left'   => '20',
					'unit'   => 'px', 'isLinked' => false,
				],
				'tablet_default' => [
					'top'    => '12', 'right'  => '16',
					'bottom' => '12', 'left'   => '16',
					'unit'   => 'px',
				],
				'mobile_default' => [
					'top'    => '10', 'right'  => '14',
					'bottom' => '10', 'left'   => '14',
					'unit'   => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .zymarg-cart-header' =>
						'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_border_radius',
			[
				'label'      => __( 'Border Radius', 'zymarg-cart' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'default'    => [
					'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12',
					'unit' => 'px', 'isLinked' => true,
				],
				'mobile_default' => [
					'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10',
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .zymarg-cart-header' =>
						'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'show_header_border',
			[
				'label'        => __( 'Border', 'zymarg-cart' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'zymarg-cart' ),
				'label_off'    => __( 'Hide', 'zymarg-cart' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'header_border',
				'selector'  => '{{WRAPPER}} .zymarg-cart-header',
				'condition' => [ 'show_header_border' => 'yes' ],
				'fields_options' => [
					'border' => [ 'default' => 'solid' ],
					'width'  => [
						'default' => [
							'top' => '0.5', 'right' => '0.5',
							'bottom' => '0.5', 'left' => '0.5',
							'unit' => 'px',
						],
					],
					'color'  => [ 'default' => '#d8bfd3' ],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name'      => 'header_shadow',
				'selector'  => '{{WRAPPER}} .zymarg-cart-header',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// ── Section: Title ────────────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_title',
			[
				'label'     => __( 'Title', 'zymarg-cart' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_cart_title' => 'yes' ],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Color', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#131b2e',
				'selectors' => [
					'{{WRAPPER}} .zymarg-cart-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .zymarg-cart-title',
				'fields_options' => [
					'typography'  => [ 'default' => 'yes' ],
					'font_size'   => [
						'default'        => [ 'size' => 16, 'unit' => 'px' ],
						'tablet_default' => [ 'size' => 15, 'unit' => 'px' ],
						'mobile_default' => [ 'size' => 14, 'unit' => 'px' ],
					],
					'font_weight' => [ 'default' => '500' ],
				],
			]
		);

		$this->add_responsive_control(
			'title_gap',
			[
				'label'      => __( 'Gap to Count Badge', 'zymarg-cart' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 0, 'max' => 30 ] ],
				'default'        => [ 'size' => 10, 'unit' => 'px' ],
				'tablet_default' => [ 'size' => 8,  'unit' => 'px' ],
				'mobile_default' => [ 'size' => 6,  'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .zymarg-header-left' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Section: Count Badge ──────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_count',
			[
				'label'     => __( 'Item Count Badge', 'zymarg-cart' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_item_count' => 'yes' ],
			]
		);

		$this->add_control(
			'count_color',
			[
				'label'     => __( 'Color', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#857183',
				'selectors' => [
					'{{WRAPPER}} .zymarg-item-count' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'count_typography',
				'selector' => '{{WRAPPER}} .zymarg-item-count',
				'fields_options' => [
					'typography'  => [ 'default' => 'yes' ],
					'font_size'   => [
						'default'        => [ 'size' => 13, 'unit' => 'px' ],
						'tablet_default' => [ 'size' => 12, 'unit' => 'px' ],
						'mobile_default' => [ 'size' => 11, 'unit' => 'px' ],
					],
				],
			]
		);

		$this->end_controls_section();

		// ── Section: Cart Icon ────────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_icon',
			[
				'label'     => __( 'Cart Icon', 'zymarg-cart' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_cart_icon' => 'yes' ],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Size', 'zymarg-cart' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 10, 'max' => 60 ] ],
				'default'        => [ 'size' => 18, 'unit' => 'px' ],
				'tablet_default' => [ 'size' => 17, 'unit' => 'px' ],
				'mobile_default' => [ 'size' => 16, 'unit' => 'px' ],
				'selectors'  => [
					'{{WRAPPER}} .zymarg-cart-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Color', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#9500a5',
				'selectors' => [
					'{{WRAPPER}} .zymarg-cart-icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Section: Edit Button ──────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_edit_btn',
			[
				'label'     => __( 'Edit Button', 'zymarg-cart' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_edit_btn' => 'yes' ],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'edit_btn_typography',
				'selector' => '{{WRAPPER}} .zymarg-edit-btn',
				'fields_options' => [
					'typography'  => [ 'default' => 'yes' ],
					'font_size'   => [
						'default'        => [ 'size' => 12, 'unit' => 'px' ],
						'tablet_default' => [ 'size' => 12, 'unit' => 'px' ],
						'mobile_default' => [ 'size' => 11, 'unit' => 'px' ],
					],
					'font_weight' => [ 'default' => '400' ],
				],
			]
		);

		$this->start_controls_tabs( 'tabs_edit_btn' );

		$this->start_controls_tab(
			'tab_edit_btn_normal',
			[ 'label' => __( 'Normal', 'zymarg-cart' ) ]
		);

		$this->add_control(
			'edit_btn_text_color',
			[
				'label'     => __( 'Text Color', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#9500a5',
				'selectors' => [
					'{{WRAPPER}} .zymarg-edit-btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'edit_btn_bg_color',
			[
				'label'     => __( 'Background', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => 'transparent',
				'selectors' => [
					'{{WRAPPER}} .zymarg-edit-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_edit_btn_hover',
			[ 'label' => __( 'Hover', 'zymarg-cart' ) ]
		);

		$this->add_control(
			'edit_btn_text_color_hover',
			[
				'label'     => __( 'Text Color', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#bd00d1',
				'selectors' => [
					'{{WRAPPER}} .zymarg-edit-btn:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'edit_btn_bg_color_hover',
			[
				'label'     => __( 'Background', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffd6fb',
				'selectors' => [
					'{{WRAPPER}} .zymarg-edit-btn:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'edit_btn_border',
				'selector'  => '{{WRAPPER}} .zymarg-edit-btn',
				'separator' => 'before',
				'fields_options' => [
					'border' => [ 'default' => 'solid' ],
					'width'  => [
						'default' => [
							'top' => '0.5', 'right' => '0.5',
							'bottom' => '0.5', 'left' => '0.5',
							'unit' => 'px',
						],
					],
					'color'  => [ 'default' => '#9500a5' ],
				],
			]
		);

		$this->add_responsive_control(
			'edit_btn_border_radius',
			[
				'label'      => __( 'Border Radius', 'zymarg-cart' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8',
					'unit' => 'px', 'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .zymarg-edit-btn' =>
						'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'edit_btn_padding',
			[
				'label'      => __( 'Padding', 'zymarg-cart' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top' => '6', 'right' => '14', 'bottom' => '6', 'left' => '14',
					'unit' => 'px', 'isLinked' => false,
				],
				'tablet_default' => [
					'top' => '6', 'right' => '12', 'bottom' => '6', 'left' => '12',
					'unit' => 'px',
				],
				'mobile_default' => [
					'top' => '5', 'right' => '10', 'bottom' => '5', 'left' => '10',
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .zymarg-edit-btn' =>
						'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// ── Section: Delete Button ────────────────────────────────────────────
		$this->start_controls_section(
			'section_style_delete_btn',
			[
				'label'     => __( 'Delete Button', 'zymarg-cart' ),
				'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [ 'show_delete_btn' => 'yes' ],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name'     => 'delete_btn_typography',
				'selector' => '{{WRAPPER}} .zymarg-delete-btn',
				'fields_options' => [
					'typography'  => [ 'default' => 'yes' ],
					'font_size'   => [
						'default'        => [ 'size' => 12, 'unit' => 'px' ],
						'tablet_default' => [ 'size' => 12, 'unit' => 'px' ],
						'mobile_default' => [ 'size' => 11, 'unit' => 'px' ],
					],
					'font_weight' => [ 'default' => '400' ],
				],
			]
		);

		$this->start_controls_tabs( 'tabs_delete_btn' );

		$this->start_controls_tab(
			'tab_delete_btn_normal',
			[ 'label' => __( 'Normal', 'zymarg-cart' ) ]
		);

		$this->add_control(
			'delete_btn_text_color',
			[
				'label'     => __( 'Text Color', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#a32d2d',
				'selectors' => [
					'{{WRAPPER}} .zymarg-delete-btn:not([disabled])' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'delete_btn_bg_color',
			[
				'label'     => __( 'Background', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#fcebeb',
				'selectors' => [
					'{{WRAPPER}} .zymarg-delete-btn:not([disabled])' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_delete_btn_hover',
			[ 'label' => __( 'Hover', 'zymarg-cart' ) ]
		);

		$this->add_control(
			'delete_btn_text_color_hover',
			[
				'label'     => __( 'Text Color', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .zymarg-delete-btn:not([disabled]):hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'delete_btn_bg_color_hover',
			[
				'label'     => __( 'Background', 'zymarg-cart' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'default'   => '#a32d2d',
				'selectors' => [
					'{{WRAPPER}} .zymarg-delete-btn:not([disabled]):hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name'      => 'delete_btn_border',
				'selector'  => '{{WRAPPER}} .zymarg-delete-btn',
				'separator' => 'before',
				'fields_options' => [
					'border' => [ 'default' => 'solid' ],
					'width'  => [
						'default' => [
							'top' => '0.5', 'right' => '0.5',
							'bottom' => '0.5', 'left' => '0.5',
							'unit' => 'px',
						],
					],
					'color'  => [ 'default' => '#a32d2d' ],
				],
			]
		);

		$this->add_responsive_control(
			'delete_btn_border_radius',
			[
				'label'      => __( 'Border Radius', 'zymarg-cart' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default'    => [
					'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8',
					'unit' => 'px', 'isLinked' => true,
				],
				'selectors'  => [
					'{{WRAPPER}} .zymarg-delete-btn' =>
						'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'delete_btn_padding',
			[
				'label'      => __( 'Padding', 'zymarg-cart' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default'    => [
					'top' => '6', 'right' => '14', 'bottom' => '6', 'left' => '14',
					'unit' => 'px', 'isLinked' => false,
				],
				'tablet_default' => [
					'top' => '6', 'right' => '12', 'bottom' => '6', 'left' => '12',
					'unit' => 'px',
				],
				'mobile_default' => [
					'top' => '5', 'right' => '10', 'bottom' => '5', 'left' => '10',
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .zymarg-delete-btn' =>
						'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// =========================================================================
	// Render
	// =========================================================================

	/**
	 * Renders the widget's frontend HTML by including the cart-header template.
	 * The template receives $settings and $item_count via PHP scope.
	 */
	protected function render(): void {
		$settings   = $this->get_settings_for_display();
		$item_count = Zymarg_Cart_Helpers::is_cart_available()
			? (int) WC()->cart->get_cart_contents_count()
			: 0;

		include ZYMARG_CART_PATH . 'templates/cart-header.php';
	}

	/**
	 * Renders the live-preview template for the Elementor editor.
	 * Uses Elementor's Underscore.js template syntax.
	 */
	protected function content_template(): void {
		?>
		<#
		var title      = settings.cart_title      || 'My Cart';
		var editLabel  = settings.edit_btn_label  || 'Edit';
		var doneLabel  = settings.done_btn_label  || 'Done';
		var delLabel   = settings.delete_btn_label || 'Delete';
		#>
		<div class="zymarg-cart-header">
			<div class="zymarg-header-left">
				<# if ( 'yes' === settings.show_cart_icon ) { #>
					<i class="ti ti-shopping-cart zymarg-cart-icon" aria-hidden="true"></i>
				<# } #>
				<# if ( 'yes' === settings.show_cart_title ) { #>
					<span class="zymarg-cart-title">{{{ title }}}</span>
				<# } #>
				<# if ( 'yes' === settings.show_item_count ) { #>
					<span class="zymarg-item-count">(0 items)</span>
				<# } #>
			</div>
			<div class="zymarg-header-right">
				<# if ( 'yes' === settings.show_delete_btn ) { #>
					<button class="zymarg-delete-btn" disabled aria-label="{{{ delLabel }}}">{{{ delLabel }}}</button>
				<# } #>
				<# if ( 'yes' === settings.show_edit_btn ) { #>
					<button class="zymarg-edit-btn"
						data-edit-label="{{{ editLabel }}}"
						data-done-label="{{{ doneLabel }}}">{{{ editLabel }}}</button>
				<# } #>
			</div>
		</div>
		<?php
	}
}
