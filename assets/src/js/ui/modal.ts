import { qsa } from '../core/dom.js';
import { createFocusTrap,type FocusTrap } from 'focus-trap';
import { restoreFocus,storeFocus } from "../utils/focus.js";
import { lockScroll,unlockScroll } from "../utils/scroll-lock.js";

/**
 * Initializes all modals on the page.
 *
 * @example
 * ```html
 * <button data-modal-open="contact">
 *   Open
 * </button>
 *
 * <div
 *   role="dialog"
 *   data-modal="contact"
 *   aria-modal="true"
 *   hidden
 * >
 *   <button data-modal-close>
 *     Close
 *   </button>
 *
 *   ... Content
 * </div>
 * ```
 */
export function initModals() {
  const modals = new Map<string,HTMLElement>();

  qsa<HTMLElement>( '[data-modal]' ).forEach( ( modal ) => {
    const id = modal.dataset.modal;
    if ( id ) modals.set( id,modal );
  } )

  qsa<HTMLElement>( '[data-modal-open]' ).forEach( ( trigger ) => {
    const id = trigger.dataset.modalOpen;
    const modal = id ? modals.get( id ) : null;
    if ( !modal ) return;

    let trap: FocusTrap | null = null;
    let lastFocused: HTMLElement | null = null;

    const open = () => {
      lastFocused = storeFocus();

      modal.hidden = false;
      lockScroll();

      try {
        trap = createFocusTrap( modal, {
          escapeDeactivates: false,
        } );
        trap.activate();
      } catch (error) {
        console.error('Focus trap failed for modal:', error)
      }

      document.addEventListener( 'keydown',onKeydown );
    }

    const close = () => {
      trap?.deactivate();
      trap = null;

      modal.hidden = true;
      unlockScroll();
      restoreFocus( lastFocused );

      document.removeEventListener( 'keydown',onKeydown );
    }

    const onKeydown = ( event: KeyboardEvent ) => {
      if ( event.key === 'Escape' ) close();
    }

    trigger.addEventListener( 'click',open );

    qsa( '[data-modal-close]',modal ).forEach( ( closeBtn ) => {
      closeBtn.addEventListener( 'click',close );
    } );
  } )
}