/**
 * ZYMARG Cart — Edit mode (delete mode) toggle module.
 *
 * Manages the two-state edit toggle in Widget 1 (Cart Header):
 *
 * NORMAL MODE (default):
 *   Product checkboxes → checkout selection.
 *   Delete button hidden.
 *   Edit button shows "Edit" label.
 *
 * EDIT MODE (.zymarg-edit-mode on <body>):
 *   Product checkboxes → deletion selection (no totals recalc).
 *   Delete button visible, disabled until ≥ 1 product is checked.
 *   Edit button shows "Done" label.
 *   Delete button click → optional confirmation → removes selected items
 *     sequentially via ZymargAjax.removeItem() → exits edit mode when done.
 *
 * CSS responsibilities (defined in zymarg-cart.css, not here):
 *   .zymarg-edit-mode .zymarg-delete-btn   { display: flex; }
 *   .zymarg-edit-mode .zymarg-save-later-btn { display: none; }
 *   .zymarg-edit-mode .zymarg-product-row  { ... visual cues ... }
 *
 * Dependencies: jQuery (global), zymargCartData (wp_localize_script),
 *               ZymargAjax (zymarg-cart-ajax.js).
 *
 * @package ZymargCart
 * @since   1.0.0
 */

/* global zymargCartData, ZymargAjax, ZymargCheckbox */
( function ( $, cfg ) {
    'use strict';

    if ( ! cfg ) {
        return;
    }

    // =========================================================================
    // Constants
    // =========================================================================

    var EDIT_CLASS     = 'zymarg-edit-mode';
    var SEL_EDIT_BTN   = '.zymarg-edit-btn';
    var SEL_DEL_BTN    = '.zymarg-delete-btn';
    var SEL_PRODUCT_CB = '.zymarg-product-cb';

    // =========================================================================
    // Enter edit mode
    // =========================================================================

    /**
     * Activates edit/delete mode.
     *  - Adds EDIT_CLASS to <body> (CSS shows delete button, hides save-for-later).
     *  - Unchecks all product checkboxes (clean slate for deletion selection).
     *  - Updates edit button label to "Done".
     *  - Disables delete button (nothing selected yet).
     *
     * @param {jQuery} $editBtn  The edit button that was clicked.
     */
    function enterEditMode( $editBtn ) {
        document.body.classList.add( EDIT_CLASS );

        // Uncheck all product checkboxes — fresh deletion selection state.
        $( SEL_PRODUCT_CB ).prop( 'checked', false );

        // v1.1.3: also clear the global selected-keys array. Pre-1.1.3 the
        // unchecking above did not propagate to ZymargCart._selectedKeys
        // because the checkbox module's edit-mode guard short-circuits the
        // selection cascade — so any code reading getSelectedKeys() while in
        // edit mode would receive a stale list.
        if ( window.ZymargCart ) {
            ZymargCart.updateSelectedKeys( [] );
        }

        // Swap label: "Edit" → "Done".
        $editBtn.find( '.zymarg-btn-label' )
            .text( $editBtn.data( 'done-label' ) || cfg.i18n.done || 'Done' );
        $editBtn
            .find( '.zymarg-btn-icon' )
            .removeClass( 'ti-edit' )
            .addClass( 'ti-check' );
        $editBtn.attr( 'aria-pressed', 'true' );

        // Locate and disable the delete button.
        var $delBtn = findDeleteBtn( $editBtn );
        $delBtn.prop( 'disabled', true ).attr( 'aria-disabled', 'true' );
    }

    // =========================================================================
    // Exit edit mode
    // =========================================================================

    /**
     * Deactivates edit/delete mode.
     *  - Removes EDIT_CLASS from <body>.
     *  - Re-checks all product checkboxes (restore checkout selection).
     *  - Updates edit button label back to "Edit".
     *  - Resyncs vendor + master checkbox states.
     *
     * @param {jQuery} $editBtn  The edit button.
     */
    function exitEditMode( $editBtn ) {
        document.body.classList.remove( EDIT_CLASS );

        // Do NOT re-check checkboxes on exit — checkboxes should only be checked
        // when the user explicitly selects them. Re-checking here was causing all
        // remaining items to appear selected after a deletion, contradicting the
        // "unchecked by default" behaviour.

        // Swap label: "Done" → "Edit".
        $editBtn.find( '.zymarg-btn-label' )
            .text( $editBtn.data( 'edit-label' ) || cfg.i18n.edit || 'Edit' );
        $editBtn
            .find( '.zymarg-btn-icon' )
            .removeClass( 'ti-check' )
            .addClass( 'ti-edit' );
        $editBtn.attr( 'aria-pressed', 'false' );

        // Resyncs vendor + master checkboxes and fires a getTotals call.
        if ( window.ZymargCheckbox ) {
            ZymargCheckbox.syncAll();
        }
    }

    // =========================================================================
    // Delete button state
    // =========================================================================

    /**
     * Enables or disables the delete button based on whether any product
     * checkboxes are currently checked in edit mode.
     */
    function updateDeleteBtnState() {
        var anyChecked = $( SEL_PRODUCT_CB + ':checked' ).length > 0;
        $( SEL_DEL_BTN )
            .prop( 'disabled', ! anyChecked )
            .attr( 'aria-disabled', anyChecked ? 'false' : 'true' );
    }

    // =========================================================================
    // Delete selected items
    // =========================================================================

    /**
     * Collects all checked product rows and removes them sequentially via AJAX.
     * After all deletions, exits edit mode.
     *
     * Sequential (not parallel) deletion prevents race conditions in WC cart
     * session recalculations and ensures each removeItem response is processed
     * before the next call fires.
     *
     * @param {jQuery} $delBtn  The delete button that was pressed.
     */
    function deleteSelectedItems( $delBtn ) {
        // Collect targets before any DOM changes.
        var targets = [];
        $( SEL_PRODUCT_CB + ':checked' ).each( function () {
            var $row = $( this ).closest( '.zymarg-product-row' );
            var key  = $row.data( 'cart-key' );
            if ( key ) {
                targets.push( { key: key, $row: $row } );
            }
        } );

        if ( ! targets.length ) {
            return;
        }

        // Confirmation dialog (if enabled on the delete button).
        if ( '1' === String( $delBtn.data( 'confirm' ) ) ) {
            var confirmText = $delBtn.data( 'confirm-text' ) ||
                cfg.i18n.confirmDelete ||
                'Are you sure you want to remove the selected items?';

            if ( ! window.confirm( confirmText ) ) {
                return;
            }
        }

        // Disable the delete button during the operation.
        $delBtn.prop( 'disabled', true ).attr( 'aria-disabled', 'true' );

        var $editBtn = findEditBtn( $delBtn );
        var index    = 0;

        /**
         * Recursively removes items one at a time, awaiting each AJAX response
         * before firing the next. v1.1.3: pre-1.1.3 used setTimeout(120) which
         * fired the next request before the previous one had committed, causing
         * race conditions where two parallel deletions both reported
         * vendor_empty: false (each read the cart before the other had
         * committed) and the empty vendor block stayed visible — and one of
         * the two items might not actually be deleted server-side at all.
         */
        function removeNext() {
            if ( index >= targets.length ) {
                // All done — exit edit mode.
                exitEditMode( $editBtn );
                return;
            }

            var target = targets[ index++ ];

            if ( ! target.$row.length || ! target.$row.parent().length ) {
                // Row already removed (e.g. vendor group cleared by earlier call).
                setTimeout( removeNext, 0 );
                return;
            }

            if ( ! window.ZymargAjax ) {
                // ZymargAjax not loaded — bail.
                exitEditMode( $editBtn );
                return;
            }

            var deferred = ZymargAjax.removeItem(
                target.$row,
                target.key,
                [] // Empty selectedKeys — we're deleting, not checking out.
            );

            // True sequential — wait for the server to finish before firing the
            // next request. Use .always() so a failed request also advances the
            // queue (otherwise a single error would freeze the whole bulk
            // delete). A small 80 ms gap between iterations gives the
            // remove-row fade animation visual breathing room.
            if ( deferred && typeof deferred.always === 'function' ) {
                deferred.always( function () {
                    setTimeout( removeNext, 80 );
                } );
            } else {
                // Fallback for any caller / version that doesn't return a
                // deferred — keep the original behaviour rather than hanging.
                setTimeout( removeNext, 400 );
            }
        }

        removeNext();
    }

    // =========================================================================
    // DOM helpers
    // =========================================================================

    /**
     * Finds the delete button in the same header row as the edit button.
     *
     * @param  {jQuery} $editBtn
     * @returns {jQuery}
     */
    function findDeleteBtn( $editBtn ) {
        return $editBtn.closest( '.zymarg-header-right, .zymarg-cart-header' )
                       .find( SEL_DEL_BTN );
    }

    /**
     * Finds the edit button in the same header row as the delete button.
     *
     * @param  {jQuery} $delBtn
     * @returns {jQuery}
     */
    function findEditBtn( $delBtn ) {
        return $delBtn.closest( '.zymarg-header-right, .zymarg-cart-header' )
                      .find( SEL_EDIT_BTN );
    }

    // =========================================================================
    // Event listeners
    // =========================================================================

    /** Edit button click — toggle edit mode on/off. */
    $( document ).on( 'click', SEL_EDIT_BTN, function ( e ) {
        e.preventDefault();
        var $btn    = $( this );
        var inEdit  = document.body.classList.contains( EDIT_CLASS );
        inEdit ? exitEditMode( $btn ) : enterEditMode( $btn );
    } );

    /** Delete button click — remove selected items. */
    $( document ).on( 'click', SEL_DEL_BTN + ':not([disabled])', function ( e ) {
        e.preventDefault();
        deleteSelectedItems( $( this ) );
    } );

    /**
     * Product checkbox change in edit mode → update delete button state.
     * The checkbox module fires this event to avoid direct coupling.
     */
    document.addEventListener( 'zymarg:editCheckboxChanged', function () {
        if ( document.body.classList.contains( EDIT_CLASS ) ) {
            updateDeleteBtnState();
        }
    } );

    /**
     * Keyboard: Escape key exits edit mode from anywhere on the page.
     */
    $( document ).on( 'keydown', function ( e ) {
        if ( e.key === 'Escape' && document.body.classList.contains( EDIT_CLASS ) ) {
            var $editBtn = $( SEL_EDIT_BTN ).first();
            if ( $editBtn.length ) {
                exitEditMode( $editBtn );
            }
        }
    } );

    // =========================================================================
    // Init — ensure delete button is hidden on load (CSS also handles this,
    // but set as a safe fallback in case CSS is slow to load).
    // =========================================================================

    $( function () {
        // Only hide if <body> doesn't already have the edit class.
        if ( ! document.body.classList.contains( EDIT_CLASS ) ) {
            $( SEL_DEL_BTN ).hide();
        }
    } );

} )( jQuery, zymargCartData );
