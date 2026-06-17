/**
 * ZYMARG Cart — Breakdown panel slide module.
 *
 * Manages the sliding Part A breakdown panel in Widget 3 (Cart Total).
 *
 * Clicking (or pressing Enter/Space on) the subtotal bar toggles the breakdown
 * panel open or closed via CSS class manipulation. The CSS handles the actual
 * max-height transition and arrow rotation (defined in zymarg-cart.css).
 *
 * State persistence:
 *   The open/closed state is stored in sessionStorage under the key
 *   'zymargBreakdownOpen'. On page load this overrides the widget's
 *   "Open by default" Elementor setting so the state survives page navigation
 *   within the same browser tab.
 *
 * CSS classes toggled by this module:
 *   .breakdown-open        → added to .zymarg-breakdown-panel when open
 *   .breakdown-arrow-open  → added to .zymarg-breakdown-arrow when open
 *                            (CSS rotates the icon 180°)
 *
 * Accessibility:
 *   aria-expanded  on .zymarg-subtotal-bar  (true when open)
 *   aria-hidden    on .zymarg-breakdown-panel (false when open)
 *
 * Dependencies: jQuery (global), zymargCartData (wp_localize_script).
 *
 * @package ZymargCart
 * @since   1.0.0
 */

/* global zymargCartData */
( function ( $, cfg ) {
    'use strict';

    if ( ! cfg ) {
        return;
    }

    // =========================================================================
    // Constants
    // =========================================================================

    var STORAGE_KEY    = 'zymargBreakdownOpen';
    var CLASS_OPEN     = 'breakdown-open';
    var CLASS_ARROW    = 'breakdown-arrow-open';
    var SEL_BAR        = '.zymarg-subtotal-bar';
    var SEL_PANEL      = '.zymarg-breakdown-panel';
    var SEL_ARROW      = '.zymarg-breakdown-arrow';
    var SEL_TOTAL      = '.zymarg-cart-total';

    // =========================================================================
    // Core open / close helpers
    // =========================================================================

    /**
     * Opens the breakdown panel.
     *
     * @param {jQuery} $panel  The .zymarg-breakdown-panel element.
     * @param {jQuery} $bar    The .zymarg-subtotal-bar element.
     */
    function openPanel( $panel, $bar ) {
        $panel
            .addClass( CLASS_OPEN )
            .attr( 'aria-hidden', 'false' );

        $bar.attr( 'aria-expanded', 'true' );
        $bar.find( SEL_ARROW ).addClass( CLASS_ARROW );

        try {
            sessionStorage.setItem( STORAGE_KEY, '1' );
        } catch ( err ) { /* sessionStorage may be blocked */ }
    }

    /**
     * Closes the breakdown panel.
     *
     * @param {jQuery} $panel  The .zymarg-breakdown-panel element.
     * @param {jQuery} $bar    The .zymarg-subtotal-bar element.
     */
    function closePanel( $panel, $bar ) {
        $panel
            .removeClass( CLASS_OPEN )
            .attr( 'aria-hidden', 'true' );

        $bar.attr( 'aria-expanded', 'false' );
        $bar.find( SEL_ARROW ).removeClass( CLASS_ARROW );

        try {
            sessionStorage.setItem( STORAGE_KEY, '0' );
        } catch ( err ) { /* sessionStorage may be blocked */ }
    }

    /**
     * Toggles the breakdown panel open or closed.
     *
     * @param {jQuery} $bar  The .zymarg-subtotal-bar that was activated.
     */
    function togglePanel( $bar ) {
        var $total = $bar.closest( SEL_TOTAL );
        var $panel = $total.length
            ? $total.find( SEL_PANEL )
            : $( SEL_PANEL ).first();

        if ( ! $panel.length ) {
            return;
        }

        if ( $panel.hasClass( CLASS_OPEN ) ) {
            closePanel( $panel, $bar );
        } else {
            openPanel( $panel, $bar );
        }
    }

    // =========================================================================
    // Event listeners
    // =========================================================================

    /** Mouse / touch click on the subtotal bar. */
    $( document ).on( 'click', SEL_BAR, function () {
        togglePanel( $( this ) );
    } );

    /**
     * Keyboard activation: Enter or Space on the subtotal bar.
     * The bar has role="button" and tabindex="0" in the template.
     */
    $( document ).on( 'keydown', SEL_BAR, function ( e ) {
        if ( e.key === 'Enter' || e.key === ' ' ) {
            e.preventDefault();
            togglePanel( $( this ) );
        }
    } );

    // =========================================================================
    // Auto-expand when items are selected (v1.0.7)
    // =========================================================================

    /**
     * Listens for the zymarg:selectionChanged event broadcast by the checkbox
     * module. Opens the breakdown panel automatically when at least one product
     * is checked so the user can see the updated order summary without having
     * to tap the bar manually.
     */
    document.addEventListener( 'zymarg:selectionChanged', function ( e ) {
        var keys = ( e && e.detail && e.detail.selectedKeys ) ? e.detail.selectedKeys : [];

        var $bar   = $( SEL_BAR ).first();
        var $total = $bar.closest( SEL_TOTAL );
        var $panel = $total.length ? $total.find( SEL_PANEL ) : $( SEL_PANEL ).first();

        if ( ! $panel.length || ! $bar.length ) {
            return;
        }

        if ( keys.length > 0 && ! $panel.hasClass( CLASS_OPEN ) ) {
            openPanel( $panel, $bar );
        } else if ( keys.length === 0 && $panel.hasClass( CLASS_OPEN ) ) {
            closePanel( $panel, $bar );
        }
    } );

    // =========================================================================
    // Init — always start collapsed on page load (v1.0.7)
    // =========================================================================

    $( function () {
        var $bar   = $( SEL_BAR ).first();
        var $total = $bar.closest( SEL_TOTAL );
        var $panel = $total.length ? $total.find( SEL_PANEL ) : $( SEL_PANEL ).first();

        if ( ! $panel.length ) {
            return;
        }

        // Always start closed regardless of Elementor setting or sessionStorage.
        closePanel( $panel, $bar );

        // Clear any previously stored open state.
        try {
            sessionStorage.removeItem( STORAGE_KEY );
        } catch ( err ) { /* sessionStorage may be blocked */ }
    } );

} )( jQuery, zymargCartData );
