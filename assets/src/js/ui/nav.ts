import { qs } from "../core/dom.js";
import { lockScroll,unlockScroll } from "../utils/scroll-lock.js";
import { focusFirst,restoreFocus,storeFocus,trapFocus } from "../utils/focus.js";

/**
 * Initialize the page's responsive navigation.
 *
 * The panel is expected to sit off-canvas by default (CSS transform) and
 * slide into view when `.is-open` is added — the `hidden` attribute is
 * still used to fully remove it from the accessibility tree and tab
 * order once it has finished animating closed.
 *
 * @example
 * ```html
 * <button
 *   data-nav-toggle
 *   aria-expanded="false"
 *   aria-controls="site-nav"
 * >
 *  Toggle Menu
 * </button>
 *
 * <nav id="site-nav" data-nav-panel hidden>
 *  ... nav items go here
 * </nav>
 *
 * <div data-nav-backdrop hidden></div>
 * ```
 *
 * @param root
 */
export function initNav( root: HTMLElement = document.body ) {
  const toggle = qs<HTMLButtonElement>( '[data-nav-toggle]',root );
  const panel = qs<HTMLElement>( '[data-nav-panel]',root );
  const backdrop = qs<HTMLElement>( '[data-nav-backdrop]',root );
  const closeBtn = qs<HTMLButtonElement>( '[data-nav-close]',root );

  if ( !toggle || !panel ) return;

  let lastFocused: HTMLElement | null = null;
  let closeTimeout: ReturnType<typeof setTimeout> | null = null;

  // Matches the CSS transition duration on [data-nav-panel]; used as a
  // fallback in case transitionend doesn't fire (e.g. reduced motion).
  const TRANSITION_MS = 300;

  const open = () => {
    if ( closeTimeout ) {
      clearTimeout( closeTimeout );
      closeTimeout = null;
    }

    lastFocused = storeFocus();

    panel.hidden = false;
    if ( backdrop ) backdrop.hidden = false;

    // Force layout so the browser registers the off-canvas position
    // before the class flips, otherwise the transition gets skipped.
    void panel.offsetHeight;

    panel.classList.add( 'is-open' );
    if ( backdrop ) backdrop.classList.add( 'is-open' );

    toggle.setAttribute( 'aria-expanded','true' );
    lockScroll();
    focusFirst( panel );

    document.addEventListener( 'keydown',onKeydown );
  }

  const close = () => {
    panel.classList.remove( 'is-open' );
    if ( backdrop ) backdrop.classList.remove( 'is-open' );

    toggle.setAttribute( 'aria-expanded','false' );

    unlockScroll();
    restoreFocus( lastFocused );

    document.removeEventListener( 'keydown',onKeydown );

    // Wait for the slide-out transition to finish before pulling the
    // panel out of the accessibility tree / tab order.
    closeTimeout = setTimeout( () => {
      panel.hidden = true;
      if ( backdrop ) backdrop.hidden = true;
      closeTimeout = null;
    },TRANSITION_MS );
  }

  const onKeydown = ( event: KeyboardEvent ) => {
    if ( event.key === 'Escape' ) {
      close();
      return;
    }

    trapFocus( panel,event );
  }

  toggle.addEventListener( 'click',() => {
    const expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
    expanded ? close() : open();
  } )

  backdrop?.addEventListener( 'click',close );
  closeBtn?.addEventListener( 'click',close );
}